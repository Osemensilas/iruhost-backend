<?php

namespace App\Controllers\API;
use App\Core\DB;
use PDO;
require __DIR__ . '/../../../vendor/autoload.php';
use PHPMailer\PHPMailer\Exception;
use Resend;
use Dotenv\Dotenv;
use PHPMailer\PHPMailer\PHPMailer;

class SupportController{

    protected $adminId;
    protected $pdo;
    protected $resend;
    protected $resendApiCode;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();

        $this->adminId = $_SESSION['admin']['user_id'];
        $this->pdo =  DB::connection();
        $this->resendApiCode = $_ENV['RESEND_API_KEY'] ?? null;
        $this->resend = Resend::client($this->resendApiCode);
    }

    public function supportLogin(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"), true);

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (empty($email) || empty($password)) {
            //http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'All field required'
            ]);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)){
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid email address'
            ]);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$email, 'admin']);

        if ($stmt->rowCount() < 1){

            echo json_encode([
                'status' => 'error',
                'message' => 'You do not have permission'
            ]);
            return;
        }

        $row = $stmt->fetch();

        if($row['permission'] === 'support'){
            if (!password_verify($password, $row['password'])){
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Incorrect password'
                ]);
                return;
            }else{
                $_SESSION['admin'] = [
                    'user_id' => $row['user_id'],
                    'name' => $row['name'],
                    'email' => $email,
                ];

                echo json_encode([
                    'status' => 'success',
                    'message' => 'valid admin'
                ]);
            }
        }else{
            echo json_encode([
                'status' => 'error',
                'message' => 'You do not have permission'
            ]);
            return;
        }
    }

    public function deleteBlog(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['admin'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"), true);

        $blogId = $data['blog_id'];

        $deleteBlog = $this->pdo->prepare("DELETE FROM `blogs` WHERE blog_id = ?");
        $deleteBlog->execute([$blogId]);

         echo json_encode([
            'status' => 'success',
            'message' => 'Delete successful',
        ]);
    }

    public function suportLogout() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['admin'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if ($data['action'] === "logout"){
            session_unset();
            session_destroy();
            echo json_encode([
                'status' => 'success', 
                'message' => 'Logged out'
            ]);
        }
    }

    public function suportDetails(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['admin'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$this->adminId]);

        if ($stmt->rowCount() < 1){
            echo json_encode(['status' => 'error', 'message' => 'User does not exist']);
            return;
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success', 
            'message' => 'User details fetch',
            'name' => $row['name']
        ]);
    }
}