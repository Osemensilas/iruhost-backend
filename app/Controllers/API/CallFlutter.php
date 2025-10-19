<?php

namespace App\Controllers\API;
use App\Core\DB;
use PDO;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class CallFlutter {

    protected $pdo;
    protected $secretKey;
    protected $userId;
    protected $clientId;
    protected $nameSiloKey;
    protected $enomUserId;
    protected $enomApiToken;
    protected $cpanelUsername;
    protected $cpanelApiToken;

    public function __construct(){
        $this->pdo = DB::connection();
        $this->secretKey = "FLWSECK_TEST-76fca9105670eb0ded6852bc4785f25b-X";
        $this->userId = $_SESSION['user']['user_id'];
        $this->clientId = "200642152";
        $this->nameSiloKey = "514f12a14ed69fe33b7072ed8"; 
        $this->enomUserId = "osemen";
        $this->enomApiToken = "WMGTAYX54FS4WL4MWVIC4SMSHGCQWTWKTJKUE64R";
        $this->cpanelUsername = "iruhostc";
        $this->cpanelApiToken = "6YK32KTFBWMNM5UFBT1D5OBEZ4S65SV8";
    }

    public function paymentSuccessful(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $stmt = $this->pdo->prepare("SELECT * FROM cart WHERE user_id = ?");
        $stmt->execute([$this->userId]);
        $totalPrice = 0;
        $content = '';

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach($rows as $row){
                $totalPrice += round($row['amount'], 2);
                $content .= rtrim($row['product_name'], ',');
            }
        }

        $paymentId = $data['id'];
        $status = $data['status'];
        $ref = $data['ref'];
        $userId = $this->userId;
        $amount = $totalPrice;
        $details = "Payment for $content";

        $stmt = $this->pdo->prepare("INSERT INTO `transactions`(`user_id`, `transaction_id`, `reference`, `amount`, `details`, `status`) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$userId, $paymentId, $ref, $amount, $details, $status]);

        $domain_status = $hosting_status = $ssl_status = $email_status = $web_status = 'not processed';
        $domain_message = $hosting_message = $ssl_message = $email_message = $web_message = 'not processed';

        if ($stmt->rowCount() > 0){
            foreach($rows as $row){
                if ($row['product'] === 'Domain Registration'){
                    $productName = $row['product_name'];
                    $billing = $row['billing'];
                    $cartId = $row['cart_id'];
                    $domain = $row['domain'];


                    $domainResponse = $this->regDomain($productName, $billing, $cartId, $domain);
                    $domain_status = $domainResponse['status'] ?? 'unknown';
                    $domain_message = $domainResponse['message'] ?? 'unknown';

                    

                    // if ($rrpCode !== "200" || $rrpCode !== "1300"){
                    //     $domain = 'Unsuccessful';

                    //     $this->regDomain($productName, $billing, $cartId, $domain);
                    // }
                }else{
                    if ($row['product'] === 'SSL Registration'){
                        $productName = $row['product_name'];
                        $billing = $row['billing'];
                        $cartId = $row['cart_id'];
                        $domain = $row['domain'];
                        $sslResponse = $this->regSsl($productName, $billing, $cartId, $domain);
                        $ssl_status = $sslResponse['status'] ?? 'unknown';
                        $ssl_message = $sslResponse['message'] ?? 'unknown';
                    }else{
                        if ($row['product_name'] === 'Starter' || $row['product_name'] === 'Growth' || $row['product_name'] === 'Pro' || $row['product_name'] === 'Enterprise'){
                            $productName = $row['product_name'];
                            $billing = $row['billing'];
                            $cartId = $row['cart_id'];
                            $domain = $row['domain'];
                            $url = '/cpanel-login';
                            if ($row['billing'] === 'year'){
                                $expiryDate = date('Y-m-d H:i:s', strtotime('+1 year'));
                            }
                            if ($row['billing'] === 'quarter'){
                                $expiryDate = date('Y-m-d H:i:s', strtotime('+3 months'));
                            }
                            if($row['billing'] === 'month'){
                                $expiryDate = date('Y-m-d H:i:s', strtotime('+1 month'));
                            }

                            $hostingResponse = $this->regHosting($expiryDate, $url, $productName, $billing, $cartId, $domain);
                            $hosting_status = $hostingResponse['status'] ?? 'unknown';
                            $hosting_message = $hostingResponse['message'] ?? 'unknown';
                        } else{
                            if ($row['product'] === 'Email Registration'){
                                $productName = $row['product_name'];
                                $billing = $row['billing'];
                                $cartId = $row['cart_id'];
                                $domain = $row['domain'];
                                $emailResponse = $this->regEmail($productName, $billing, $cartId, $domain);
                                $email_status = $emailResponse['status'] ?? 'unknown';
                                $email_message = $emailResponse['message'] ?? 'unknown';
                            }else{
                                if ($row['product'] === 'Web application'){
                                    $productName = $row['product_name'];
                                    $cartId = $row['cart_id'];
                                    $domain = $row['domain'];
                                    $web_status = $this->webApp($productName, $cartId, $domain);
                                    $webResponse = $webResponse['status'] ?? 'unknown';
                                    $web_message = $webResponse['message'] ?? 'unknown';
                                }else{
                                    if ($row['product'] === 'Domain Transfer'){
                                        $productName = $row['product_name'];
                                        $billing = $row['billing'];
                                        $cartId = $row['cart_id'];
                                        $authCode = $row['domain'];

                                        $url = "https://www.namesilo.com/api/transferDomain?version=1&type=xml&key=$this->nameSiloKey&domain=$productName&auth=$authCode&private=1&auto_renew=0";
                        
                                        $ch = curl_init();
                                        curl_setopt($ch, CURLOPT_URL, $url);
                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                        $response = curl_exec($ch);
                                        curl_close($ch);

                                        $this->regDomain($productName, $billing, $cartId, $authCode);
                                        $domain_status = $domainResponse['status'] ?? 'unknown';
                                        $domain_message = $domainResponse['message'] ?? 'unknown';
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        echo json_encode([
            'status' => 'successful',
            'message' => 'Product added successfully',
            'domain_status' => $domain_status,
            'hosting_status' => $hosting_status,
            'ssl_status' => $ssl_status,
            'email_status' => $email_status,
            'web_status' => $web_status,
            'domain_message' => $domain_message,
            'hosting_message' => $hosting_message,
            'ssl_message' => $ssl_message,
            'email_message' => $email_message,
            'web_message' => $web_message
        ]);
    }

    public function topupSuccessful(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $paymentId = $data['id'];
        $status = $data['status'];
        $ref = $data['ref'];
        $userId = $this->userId;
        $amount = $data['amount'];
        $details = "Payment for account top up.";

        $stmt = $this->pdo->prepare("INSERT INTO `transactions`(`user_id`, `transaction_id`, `reference`, `amount`, `details`, `status`) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$userId, $paymentId, $ref, $amount, $details, $status]);

        $this->topUp($amount);
    }

    private function regDomain($productName, $billing, $cartId, $domain){
        $productId = uniqid('prod_');
        $product = 'domain';
        $text = 'Manage';
        $url = "/manage-domain?domain=$productName";
        $expiryDate = date('Y-m-d H:i:s', strtotime('+1 year'));

        $tdl = substr($domain, strpos($domain, '.') + 1);
        $sld = substr($domain, 0, strpos($domain, '.'));

        $url = "https://reseller.enom.com/interface.asp?command=Purchase&uid=$this->enomUserId&pw=$this->enomApiToken&SLD=$sld&TLD=$tdl&responsetype=xml";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        $xml = simplexml_load_string($response);

        $rrpCode = (int) $xml->RRPCode;

        if ($rrpCode === 210){
            $stmt = $this->pdo->prepare("INSERT INTO `products`(`user_id`, `product_id`, `product`, `product_name`, `billing`, `domain`, `url`, `text`, `expiry_date`) VALUES (?,?,?,?,?,?,?,?,?)");
            $result = $stmt->execute([$this->userId, $productId, $product, $productName, $billing, $domain, $url, $text, $expiryDate]);

            if ($result){
                $stmt = $this->pdo->prepare("DELETE FROM `cart` WHERE cart_id = ? AND user_id = ?");
                $stmt->execute([$cartId, $this->userId]);

                return [
                    'status' => 'success',
                    'message' => 'Domain Created'
                ];
            }
        }else{
            if($rrpCode === 200){
                return [
                    'status' => 'error',
                    'message' => 'Domain not created'
                ];    
            }
                    
            if ($rrpCode === 1300){
                return [
                    'status' => 'error',
                    'message' => 'Domain not created'
                ];
            }
        }
    }

    private function regSsl($productName, $billing, $cartId, $domain){
        $productId = uniqid('prod_');
        $product = 'SSL';
        $text = 'Manage';
        $url = "/manage-ssl?ssl=$productName";
        $expiryDate = date('Y-m-d H:i:s', strtotime('+1 year'));

        $stmt = $this->pdo->prepare("INSERT INTO `products`(`user_id`, `product_id`, `product`, `product_name`, `billing`, `domain`, `url`, `text`, `expiry_date`) VALUES (?,?,?,?,?,?,?,?,?)");
        $result = $stmt->execute([$this->userId, $productId, $product, $productName, $billing, $domain, $url, $text, $expiryDate]);

        if ($result){
            $stmt = $this->pdo->prepare("DELETE FROM `cart` WHERE cart_id = ? AND user_id = ?");
            $stmt->execute([$cartId, $this->userId]);
            
            return [
                'status' => 'success',
                'message' => 'SSL Created'
            ];
        }
    }

    private function regEmail($productName, $billing, $cartId, $domain){
        $productId = uniqid('prod_');
        $product = 'email';
        $text = 'Manage';
        $url = "/manage-email?email=$productName";
        $expiryDate = date('Y-m-d H:i:s', strtotime('+1 year'));

        $stmt = $this->pdo->prepare("INSERT INTO `products`(`user_id`, `product_id`, `product`, `product_name`, `billing`, `domain`, `url`, `text`, `expiry_date`) VALUES (?,?,?,?,?,?,?,?,?)");
        $result = $stmt->execute([$this->userId, $productId, $product, $productName, $billing, $domain, $url, $text, $expiryDate]);

        if ($result){
            $stmt = $this->pdo->prepare("DELETE FROM `cart` WHERE cart_id = ? AND user_id = ?");
            $stmt->execute([$cartId, $this->userId]);

            return [
                'status' => 'success',
                'message' => 'Email Created'
            ];
        }
    }

    private function regHosting($expiryDate, $url, $productName, $billing, $cartId, $domain){
        $productId = uniqid('prod_');
        $product = 'hosting';
        $text = 'Cpanel';
        $hostingName = "iruhostc_$productName";

        $stmt = $this->pdo->prepare("INSERT INTO `products`(`user_id`, `product_id`, `product`, `product_name`, `billing`, `domain`, `url`, `text`, `expiry_date`) VALUES (?,?,?,?,?,?,?,?,?)");
        $result = $stmt->execute([$this->userId, $productId, $product, $productName, $billing, $domain, $url, $text, $expiryDate]);

        if ($result){
            $stmt = $this->pdo->prepare("DELETE FROM `cart` WHERE cart_id = ? AND user_id = ?");
            $stmt->execute([$cartId, $this->userId]);

            // ===============================
            // Your WHM credentials
            // ===============================
            $whm_host = "iruhost.com";  // Your WHM domain (or server IP)

            // ===============================
            // New customer details
            // ===============================
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$this->userId]);

            $rows = $stmt->fetch();

            $username = 'iru' . strtolower(substr(preg_replace('/[^a-zA-Z0-9]/', '', $rows['name']), 0, 5)) . rand(10, 99);

            function generateRandomPassword($length = 16) {
                $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
                $password = '';
                $maxIndex = strlen($characters) - 1;

                for ($i = 0; $i < $length; $i++) {
                    $password .= $characters[random_int(0, $maxIndex)];
                }

                return $password;
            }

            $password = generateRandomPassword();

            
            $url = "https://{$whm_host}:2087/json-api/createacct?api.version=1"
                . "&username={$username}"
                . "&domain={$domain}"
                . "&password=" . urlencode($password)
                . "&contactemail=" . urlencode($rows['email'])
                . "&plan={$hostingName}";

            // ===============================
            // Initialize cURL
            // ===============================
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);  // Disable SSL verify if needed
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: WHM {$this->cpanelUsername}:{$this->cpanelApiToken}"
            ]);

            // ===============================
            // Execute and handle response
            // ===============================
            $result = curl_exec($ch);
            if ($result === false) {
                die("cURL Error: " . curl_error($ch));
            }
            curl_close($ch);

            // Decode the response from JSON
            $response = json_decode($result, true);

            if (isset($response['metadata']['result']) && $response['metadata']['result'] == 1) {
                
                $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host       = 'mail.enom.com'; 
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'contact@iruhost.com'; 
                    $mail->Password   = 'Bank$101Onion'; 
                    $mail->SMTPSecure = 'ssl'; 
                    $mail->Port       = 465;

                    // Email Headers
                    $mail->isHTML(true);
                    $mail->setFrom('contact@iruhost.com', 'IruHost');
                    $mail->addAddress($rows['email']); 
                    $mail->Subject = 'Password Reset';
                    $mail->Body = "
                    <div style='font-family: Arial, sans-serif; color: #333; padding: 20px;'>
                        <p>Dear Osemen Oseobonoite (Iruap Tech Studio Limited),</p>
                        <h2></h2>
                        <p>The cpanel account for {$domain} has been set up. The username and password below are for both cPanel to manage the website at {$domain}.</p>
                        <h2>cPanel Account Information</h2>
                        <p style='font-size: 24px; font-weight: bold;'><strong>Username:</strong> {$username}</p>
                        <p style='font-size: 24px; font-weight: bold;'><strong>Password:</strong> {$password}</p>
                        <a href='https://www.{$domain}:2083'>https://www.{$domain}:2083</a>
                    </div>
                    ";

                    // Send Mail
                    if ($mail->send()) {
                        return [
                            'status' => 'success',
                            'message' => 'Message sent successfully'
                        ];
                    } else {
                        return [
                            'status' => 'error',
                            'message' => 'Message not sent. Check connection'
                        ];
                    }
                } catch (Exception $e) {
                    return [
                        'status' => 'error',
                        'message' => "Email failed: {$mail->ErrorInfo}"
                    ];
                }
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Failed to create hosting account',
                    'reason' => $response['metadata']['reason'] ?? 'Unknown error'
                ];
            }

        }
    }

    private function webApp($productName, $cartId, $domain){
        $productId = uniqid('prod_');
        $product = 'web app';
        $billing = '';
        $text = 'Manage';
        $url = "/manage-web?web=$productName";
        $expiryDate = '';

        $stmt = $this->pdo->prepare("INSERT INTO `products`(`user_id`, `product_id`, `product`, `product_name`, `billing`, `domain`, `url`, `text`, `expiry_date`) VALUES (?,?,?,?,?,?,?,?,?)");
        $result = $stmt->execute([$this->userId, $productId, $product, $productName, $billing, $domain, $url, $text, $expiryDate]);

        if ($result){
            $stmt = $this->pdo->prepare("DELETE FROM `cart` WHERE cart_id = ? AND user_id = ?");
            $stmt->execute([$cartId, $this->userId]);

            return [
                'status' => 'success',
                'message' => 'Web Created'
            ];
        }
    }

    private function topUp($amount){

        $stmt = $this->pdo->prepare("SELECT * FROM `account_balance` WHERE user_id = ?");
        $stmt->execute([$this->userId]);

        if ($stmt->rowCount() > 0){
            $row = $stmt->fetch();

            $newAmount = $row['balance'] + $amount;

            $stmt = $this->pdo->prepare("UPDATE `account_balance` SET `balance`=? WHERE user_id = ?");
            $stmt->execute([$newAmount, $this->userId]);

            echo json_encode([
                'status' => 'successful',
                'message' => 'Top up successful'
            ]);
        }
    }
}