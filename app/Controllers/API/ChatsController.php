<?php

namespace App\Controllers\API;
use App\Core\DB;
use PDO;
use Dotenv\Dotenv;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../../vendor/autoload.php';

use Resend;

class ChatsController{
    protected $pdo;
    private $userId;
    protected $emailHost;
    protected $emailPassword;
    protected $emailEnct;
    protected $emailPort;
    protected $emailUsername;
    protected $resend;
    protected $resendApiCode;
    public function __construct(){
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();
        $this->pdo = DB::connection();
        $this->userId = $_SESSION['user']['user_id'] ?? $_SESSION['guest']['id'] ?? null;
        $this->emailHost = $_ENV['MAIL_HOST'] ?? null;
        $this->emailUsername = $_ENV['MAIL_USERNAME'] ?? null;
        $this->emailPassword = $_ENV['MAIL_PASSWORD'] ?? null;
        $this->resendApiCode = $_ENV['RESEND_API_KEY'] ?? null;
        $this->resend = Resend::client($this->resendApiCode);
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
       try {
            $this->resend->emails->send([
                'from' => 'IruHost <contact@iruhost.com>',
                'to' => ['osemensilas@gmail.com'],
                'subject' => 'New Chat',
                'html' => "
                <p>{$message}</p>
                "
            ]);

        } catch (\Exception $e) {
            
        }
    }

