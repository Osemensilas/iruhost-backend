<?php

namespace App\Controllers\API;
use App\Core\DB;
use PDO;
use PHPMailer\PHPMailer\Exception;
use Resend;
use Dotenv\Dotenv;

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
        $stmt->execute([$email, 'support']);

        if ($stmt->rowCount() > 0){
            $row = $stmt->fetch();

            if($row['role'] === 'support'){
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
    }
}