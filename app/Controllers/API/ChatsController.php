<?php

namespace App\Controllers\API;
use App\Core\DB;
use PDO;
use Dotenv\Dotenv;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../../vendor/autoload.php';

class ChatsController{
    protected $pdo;
    private $userId;
    protected $emailHost;
    protected $emailPassword;
    protected $emailEnct;
    protected $emailPort;
    protected $emailUsername;
    public function __construct(){
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();
        $this->pdo = DB::connection();
        $this->userId = $_SESSION['user']['user_id'] ?? $_SESSION['guest']['id'] ?? null;
        $this->emailHost = $_ENV['MAIL_HOST'] ?? null;
        $this->emailUsername = $_ENV['MAIL_USERNAME'] ?? null;
        $this->emailPassword = $_ENV['MAIL_PASSWORD'] ?? null;
    }

    public function createChatUser(){
        if (!isset($_SESSION['user']['user_id'])){

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
                return;
            }
            
            $data = json_decode(file_get_contents("php://input"), true);

            $email = $data['email'];
            $fullname = $data['fullname'];

            if (empty($email) || empty($fullname)){
                echo json_encode(['status' => 'error', 'message' => 'All field required']);
                return;
            }

            if (!preg_match('/^[a-zA-Z|| ]+$/', $fullname)){
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid name'
                ]);
                return;
            }

            if (!filter_var( $email, FILTER_VALIDATE_EMAIL)){
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid email address'
                ]);
                return;
            }

            $stmt = $this->pdo->prepare("SELECT * FROM chats_reg WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0){
                echo json_encode([
                    'status' => 'success',
                    'user' => $fullname
                ]);
                return;
            }

            $stmt = $this->pdo->prepare("INSERT INTO `chats_reg`(`user_id`, `email`, `fullname`) VALUES (?,?,?)");
            $stmt->execute([$this->userId, $email, $fullname]);

            echo json_encode([
                'status' => 'success',
                'user' => $fullname
            ]);
        }
    }

    public function addChat(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }
        
        $message = htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8');
        $image = '';

        if (empty($_FILES['image']['name'] ?? '') && empty($message)) {
            return;
        }

        if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
            $uploadDir = __DIR__ . "../../../../public/uploads/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $filename   = time() . "_" . basename($_FILES['image']['name']);
            $targetFile = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $image = $filename;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Image upload failed']);
                return;
            }
        }
            
        try{
            $recieverId = 'admin';

            $stmt = $this->pdo->prepare("INSERT INTO `chats`(`user_id`, `reciever_id`, `message`, `status`, `image`) VALUES (?,?,?,?,?)");
            $result = $stmt->execute([$this->userId, $recieverId, $message, 'new', $image]);

            if ($result){
                echo json_encode([
                    'status'  => 'success',
                    'message' => 'message recieved'
                ]);

                $this->sentChatMessage($message);
            }
        }catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            return;
        }
    }

    public function getChats(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }
        
        $stmt = $this->pdo->prepare("SELECT * FROM chats WHERE user_id = ? OR reciever_id = ? ORDER BY id");
        $stmt->execute([$this->userId, $this->userId]);

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            //print_r($rows);

            echo json_encode([
                'status' => 'success',
                'message' => $rows
            ]);
        }
    }

    public function consult(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"), true);

        $firstname = $data['firstname'];
        $lastname = $data['lastname'];
        $email = $data['email'];
        $phone = $data['phone'];
        $code = $data['code'];

        if (empty($firstname) || empty($lastname) || empty($email) || empty($phone) || empty($code)){
            echo json_encode([
                'status' => 'error',
                'message' => 'All field required'
            ]);
            return;
        }

        if (!preg_match('/^[a-zA-Z]+$/', $firstname)){
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid first name'
            ]);
            return;
        }

        if (!preg_match('/^[a-zA-Z]+$/', $lastname)){
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid last name'
            ]);
            return;
        }

        if (!preg_match('/^[+][0-9]{1,3}$/', $code)){
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid country code'
            ]);
            return;
        }

        if (!preg_match('/^[0-9]{7,11}$/', $phone)){
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid phone number'
            ]);
            return;
        }

        if (!filter_var( $email, FILTER_VALIDATE_EMAIL)){
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid email address'
            ]);
            return;
        }

        $consultResponse = $this->sendConsultMail($firstname, $lastname, $email, $code, $phone);
        
        $consult_status = $consultResponse['status'] ?? 'unknown';
        $consult_msg = $consultResponse['message'] ?? 'unknown';

        echo json_encode([
            'status' => $consult_status,
            'message' => $consult_msg
        ]);
    }

    private function sentChatMessage($message){
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $this->emailHost; 
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->emailUsername; 
            $mail->Password   = $this->emailPassword; 
            $mail->SMTPSecure = 'ssl'; 
            $mail->Port       = 587;

            // Email Headers
            $mail->isHTML(true);
            $mail->setFrom('contact@iruhost.com', 'IruHost');
            $mail->addAddress('osemensilas@gmail.com'); 
            $mail->Subject = 'New Message from ChatBox';
            $mail->Body = "
            <div style='font-family: Arial, sans-serif; color: #333; padding: 20px;'>
                <h2 style='color: #000000; paddin'>Password Reset</h2>
                <p>{$message}</p>
            </div>
            ";

        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => "Email failed: {$mail->ErrorInfo}"
            ]);
        }
    }

    private function sendConsultMail($firstname, $lastname, $email, $code, $phone){
        // SMTP Configuration
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $this->emailHost; 
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->emailUsername; 
            $mail->Password   = $this->emailPassword; 
            $mail->SMTPSecure = 'ssl'; 
            $mail->Port       = 587;

            // Email Headers
            $mail->isHTML(true);
            $mail->setFrom('contact@iruhost.com', 'IruHost');
            $mail->addAddress('osemensilas@gmail.com'); 
            $mail->Subject = 'New Message from ChatBox';
            $mail->Body = "
            <div style='font-family: Arial, sans-serif; color: #333; padding: 20px;'>
                <h2 style='color: #000000; paddin'>Password Reset</h2>
                <p>Senders Firstname: {$firstname}</p>
                <p>Senders Lastname: {$lastname}</p>
                <p>Senders Email: {$email}</p>
                <p>Senders Phone Number: {$phone}</p>
                <p>Country Code: {$code}</p>
            </div>
            ";

            // Send Mail
            if ($mail->send()) {
                $response['status'] = 'success';
                $response['message'] = 'Message sent successfully';
            } else {
                $response['status'] = 'error';
                $response['message'] = 'Message not sent. Check connection';
            }
        } catch (Exception $e) {
            $response['status'] = 'error';
            $response['message'] = "Email failed: {$mail->ErrorInfo}";
        }

        return json_encode([
            'status' => $response['status'],
            'message' => $response['message']
        ]);
    }
}