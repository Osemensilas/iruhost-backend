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

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($stmt->rowCount() > 0){

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

        $transactionStmt = $this->pdo->prepare("INSERT INTO `transactions`(`user_id`, `transaction_id`, `reference`, `amount`, `details`, `status`) VALUES (?,?,?,?,?,?)");
        $transactionStmt->execute([$userId, $paymentId, $ref, $amount, $details, $status]);

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
                                    $webResponse = $this->webApp($productName, $cartId, $domain);
                                    $web_status = $webResponse['status'] ?? 'unknown';
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

                                        $domainResponse = $this->regDomain($productName, $billing, $cartId, $authCode);
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

        $stmt = $this->pdo->prepare("DELETE FROM `cart` WHERE cart_id = ? AND user_id = ?");
        $stmt->execute([$cartId, $this->userId]);

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
                return [
                    'status' => 'success',
                    'message' => 'Domain Created'
                ];
            }
        }else{
            return [
                'status' => 'error',
                'message' => 'Domain not created'
            ]; 
        }

        return [
            'status' => 'error',
            'message' => 'Unknown error occurred'
        ];
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

        return [
            'status' => 'error',
            'message' => 'Unknown error occurred'
        ];
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

        return [
            'status' => 'error',
            'message' => 'Unknown error occurred'
        ];
    }

    private function regHosting($expiryDate, $url, $productName, $billing, $cartId, $domain){
        $productId = uniqid('prod_');
        $product = 'hosting';
        $text = 'Cpanel';
        $hostingName = "iruhostc_$productName";

        $stmt = $this->pdo->prepare("INSERT INTO `products`(`user_id`, `product_id`, `product`, `product_name`, `billing`, `domain`, `url`, `text`, `expiry_date`) VALUES (?,?,?,?,?,?,?,?,?)");
        $result = $stmt->execute([$this->userId, $productId, $product, $productName, $billing, $domain, $url, $text, $expiryDate]);

        if (!$result){
            return [
                'status' => 'error',
                'message' => 'Error inserting to database'
            ];
        }

        $stmtDel = $this->pdo->prepare("DELETE FROM `cart` WHERE cart_id = ? AND user_id = ?");
        $delete = $stmtDel->execute([$cartId, $this->userId]);

        if (!$delete){
            return [
                'status' => 'error',
                'message' => 'Error deleting from cart'
            ];
        }

        $stmtUser = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmtUser->execute([$this->userId]);
        $userRow = $stmtUser->fetch();

        if (!$userRow) {
            return [
                'status' => 'error',
                'message' => 'User not found'
            ];
        }

        // Generate unique cPanel username (max 16 characters)
        $username = 'iru' . strtolower(substr(preg_replace('/[^a-zA-Z0-9]/', '', $userRow['name']), 0, 8)) . rand(10, 99);
        
        // Generate secure password
        
        $password = $this->generateSecurePassword();

        // WHM API credentials
        $whm_host = "iruhost.com";
        $whm_username = $this->cpanelUsername;
        $whm_token = $this->cpanelApiToken;

        // Create cPanel account via WHM API
        $api_endpoint = "https://{$whm_host}:2087/json-api/createacct?api.version=1";

        // Account details
        $account_data = [
            'username' => $username,
            'domain' => $domain,
            'password' => $password,
            'plan' => 'iruhostc_' . $productName,
            'contactemail' => $userRow['email'],
        ];

        // Build the query string
        $query_string = http_build_query($account_data);
        $full_api_url = $api_endpoint . '&' . $query_string;

        // Initialize cURL
        $curl = curl_init();

        // Set cURL options
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ["Authorization: whm {$whm_username}:{$whm_token}"]);
        curl_setopt($curl, CURLOPT_URL, $full_api_url);

        $result = curl_exec($curl);

        // Check for cURL errors
        if ($result === false) {
            error_log("cURL error: " . curl_error($curl));
        }

        // Close cURL
        curl_close($curl);

        // Process the API response
        if ($result) {
            $decoded_result = json_decode($result, true);
            // Handle success or error based on the API response
            if (isset($decoded_result['metadata']['result']) && $decoded_result['metadata']['result'] == 1) {
                return [
                    'status' => 'success',
                    'message' => $result
                ];
            } else {
                return [
                    'status' => 'success',
                    'message' => $decoded_result['metadata']['reason'] ?? 'Unknown error'
                ];
            }
        } else {
            return [
                'status' => 'success',
                'message' => 'Failed to get API response.'
            ];
        }
    }

    private function generateSecurePassword($length = 12){
        // Define character sets
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers   = '0123456789';
        $symbols   = '!@#$%^&*()-_=+<>?';

        // Combine all character sets
        $allChars = $uppercase . $lowercase . $numbers . $symbols;

        // Ensure password contains at least one of each type
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];

        // Fill the rest of the password length with random characters
        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        // Shuffle to avoid predictable placement
        $password = str_shuffle($password);

        return $password;
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

        return [
            'status' => 'error',
            'message' => 'Unknown error occurred'
        ];
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