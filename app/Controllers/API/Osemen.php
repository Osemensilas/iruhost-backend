<?php

namespace App\Controllers\API;
use App\Core\DB;
use PDO;
require __DIR__ . '/../../../vendor/autoload.php';
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Dotenv\Dotenv;

class Osemen{
    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();
    }

    public function msgSilas(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $message = $data['message'] ?? null;
        $fullName = $data['full_name'] ?? null;
        $email = $data['email'] ?? null;

        if (empty($message) || empty($fullName) || empty($email)) {
            echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
            return;
        }

        $this->sendEmailToSilas($fullName, $email, $message);
    }

    private function sendEmailToSilas($fullName, $email, $message){
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'iruhost.com'; // your SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = 'noreply@iruhost.com'; // SMTP username
            $mail->Password = 'Onion$101Banks';   // SMTP password
            $mail->SMTPSecure = 'ssl'; // or ENCRYPTION_SMTPS
            $mail->Port = 465; // 465 for SSL

            $mail->setFrom('noreply@iruhost.com', 'IruHost');
            $mail->addAddress("osemensilas@gmail.com", $fullName);

            $mail->isHTML(true);
            $mail->Subject = "New Message from portfolio contact form";
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; background-color: #f6f8fb; padding: 30px;'>
                    <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); padding: 30px;'>
                        
                        <h2 style='color: #1a1a1a; text-align: center; margin-bottom: 20px;'>New Message: {$fullName}</h2>
                        
                        <p style='color: #333;'>Email: <strong>{$email}</strong>,</p>
                        
                        <p style='color: #333; line-height: 1.6;'>
                        {$message}
                        </p>
                        
                        <div style='text-align:center; color:#777; font-size:13px; margin-top:30px;'>
                        Thank you for being a valued member of the <strong>IruHost</strong> community.<br>
                        Need help? Contact us at <a href='mailto:support@iruhost.com'>support@iruhost.com</a>
                        <div class='logo' style='margin-top: 20px; height: max-content; width: 100%; display: flex; justify-content: center; align-items: center;'>
                            <img src='https://iruhost.com/logo.png' alt='IruHost Logo' style='display: block; margin: 20px auto; width: 60px; height: 60px; object-fit: contain;'>
                        </div>
                    </div>
                </div>
            ";

            if ($mail->send()){
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Message sent successfully'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Failed to send message'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error', 
                'message' => 'SMTP Mail Error to ' . $email . ': ' . $mail->ErrorInfo
            ]);
        }
    }
}