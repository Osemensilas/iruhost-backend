<?php

namespace App\Controllers\API;
use App\Core\DB;
use PDO;
use Dotenv\Dotenv;
use Resend;
use PDOException;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class CallFlutter {

    protected $pdo;
    protected $secretKey;
    protected $userId;
    protected $enomUserId;
    protected $enomApiToken;
    protected $cpanelUsername;
    protected $cpanelApiToken;
    protected $cpanelHostname;
    protected $encryptionKey;
    protected $encryptionIV;
    protected $resend;
    protected $resendApiCode;
    protected $server;

    public function __construct(){

        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();

        $this->pdo = DB::connection();
        $this->secretKey = $_ENV['FLUTTERWAVE_SECRETE_KEY'] ?? null;
        $this->userId = $_SESSION['user']['user_id'] ?? null;
        $this->enomUserId = $_ENV['ENOM_USER_ID'];
        $this->enomApiToken = $_ENV['ENOM_USER_API_TOKEN'] ?? null;
        $this->cpanelUsername = $_ENV['CPANEL_USERNAME'] ?? null;
        $this->cpanelApiToken = $_ENV['CPANEL_API_TOKEN'] ?? null;
        $this->cpanelHostname = $_ENV['CPANEL_HOST'] ?? null;
        $this->encryptionKey = hash('sha256', $_ENV['ENCRYPTION_KEY']);
        $this->encryptionIV = substr(hash('sha256', $_ENV['ENCRYPTION_IV']), 0, 16);
        $this->resendApiCode = $_ENV['RESEND_API_KEY'] ?? null;
        $this->resend = Resend::client($this->resendApiCode);
        $this->whmApiToken = "iruap";
        $this->server = "37.59.113.132";
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
                        if ($row['product'] === 'Hosting Registration'){
                            $productName = strtolower($row['product_name']);
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
                            //$iruHosting = $this->regIruHost($expiryDate, $url, $productName, $billing, $cartId, $domain);
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

                                        $url = "";
                        
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

    // private function regIruHost($expiryDate, $url, $productName, $billing, $cartId, $domain){
        
    //     $productId = uniqid('prod_');
    //     $product = 'hosting';
    //     $text = 'Ipanel';
    //     $hostingName = "$productName";
    //     $url = "https://iruap-shared-hosting.vercel.app/";

    //     $stmt = $this->pdo->prepare("INSERT INTO `products`(`user_id`, `product_id`, `product`, `product_name`, `billing`, `domain`, `url`, `text`, `expiry_date`) VALUES (?,?,?,?,?,?,?,?,?)");
    //     $result = $stmt->execute([$this->userId, $productId, $product, $hostingName, $billing, $domain, $url, $text, $expiryDate]);

    //     if (!$result){
    //         return [
    //             'status' => 'error',
    //             'message' => 'Error inserting to database'
    //         ];
    //     }

    //     $stmtUser = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    //     $stmtUser->execute([$this->userId]);
    //     $userRow = $stmtUser->fetch();

    //     if (!$userRow) {
    //         return [
    //             'status' => 'error',
    //             'message' => 'User not found'
    //         ];
    //     }

    //     $userEmail = $userRow['email'];
    //     $clientName = $userRow['name'];
    //     $clientId = $userRow['user_id'];

    //     $panelUserId = uniqid("IruPanel_");
    //     $sld = substr($domain, 0, strpos($domain, '.'));
    //     $username = "Iru_" . $sld;

    //     $password = $this->generateSecurePassword();

    //     $encryptedPassword = openssl_encrypt($password, 'AES-256-CBC', $this->encryptionKey, 0, $this->encryptionIV);

    //     $stmtCreatePanelUser = $this->pdo->prepare("INSERT INTO `panel_users`(`user_id`, `username`, `email`, `domain`, `password`, `auto_login`) VALUES (?,?,?,?,?,?)");
    //     $userCreated = $stmtCreatePanelUser->execute([$panelUserId, $username, $userEmail, $domain, $encryptedPassword, '']);

    //     if (!$userCreated){
    //         return [
    //             'status' => 'error',
    //             'message' => 'Error Creating Panel User'
    //         ];
    //     }


    //     $stmtDel = $this->pdo->prepare("DELETE FROM `cart` WHERE cart_id = ? AND user_id = ?");
    //     $delete = $stmtDel->execute([$cartId, $this->userId]);

    //     if (!$delete){
    //         return [
    //             'status' => 'error',
    //             'message' => 'Error deleting from cart'
    //         ];
    //     }

    //     $basePath = __DIR__ . "/../../Services/Iruhost/";
    //     $userFolder = $basePath . $clientId;

    //     if (!is_dir($userFolder)) {
    //         mkdir($userFolder, 0777, true);
    //     }

    //     $publicHtml = $userFolder . "/public_html";

    //     if (!is_dir($publicHtml)) {
    //         mkdir($publicHtml, 0777, true);
    //     }

    //     $defaultIndex = $publicHtml . "/index.html";

    //     if (!file_exists($defaultIndex)) {
    //         $content = "
    //         <!DOCTYPE html>
    //         <html>
    //             <head>
    //                 <meta charset='UTF-8'>
    //                 <title>Welcome to Your Hosting</title>
    //             </head>
    //             <body>
    //                 <h1>Your hosting space is ready 🎉</h1>
    //                 <p>Upload your website files into this folder.</p>
    //             </body>
    //         </html>
    //         ";
    //         file_put_contents($defaultIndex, $content);
    //     }

    //     $databaseName = "db_" . $panelUserId; // e.g., db_user123

    //     $dbUser = "iru_" . substr(md5($panelUserId), 0, 8);
    //     $dbPass = $this->generateSecurePassword();

    //     $encryptedDbPass = openssl_encrypt($dbPass, 'AES-256-CBC', $this->encryptionKey, 0, $this->encryptionIV);

    //     try {
    //         $this->pdo->exec("CREATE USER '$dbUser'@'localhost' IDENTIFIED BY '$dbPass'");
    //         $this->pdo->exec("GRANT ALL PRIVILEGES ON `$databaseName`.* TO '$dbUser'@'localhost'");
    //         $this->pdo->exec("FLUSH PRIVILEGES");

    //         $panelDatabasesStmt = $this->pdo->prepare("INSERT INTO `panel_users_database`(`user_id`, `panel_id`, `database_name`, `db_user`, `db_password`) VALUES (?,?,?,?,?)");
    //         $saveDatabaseToIruhostRecord = $panelDatabasesStmt->execute([$clientId, $panelUserId, $databaseName, $dbUser, $encryptedDbPass]);

    //         if (!$saveDatabaseToIruhostRecord){
    //             return [
    //                 'status' => 'error',
    //                 'message' => 'Error saving database name to iruhost'
    //             ];
    //         }
    //     } catch (PDOException $e) {
    //         try { 
    //             $this->pdo->exec("DROP DATABASE IF EXISTS `$databaseName`"); 
    //         } catch (\Throwable $t) {}
            
    //         return [
    //             'status'=>'error',
    //             'message'=>'Database creation failed: '.$e->getMessage()
    //         ];
    //     }


    //     $autoLogin = uniqid('iru_auto_');
    //     $autoUrl = "https://iruap-shared-hosting.vercel.app?user=$panelUserId&login=$autoLogin";

    //     $stmt = $this->pdo->prepare("UPDATE `products` SET `url`=? WHERE user_id = ? AND product = ?");
    //     $stmt->execute([$autoUrl, $this->userId, 'hosting']);

    //     $stmt = $this->pdo->prepare("UPDATE `panel_users` SET `auto_login`=? WHERE user_id = ? AND username = ?");
    //     $stmt->execute([$autoLogin, $panelUserId, $username]);

    //     echo json_encode([
    //         'status' => 'successful',
    //         'update_message' => 'User update successful',
    //         'create_message' => 'account creation successful'
    //     ]);

    //     $ipAddress = '';

    //     $this->sendHostingMessage($username, $password, $domain, $ipAddress, $userEmail, $clientName);
    // }

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

        if (strpos($domain, '.') === false) {
            return [
                'status' => 'error',
                'message' => 'Invalid domain format'
            ];
        }

        $stmt = $this->pdo->prepare("INSERT INTO `products`(`user_id`, `product_id`, `product`, `product_name`, `billing`, `domain`, `url`, `text`, `expiry_date`) VALUES (?,?,?,?,?,?,?,?,?)");
        $result = $stmt->execute([$this->userId, $productId, $product, $productName, $billing, $domain, $url, $text, $expiryDate]);

        if (!$result){
            return [
                'status' => 'error',
                'message' => 'Domain not added to product rows'
            ];
        }

        $stmt = $this->pdo->prepare("DELETE FROM `cart` WHERE cart_id = ? AND user_id = ?");
        $stmt->execute([$cartId, $this->userId]);

        $tld = substr($domain, strpos($domain, '.') + 1);
        $sld = substr($domain, 0, strpos($domain, '.'));

        $url = "https://reseller.enom.com/interface.asp?command=Purchase&uid=$this->enomUserId&pw=$this->enomApiToken&SLD=$sld&TLD=$tld&responsetype=xml";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            return [
                'status' => 'error',
                'message' => 'Failed to connect to eNom API'
            ];
        }

        $xml = @simplexml_load_string($response);

        if (!$xml) {
            return [
                'status' => 'error',
                'message' => substr($response, 0, 300),
            ];
        }

        $rrpCode = (int) $xml->RRPCode;
        $rrpText = (string) $xml->RRPText;

        if ($rrpCode !== 210) {
            return [
                'status' => 'error',
                'message' => "eNom Error: $rrpText (Code: $rrpCode)"
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Product added to cart and registered on enom'
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
        $result = $stmt->execute([$this->userId, $productId, $product, $hostingName, $billing, $domain, $url, $text, $expiryDate]);

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

        $userEmail = $userRow['email'];
        $clientName = $userRow['name'];

        // Generate unique cPanel username (max 16 characters)
        $username = 'iru' . strtolower(substr(preg_replace('/[^a-zA-Z0-9]/', '', $userRow['name']), 0, 8)) . rand(10, 99);
        $username = substr($username, 0, 16);

        $checkStmt = $this->pdo->prepare("SELECT COUNT(*) FROM products WHERE product = 'hosting' AND product_name = ?");
        $checkStmt->execute([$hostingName]);
        if ($checkStmt->fetchColumn() > 0) {
            $username .= rand(100, 999); // make it more unique
        }

        // Generate secure password
        
        $password = $this->generateSecurePassword();

        $encryptedPassword = openssl_encrypt($password, 'AES-256-CBC', $this->encryptionKey, 0, $this->encryptionIV);


        // WHM API credentials
        $whm_host = $this->cpanelHostname;
        $whm_port = 2087;
        $whm_username = $this->cpanelUsername;
        $whm_token = $this->cpanelApiToken;

        // Create cPanel account via WHM API
        $api_endpoint = "https://cloud.webhostingbliss.com:2087/json-api/createacct?api.version=1";

        // Account details
        $account_data = [
            'username' => $username,
            'domain' => $domain,
            'password' => $password,
            'plan' => $hostingName,
            'contactemail' => $userRow['email'],
        ];

        // Initialize cURL
        $curl = curl_init($api_endpoint);

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Authorization: whm iruhostc:V6DJRXECR4K7IK0ZXV2ELRX8TMF9SVLS"],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($account_data),
            CURLOPT_TIMEOUT => 60,
        ]);

        // Execute request
        $result = curl_exec($curl);

        // Check for cURL errors
        if ($result === false) {
            return [
                'status' => 'error',
                'message' => 'Unable to reach WHM server: ' . curl_error($curl)
            ];
        }

        // Close cURL
        curl_close($curl);

        file_put_contents(__DIR__ . '/whm_log.txt', date('Y-m-d H:i:s') . " - $result\n", FILE_APPEND);


        // Process the API response
        if ($result) {

            $decoded_result = json_decode($result, true);

            // Access WHM metadata
            $reason = $decoded_result['metadata']['reason'] ?? '';
            $raw_output = $decoded_result['metadata']['output']['raw'] ?? '';

            // Extract key values from raw output (regex)
            preg_match('/UserName:\s*(\S+)/', $raw_output, $usernameMatch);
            preg_match('/PassWord:\s*\(([^)]+)\)/', $raw_output, $passwordMatch);
            preg_match('/Domain:\s*(\S+)/', $raw_output, $domainMatch);

            $usernameCreated = $usernameMatch[1] ?? '';
            $passwordCreated = $passwordMatch[1] ?? '';
            $domainCreated   = $domainMatch[1] ?? '';
            $ipAddress       = $decoded_result['data']['ip'] ?? '';

            if (stripos($reason, 'Account Creation Ok') !== false){
                $stmt = $this->pdo->prepare("INSERT INTO `hosting`(`user_id`, `product_id`, `product`, `product_name`, `billing`, `domain`, `password`, `username`, `expiry_date`) VALUES (?,?,?,?,?,?,?,?,?)");
                $insert = $stmt->execute([$this->userId, $productId, $product, $hostingName, $billing, $domain, $encryptedPassword, $username, $expiryDate]);
                
                $autoLoginUrl = $this->createCpanelAutoLoginUrl($username, $domain);

                $url = $autoLoginUrl ?: "https://cloud.webhostingbliss.com:2087/";

                $this->sendHostingMessage($usernameCreated, $password, $domainCreated, $ipAddress, $userEmail, $clientName);

                if ($insert){
                    $stmt = $this->pdo->prepare("UPDATE `products` SET `url`=? WHERE user_id = ? AND product = ?");
                    $stmt->execute([$url, $this->userId, 'hosting']);

                    return [
                        'status' => 'success',
                        'message' => 'Hosting account created successfully'
                    ];
                }
            }else{
                return [
                    'status' => 'success',
                    'message' => "Here is the result: " . json_encode($decoded_result)
                ];
            }
        } else {
            return [
                'status' => 'error',
                'message' => 'Failed to get API response.'
            ];
        }
    }

    public function adminCreateHosting(){

        //T9erTsc671yw

        $productId = uniqid('prod_');
        $product = 'hosting';
        $text = 'Cpanel';
        $hostingName = "iruhostc_growth";
        $billing = "year";
        $userId = "iru_6900bb928467e";
        $userEmail = "osemensilas@gmail.com";
        $domain = "bakar-x.com";
        $password = $this->generateSecurePassword();
        $expiryDate = "2026-11-09 11:55:46";
        $clientName = "Barkar-X";

        $encryptedPassword = openssl_encrypt($password, 'AES-256-CBC', $this->encryptionKey, 0, $this->encryptionIV);

        $username = 'iru' . strtolower(substr(preg_replace('/[^a-zA-Z0-9]/', '', "Bakar-x"), 0, 8)) . rand(10, 99);
        $username = substr($username, 0, 16);

        $checkStmt = $this->pdo->prepare("SELECT COUNT(*) FROM products WHERE product = 'hosting' AND product_name = ?");
        $checkStmt->execute([$hostingName]);
        if ($checkStmt->fetchColumn() > 0) {
            $username .= rand(100, 999); // make it more unique
        }

        // Create cPanel account via WHM API
        $api_endpoint = "https://cloud.webhostingbliss.com:2087/json-api/createacct?api.version=1";

        // Account details
        $account_data = [
            'username' => $username,
            'domain' => $domain,
            'password' => $password,
            'plan' => $hostingName,
            'contactemail' => $userEmail,
        ];

        // Initialize cURL
        $curl = curl_init($api_endpoint);

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Authorization: whm iruhostc:V6DJRXECR4K7IK0ZXV2ELRX8TMF9SVLS"],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($account_data),
            CURLOPT_TIMEOUT => 60,
        ]);

        // Execute request
        $result = curl_exec($curl);

        print_r($result);

        // Check for cURL errors
        if ($result === false) {
            echo json_encode( [
                'status' => 'error',
                'message' => 'Unable to reach WHM server: ' . curl_error($curl)
            ]);
        }

        // Close cURL
        curl_close($curl);

        file_put_contents(__DIR__ . '/whm_log.txt', date('Y-m-d H:i:s') . " - $result\n", FILE_APPEND);


        // Process the API response
        if ($result) {

            $decoded_result = json_decode($result, true);

            // Access WHM metadata
            $reason = $decoded_result['metadata']['reason'] ?? '';
            $raw_output = $decoded_result['metadata']['output']['raw'] ?? '';

            // Extract key values from raw output (regex)
            preg_match('/UserName:\s*(\S+)/', $raw_output, $usernameMatch);
            preg_match('/PassWord:\s*\(([^)]+)\)/', $raw_output, $passwordMatch);
            preg_match('/Domain:\s*(\S+)/', $raw_output, $domainMatch);

            $usernameCreated = $usernameMatch[1] ?? '';
            $passwordCreated = $passwordMatch[1] ?? '';
            $domainCreated   = $domainMatch[1] ?? '';
            $ipAddress       = $decoded_result['data']['ip'] ?? '';

            if (stripos($reason, 'Account Creation Ok') !== false){
                $stmt = $this->pdo->prepare("INSERT INTO `hosting`(`user_id`, `product_id`, `product`, `product_name`, `billing`, `domain`, `password`, `username`, `expiry_date`) VALUES (?,?,?,?,?,?,?,?,?)");
                $insert = $stmt->execute([$userId, $productId, $product, $hostingName, $billing, $domain, $encryptedPassword, $username, $expiryDate]);
                
                $autoLoginUrl = $this->createCpanelAutoLoginUrl($username, $domain);

                $url = $autoLoginUrl ?: "https://cloud.webhostingbliss.com:2087/";

                $this->sendHostingMessage($usernameCreated, $password, $domainCreated, $ipAddress, $userEmail, $clientName);

                if ($insert){
                    $stmt = $this->pdo->prepare("UPDATE `products` SET `url`=? WHERE user_id = ? AND product = ?");
                    $stmt->execute([$url, $userId, 'hosting']);

                    echo json_encode ([
                        'status' => 'success',
                        'message' => 'Hosting account created successfully'
                    ]);
                }
            }else{
                return [
                    'status' => 'success',
                    'message' => "Here is the result: " . json_encode($decoded_result)
                ];
            }
        } else {
            echo json_encode( [
                'status' => 'error',
                'message' => 'Failed to get API response.'
            ]);
        }
    }

    private function createCpanelAutoLoginUrl($username, $domain) {
    // WHM API endpoint for creating user session
        $api_endpoint = "https://{$this->cpanelHostname}:2087/json-api/create_user_session";
        
        $session_data = [
            'api.version' => '1',
            'user' => $username,
            'service' => 'cpaneld'
        ];
        
        $curl = curl_init($api_endpoint);
        
        curl_setopt_array($curl, [
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Authorization: whm iruhostc:V6DJRXECR4K7IK0ZXV2ELRX8TMF9SVLS"],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($session_data),
            CURLOPT_TIMEOUT => 30,
        ]);
        
        $result = curl_exec($curl);
        curl_close($curl);
        
        if ($result) {
            $decoded = json_decode($result, true);
            
            // Extract the session URL
            $sessionUrl = $decoded['data']['url'] ?? '';
            
            if ($sessionUrl) {
                return $sessionUrl;
            }
        }
        
        // Fallback to regular login URL if auto-login fails
        return "https://cloud.webhostingbliss.com:2087/";
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

    private function sendHostingMessage($usernameCreated, $password, $domainCreated, $ipAddress, $userEmail, $clientName){
        try {
            $this->resend->emails->send([
                'from' => 'IruHost <contact@iruhost.com>',
                'to' => [$userEmail],
                'subject' => 'Your cPanel Account Information',
                'html' => "
                <div style='font-family: Arial, sans-serif; background-color: #f6f8fb; padding: 30px;'>
                <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); padding: 30px;'>
                    
                    <h2 style='color: #1a1a1a; text-align: center; margin-bottom: 20px;'>Welcome to IruHost</h2>
                    
                    <p style='color: #333;'>Dear <strong>{$clientName}</strong>,</p>
                    
                    <p style='color: #333; line-height: 1.6;'>
                    Your hosting account for <strong style='color:#0056b3;'>{$domainCreated}</strong> has been successfully set up.
                    You can now log in to your cPanel to manage your website, emails, and files using the details below:
                    </p>
                    
                    <table cellpadding='8' cellspacing='0' style='width:100%; border-collapse: collapse; margin: 20px 0;'>
                    <tr style='background-color:#f0f4ff;'>
                        <td style='width:35%; font-weight:bold; color:#333;'>Login URL:</td>
                        <td><a href='https://{$this->cpanelHostname}:2083' style='color:#007bff; text-decoration:none;'>https://{$this->cpanelHostname}:2083</a></td>
                    </tr>
                    <tr>
                        <td style='font-weight:bold; color:#333;'>Username:</td>
                        <td style='color:#555;'>{$usernameCreated}</td>
                    </tr>
                    <tr style='background-color:#f0f4ff;'>
                        <td style='font-weight:bold; color:#333;'>Password:</td>
                        <td style='color:#555;'>{$password}</td>
                    </tr>
                    </table>

                    <p style='color:#333; line-height:1.6;'>
                    <em>Note:</em> If you registered a new domain, please allow up to <strong>72 hours</strong> for DNS propagation.
                    </p>
                    
                    <div style='text-align:center; margin-top:30px;'>
                    <a href='https://{$this->cpanelHostname}:2083' style='display:inline-block; background-color:#007bff; color:#fff; text-decoration:none; padding:12px 25px; border-radius:6px; font-weight:bold;'>Login to cPanel</a>
                    </div>

                    <p style='text-align:center; color:#777; font-size:13px; margin-top:30px;'>
                    Thank you for choosing <strong>IruHost</strong>.<br>
                    Need help? Contact us at <a href='mailto:contact@iruhost.com' style='color:#007bff;'>contact@iruhost.com</a>
                    </p>
                </div>
                </div>
                "
            ]);


        } catch (\Exception $e) {
            
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

    public function autoCpanelLogin(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $productId = $data['productId'];

        $getProduct = $this->pdo->prepare("SELECT * FROM products WHERE product_id = ?");
        $getProduct->execute([$productId]);

        if ($getProduct->rowCount() > 0){
            $productRow = $getProduct->fetch();
        }

        $cpanelUser = $productRow['url'];
        $whmToken = $this->cpanelApiToken;
        $cpanelServer = $this->server;

        $url = "https://$cpanelServer:2087/json-api/create_user_session?api.version=1&user=$cpanelUser&service=cpaneld";

        $headers = [
            "Authorization: whm root:$whmToken"
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        $apiData = json_decode($response, true);

        if (isset($apiData['data']['url'])) {
            echo json_encode(['success' => true, 'url' => $apiData['data']['url']]);
        } else {
            echo json_encode(['success' => false, 'message' => $response]);
        }
    }
}