    private function sendConsultMail($firstname, $lastname, $email, $code, $phone){
        // SMTP Configuration
        try {
            $result = $this->resend->emails->send([
                'from' => 'IruHost <contact@iruhost.com>',
                'to' => ['osemensilas@gmail.com'],
                'subject' => 'Consultation Request',
                'html' => "<p>Firstname: {$firstname}, Lastname: {$lastname}, Email: {$email}, Phone Code: {$code}, Phone: {$phone}</p>"
            ]);

            return [
                'status' => 'successful',
                'message' => $result
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public function openTicket(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $name = $data["name"];
        $email = $data["email"];
        $subject = $data["subject"];
        $department = $data["department"];
        $priority = $data["priority"];
        $message = $data["message"];
        $userId = $this->userId;

        $parts = preg_split('/\s+/', trim($name));

        $avatar = strtoupper(
            substr($parts[0], 0, 1) . 
            (isset($parts[1]) ? substr($parts[1], 0, 1) : '')
        );

        if (empty($email) || empty($subject) || empty($department) || empty($priority) || empty($message)){
            echo json_encode([
                'status' => 'error',
                'message' => 'All field required'
            ]);
            return;
        }

        if (strlen($subject) < 5) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Subject is too short. Please be more specific.'
            ]);
            return;
        }

        if (strlen($subject) > 100) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Subject is too long. Keep it under 100 characters.'
            ]);
            return;
        }

        if ($subject !== strip_tags($subject)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'HTML tags are not allowed in the subject.'
            ]);
            return;
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Please enter a valid email address'
            ]);
            return;
        }

        // Validate priority (optional)
        $allowedPriorities = ['Low', 'Medium', 'High'];
        if (!in_array($priority, $allowedPriorities)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid priority selected'
            ]);
            return;
        }

        // Validate message content
        if (strlen($message) < 10) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Your message is too short. Please provide more details.'
            ]);
            return;
        }

        if (strlen($message) > 1000) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Your message is too long. Please shorten it (max 1000 characters).'
            ]);
            return;
        }

        // Prevent HTML or scripts
        if ($message !== strip_tags($message)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'HTML or script content is not allowed in the message.'
            ]);
            return;
        }

        // Optional: detect spammy content (URLs, etc.)
        if (preg_match('/https?:\/\//i', $message)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Links are not allowed in support messages.'
            ]);
            return;
        }

        $ticketId = uniqid("sp_", );

        $stmtInsert = $this->pdo->prepare("INSERT INTO `support`(`user_id`, `ticket_id`, `name`, `email`, `subject`, `department`, `priority`, `message`, `status`) VALUES (?,?,?,?,?,?,?,?,?)");
        $result = $stmtInsert->execute([$userId, $ticketId, $name, $email, $subject, $department, $priority, $message, "unresolved"]);
    
        if (!$result){
            echo json_encode([
                'status' => 'error',
                'message' => 'We are upgrading our servers. Check back later'
            ]);
        }

        $insertChat = $this->pdo->prepare("INSERT INTO `support_chats`(`ticket_id`, `sender_id`, sender, `reciever_id`, `message`, `status`, `image`, `avatar`) VALUES (?,?,?,?,?,?,?,?)");
        $insertResult = $insertChat->execute([$ticketId, $userId, $name, 'admin', $message, 'not opened', '', $avatar]);

        if (!$insertResult){
            echo json_encode([
                'status' => 'error',
                'message' => 'We are upgrading our servers. Check back later'
            ]);
        }

        $this->sendEmail($message, $email, $name);

        echo json_encode([
            'status' => 'success',
            'message' => 'Your support tick has been opened and active'
        ]);
    }

    public function postSupportReply(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $ticketId = htmlspecialchars($_POST['ticket_id'] ?? '', ENT_QUOTES, 'UTF-8');;
        $message = htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8');
        $image = '';

        if (empty($ticketId) || empty($message)){
            echo json_encode([
                'status' => 'error',
                'message' => 'All field required'
            ]);
            return;
        }

        if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
            $uploadDir = __DIR__ . "../../../../public/uploads/";

            $filename   = time() . "_" . basename($_FILES['image']['name']);
            $targetFile = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $image = $filename;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Image upload failed']);
                return;
            }
        }

        $userStmt = $this->pdo->prepare("SELECT * FROM `users` WHERE user_id = ?");
        $userStmt->execute([$this->userId]);

        if ($userStmt->rowCount() === 0){
            echo json_encode([
                'status' => 'error',
                'message' => 'User not found'
            ]);
            return;
        }

        $userRow = $userStmt->fetch(PDO::FETCH_ASSOC);
        $name = $userRow['name'] ?? 'User';
        $email = $userRow['email'] ?? '';

        $parts = preg_split('/\s+/', trim($name));

        $avatar = strtoupper(
            substr($parts[0], 0, 1) . 
            (isset($parts[1]) ? substr($parts[1], 0, 1) : '')
        );

        $stmtTickets = $this->pdo->prepare("INSERT INTO `support_chats`(`ticket_id`, `sender_id`, `sender`, `reciever_id`, `message`, `status`, `image`, `avatar`) VALUES (?,?,?,?,?,?,?,?)");
        $result = $stmtTickets->execute([$ticketId, $this->userId, $name, 'admin', $message, 'not opened', $image, $avatar]);

        if (!$result){
            echo json_encode([
                'status' => 'error',
                'message' => 'no message found'
            ]);
            return;
        }

        $this->sendEmail($message, $email, $name);

        echo json_encode([
            'status' => 'success',
            'message' => 'message sent'
        ]);
    }

    public function getSupportMessages(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $ticketId = $_GET['ticket_id'] ?? null;

        $stmtTickets = $this->pdo->prepare("SELECT * FROM `support_chats` WHERE ticket_id = ?");
        $result = $stmtTickets->execute([$ticketId]);

        if (!$result){
            echo json_encode([
                'status' => 'error',
                'message' => 'no message found'
            ]);
            return;
        }

        $rows = [];

        if ($stmtTickets->rowCount() > 0){
            $rows = $stmtTickets->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'message' => $rows
            ]);
        }
    }

    public function getTickets(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $status = $_GET['status'] ?? 'unresolved';

        $stmtTickets = $this->pdo->prepare("SELECT * FROM `support` WHERE status = ? AND user_id = ?");
        $result = $stmtTickets->execute([$status, $this->userId]);

        if (!$result){
            echo json_encode([
                'status' => 'success',
                'message' => 'no ticket opened'
            ]);
            return;
        }

        $rows = [];

        if ($stmtTickets->rowCount() > 0){
            $rows = $stmtTickets->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'message' => $rows
            ]);
        }
    }

    public function getUnresolvedTickets(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $ticketId = $_GET['ticket_id'] ?? null;

        $stmtTickets = $this->pdo->prepare("SELECT * FROM `support` WHERE ticket_id = ? AND user_id = ?");
        $result = $stmtTickets->execute([$ticketId, $this->userId]);

        if (!$result){
            echo json_encode([
                'status' => 'success',
                'message' => 'ticket not found'
            ]);
            return;
        }

        $row = $stmtTickets->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'message' => $row
        ]);
    }

    private function sendEmail($message, $email, $name){
         try {
            $this->resend->emails->send([
                'from' => 'IruHost <support@iruhost.com>',
                'to' => [$email, 'osemensilas@gmail.com'],
                'subject' => 'Support Ticket Message',
                'html' => "
                <div style='font-family: Arial, sans-serif; background-color: #f6f8fb; padding: 30px;'>
                <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); padding: 30px;'>
                    
                    <p style='color: #333; line-height: 1.6;'>
                    Your support ticket with the below message has been received. our support team will get back to you as soon as possible.
                    </p>

                    <p style='color:#333; line-height:1.6;'>
                    <em>{$message}</em>
                    </p>

                    <p style='text-align:center; color:#777; font-size:13px; margin-top:30px;'>
                    Thank you for choosing <strong>IruHost</strong>.<br>
                    Need help? Contact us at <a href='mailto:support@iruhost.com' style='color:#007bff;'>support@iruhost.com</a>
                    </p>
                </div>
                </div>
                "
            ]);
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
        }
    }

    public function addNewComment(){

        // if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        //     echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
        //     return;
        // }

        echo json_encode([
            'status' => 'success',
            'message' => 'comment added'
        ]);
    }
}