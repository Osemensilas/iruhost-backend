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
    protected $whmUsername;
    protected $whmApiToken;
    protected $whmHostname;
    protected $encryptionKey;
    protected $encryptionIV;
    protected $resend;
    protected $resendApiCode;
    protected $server;
    protected $whogohostUsername;
    protected $whogohostApi;
    public function __construct(){

        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();

        $this->pdo = DB::connection();
        $this->secretKey = $_ENV['FLUTTERWAVE_SECRETE_KEY'] ?? null;
        $this->userId = $_SESSION['user']['user_id'] ?? null;
        $this->enomUserId = $_ENV['ENOM_USER_ID'];
        $this->enomApiToken = $_ENV['ENOM_USER_API_TOKEN'] ?? null;
        $this->whmUsername = $_ENV['WHM_USERNAME'] ?? null;
        $this->whmApiToken = $_ENV['WHM_API_TOKEN'] ?? null;
        $this->whmHostname = $_ENV['WHM_HOST'] ?? null;
        $this->encryptionKey = hash('sha256', $_ENV['ENCRYPTION_KEY']);
        $this->encryptionIV = substr(hash('sha256', $_ENV['ENCRYPTION_IV']), 0, 16);
        $this->resendApiCode = $_ENV['RESEND_API_KEY'] ?? null;
        $this->resend = Resend::client($this->resendApiCode);
        $this->server = $_ENV['IP'] ?? null;
        $this->whogohostUsername = $_ENV['WHOGOHOST_USERNAME'] ?? null;
        $this->whogohostApi = $_ENV['WHOGOHOST_API'] ?? null;
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

        //https://www.iruhost.com/checkout?status=successful&tx_ref=ref_697dbdd689438&transaction_id=1971804486

        if ($stmt->rowCount() > 0){
            
            foreach($rows as $row){

                $totalPrice += round($row['amount'], 2);
                $content .= rtrim($row['product_name'], ',');

                if ($row['product'] === 'Domain Registration'){
                    $productName = $row['product_name'];
                    $billing = $row['billing'];
                    $cartId = $row['cart_id'];
                    $domain = $row['domain'];

                    $tld = substr($domain, strpos($domain, '.') + 1);

                    $ngTld = ['ng', 'com.ng', 'org.ng', 'gov.ng', 'edu.ng', 'net.ng', 'sch.ng', 'name.ng', 'mobi.ng', 'mil.ng', 'i.ng'];

                    if (in_array($tld, $ngTld)){
                        $domainResponse = $this->regNgDomain($productName, $billing, $cartId, $domain);
                        $ng_domain_status = $domainResponse['status'] ?? 'unknown';
                        $ng_domain_message = $domainResponse['message'] ?? 'unknown';
                    }else{
                        $domainResponse = $this->regDomain($productName, $billing, $cartId, $domain);
                        $domain_status = $domainResponse['status'] ?? 'unknown';
                        $domain_message = $domainResponse['message'] ?? 'unknown';
                    }
                }else{
                    if ($row['product'] === 'SSL Registration'){
                        $productName = $row['product_name'];
                        $billing = $row['billing'];
                        $cartId = $row['cart_id'];
                        $domain = $row['domain'];
                        echo $productName;
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

                            $hostingResponse = $this->regHosting($expiryDate, $productName, $billing, $cartId, $domain);
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
            'web_message' => $web_message,
        ]);
    }

        
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

    private function regNgDomain($productName, $billing, $cartId, $domain){

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

        $endpoint   = "https://whogohost.com/host/modules/addons/DomainsReseller/api/index.php";
        $action     = "/order/domains/register";
        $params     = [
            "domain"    => $domain,
            "regperiod" => "1",
            "nameservers" => [
                "ns1" => "ns1.iruhost.com",
                "ns2" => "ns2.iruhost.com",
            ],
            "contacts"  => [
                    "registrant" => [
                    "firstname" => "Osemen",
                    "lastname" => "Silas",
                    "fullname" => "Osemen Silas",
                    "companyname"  => "Iruap Tech Studio Limited",
                    "email"  => "osemensilas@gmail.com",
                    "address1"  => "FCT Abuja",
                    "address2"  => "",
                    "city"  => "Duste",
                    "state"  => "ABuja",
                    "zipcode"  => "110001",
                    "country"  => "Nigeria",
                    "phonenumber" => "+2349054060454"
                ],
                "tech" => [
                    "firstname" => "Osemen",
                    "lastname" => "Oseobonoite",
                    "fullname" => "Osemen Oseobonoite",
                    "companyname"  => "Iruap Tech Studio Limited",
                    "email"  => "osemensilas@gmail.com",
                    "address1"  => "FCT Abuja",
                    "address2"  => "",
                    "city"  => "Dutse",
                    "state"  => "Abuja",
                    "zipcode"  => "901101",
                    "country"  => "Nigeria",
                    "phonenumber" => "+2349054060454"
                ],
                "billing" => [
                    "firstname" => "Osemen",
                    "lastname" => "Oseobonite",
                    "fullname" => "Osemen Oseobonite",
                    "companyname"  => "Iruap Tech Studio Limited",
                    "email"  => "osemensilas@gmail.com",
                    "address1"  => "FCT Abuja",
                    "address2"  => "",
                    "city"  => "Dutse",
                    "state"  => "Abuja",
                    "zipcode"  => "901101",
                    "country"  => "Nigeria",
                    "phonenumber" => "+2349054060454"
                ],
                "admin" => [
                    "firstname" => "Osemen",
                    "lastname" => "Oseobonoite",
                    "fullname" => "Osemen Oseobonoite",
                    "companyname"  => "Iruap Tech Studio Limited",
                    "email"  => "contact@iruhost.com",
                    "address1"  => "FCT Abuja",
                    "address2"  => "",
                    "city"  => "Dutse",
                    "state"  => "Abuja",
                    "zipcode"  => "901101",
                    "country"  => "Nigeria",
                    "phonenumber" => "+2349054060454"
                ],
            ]
        ];
        $headers = [
            "username: " . $this->whogohostUsername,
            "token: ". base64_encode(hash_hmac("sha256", $this->whogohostApi, "$this->whogohostUsername:".gmdate("y-m-d H")))
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "{$endpoint}{$action}");
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($curl);
        curl_close($curl);

        if (!$response) {
            return [
                'status' => 'error',
                'message' => 'Failed to connect to whogohost API'
            ];
        }

        if ($response !== "success"){
            return [
                'status' => 'error',
                'message' => 'Failed to connect to whogohost API'
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
    
    private function regHosting($expiryDate, $productName, $billing, $cartId, $domain){
    // Start transaction for data consistency
        $this->pdo->beginTransaction();
        
        try {
            $productId = uniqid('prod_');
            $product = 'hosting';
            $text = 'Cpanel';
            $hostingName = $productName;

            // Insert product record
            $stmt = $this->pdo->prepare("INSERT INTO `products`(`user_id`, `product_id`, `product`, `product_name`, `billing`, `domain`, `url`, `text`, `expiry_date`) VALUES (?,?,?,?,?,?,?,?,?)");
            $result = $stmt->execute([$this->userId, $productId, $product, $hostingName, $billing, $domain, "", $text, $expiryDate]);

            if (!$result){
                throw new Exception('Error inserting product to database');
            }

            // Delete from cart
            $stmtDel = $this->pdo->prepare("DELETE FROM `cart` WHERE cart_id = ? AND user_id = ?");
            $delete = $stmtDel->execute([$cartId, $this->userId]);

            if (!$delete){
                throw new Exception('Error deleting from cart');
            }

            // Get user information
            $stmtUser = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmtUser->execute([$this->userId]);
            $userRow = $stmtUser->fetch();

            if (!$userRow) {
                throw new Exception('User not found');
            }

            $userEmail = $userRow['email'];
            $clientName = $userRow['name'];

            // Generate unique cPanel username (max 16 characters)
            $username = substr($domain, 0, strpos($domain, '.') ?: strlen($domain));
            $username = preg_replace('/[^a-zA-Z0-9]/', '', $username); // Remove special chars
            $username = substr($username, 0, 16);

            // Ensure username uniqueness
            $checkStmt = $this->pdo->prepare("SELECT COUNT(*) FROM products WHERE product = 'hosting' AND product_name = ?");
            $checkStmt->execute([$hostingName]);
            if ($checkStmt->fetchColumn() > 0) {
                $username = substr($username, 0, 13) . rand(100, 999);
            }

            // Generate secure password
            $password = $this->generateSecurePassword();

            // Create cPanel account via WHM API
            $apiResponse = $this->createCpanelAccount($username, $domain, $password, $hostingName, $userEmail);

            if (!$apiResponse['success']) {
                throw new Exception($apiResponse['message']);
            }

            // Encrypt password for storage (if needed)
            $encryptedPassword = openssl_encrypt(
                $password, 
                'AES-256-CBC', 
                $this->encryptionKey, 
                0, 
                $this->encryptionIV
            );

            // Insert hosting details
            $stmt = $this->pdo->prepare("INSERT INTO `hosting`(`user_id`, `product_id`, `product`, `product_name`, `billing`, `domain`, `password`, `username`, `expiry_date`) VALUES (?,?,?,?,?,?,?,?,?)");
            $insert = $stmt->execute([
                $this->userId, 
                $productId, 
                $product, 
                $hostingName, 
                $billing, 
                $domain, 
                $encryptedPassword, 
                $username, 
                $expiryDate
            ]);
            
            if (!$insert) {
                throw new Exception('Error inserting hosting details');
            }

            $productStmt = $this->pdo->prepare("UPDATE `products` SET `url`=? WHERE user_id = ? AND product_id = ?");
            $productStmtResult = $productStmt->execute([
                $username,
                $this->userId, 
                $productId
            ]);

            if (!$productStmtResult){
                throw new Exception('Error updating product url');
            }

            // Send email notification
            $this->sendHostingMessage(
                $apiResponse['username'],
                $password,
                $apiResponse['domain'],
                $apiResponse['ip'],
                $userEmail,
                $clientName
            );

            // Commit transaction
            $this->pdo->commit();

            return [
                'status' => 'success',
                'message' => 'Hosting account created successfully',
                'data' => [
                    'username' => $apiResponse['username'],
                    'domain' => $apiResponse['domain'],
                    'url' => ""
                ]
            ];

        } catch (Exception $e) {
            // Rollback on error
            $this->pdo->rollBack();
            
            // Log error for debugging
            error_log("Hosting registration error: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    public function adminRegNgDomain(){

        try{
            $domain = "osemen.name.ng";
            $billing = 'year';
            $productName = "osemen.name.ng";
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

            $endpoint   = "https://whogohost.com/host/modules/addons/DomainsReseller/api/index.php";
            $action     = "/order/domains/register";
            $params     = [
                "domain"    => $domain,
                "regperiod" => "1",
                "nameservers" => [
                    "ns1" => "ns1.iruhost.com",
                    "ns2" => "ns2.iruhost.com",
                ],
                "contacts"  => [
                    "registrant" => [
                    "firstname" => "Osemen",
                    "lastname" => "Silas",
                    "fullname" => "Osemen Silas",
                    "companyname"  => "Iruap Tech Studio Limited",
                    "email"  => "osemensilas@gmail.com",
                    "address1"  => "FCT Abuja",
                    "address2"  => "",
                    "city"  => "Duste",
                    "state"  => "ABuja",
                    "zipcode"  => "110001",
                    "country"  => "NG",
                    "phonenumber" => "+2349054060454"
                ],
                "tech" => [
                    "firstname" => "Osemen",
                    "lastname" => "Oseobonoite",
                    "fullname" => "Osemen Oseobonoite",
                    "companyname"  => "Iruap Tech Studio Limited",
                    "email"  => "osemensilas@gmail.com",
                    "address1"  => "FCT Abuja",
                    "address2"  => "",
                    "city"  => "Dutse",
                    "state"  => "Abuja",
                    "zipcode"  => "901101",
                    "country"  => "NG",
                    "phonenumber" => "+2349054060454"
                ],
                "billing" => [
                    "firstname" => "Osemen",
                    "lastname" => "Oseobonite",
                    "fullname" => "Osemen Oseobonite",
                    "companyname"  => "Iruap Tech Studio Limited",
                    "email"  => "osemensilas@gmail.com",
                    "address1"  => "FCT Abuja",
                    "address2"  => "",
                    "city"  => "Dutse",
                    "state"  => "Abuja",
                    "zipcode"  => "901101",
                    "country"  => "NG",
                    "phonenumber" => "+2349054060454"
                ],
                "admin" => [
                    "firstname" => "Osemen",
                    "lastname" => "Oseobonoite",
                    "fullname" => "Osemen Oseobonoite",
                    "companyname"  => "Iruap Tech Studio Limited",
                    "email"  => "osemensilas@gmail.com",
                    "address1"  => "FCT Abuja",
                    "address2"  => "",
                    "city"  => "Dutse",
                    "state"  => "Abuja",
                    "zipcode"  => "901101",
                    "country"  => "NG",
                    "phonenumber" => "+2349054060454"
                ],
            ]
            ];
            $headers = [
                "username: osemensilas@gmail.com",
                "token: ". base64_encode(hash_hmac("sha256", "sKUcg0MeTqQyVvySlVcuk6Erx1G84Al5", "osemensilas@gmail.com:".gmdate("y-m-d H")))
            ];

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, "{$endpoint}{$action}");
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($curl);
            curl_close($curl);

            if (!$response) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to connect to whogohost API'
                ];
            }

            if ($response !== "success"){
                return [
                    'status' => 'error',
                    'message' => 'Failed to connect to whogohost API'
                ];
            }

            return [
                'status' => 'success',
                'message' => 'Product added to cart and registered on enom'
            ];
        }catch (Exception $e) {
            // Rollback on error
            $this->pdo->rollBack();
            
            // Log error for debugging
            error_log("Hosting registration error: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function adminCreateHosting(){

        try{
        $productId = uniqid('prod_');
        $product = 'hosting';
        $text = 'Cpanel';
        $hostingName = "growth";
        $billing = "month";
        $userId = "iru_6900bb928467e";
        $userEmail = "osemensilas@gmail.com";
        $domain = "osemen.com";
        $password = $this->generateSecurePassword();
        $expiryDate = "2026-11-09 11:55:46";
        $clientName = "Osemen";

        $encryptedPassword = openssl_encrypt($password, 'AES-256-CBC', $this->encryptionKey, 0, $this->encryptionIV);

        $username = substr($domain, 0, strpos($domain, '.') ?: strlen($domain));
        $username = preg_replace('/[^a-zA-Z0-9]/', '', $username); // Remove special chars
        $username = substr($username, 0, 16);

        $checkStmt = $this->pdo->prepare("SELECT COUNT(*) FROM products WHERE product = ? AND product_name = ?");
        $checkStmt->execute(['hosting', $hostingName]);
        if ($checkStmt->fetchColumn() > 0) {
            $username .= rand(100, 999); // make it more unique
        }

        $apiResponse = $this->createCpanelAccount($username, $domain, $password, $hostingName, $userEmail);

        if (!$apiResponse['success']) {
            throw new Exception($apiResponse['message']);
        }

        // Encrypt password for storage (if needed)
        $encryptedPassword = openssl_encrypt(
            $password, 
            'AES-256-CBC', 
            $this->encryptionKey, 
            0, 
            $this->encryptionIV
        );

        // Insert hosting details
        $stmt = $this->pdo->prepare("INSERT INTO `hosting`(`user_id`, `product_id`, `product`, `product_name`, `billing`, `domain`, `password`, `username`, `expiry_date`) VALUES (?,?,?,?,?,?,?,?,?)");
        $insert = $stmt->execute([
            $userId, 
            $productId, 
            $product, 
            $hostingName, 
            $billing, 
            $domain, 
            $encryptedPassword, 
            $username, 
            $expiryDate
        ]);
        
        if (!$insert) {
            throw new Exception('Error inserting hosting details');
        }

        $productStmt = $this->pdo->prepare("UPDATE `products` SET `url`=? WHERE user_id = ? AND product_id = ?");
        $productStmtResult = $productStmt->execute([
            $username,
            $this->userId, 
            $productId
        ]);

        if (!$productStmtResult){
            throw new Exception('Error updating product url');
        }
        
        $this->sendHostingMessage(
            $apiResponse['username'],
            $password,
            $apiResponse['domain'],
            $apiResponse['ip'],
            $userEmail,
            $clientName
        );

        // Commit transaction
        $this->pdo->commit();

        return [
            'status' => 'success',
            'message' => 'Hosting account created successfully',
            'data' => [
                'username' => $apiResponse['username'],
                'domain' => $apiResponse['domain'],
                'url' => ""
            ]
        ];

        } catch (Exception $e) {
            // Rollback on error
            $this->pdo->rollBack();
            
            // Log error for debugging
            error_log("Hosting registration error: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
       
    }

    private function createCpanelAccount($username, $domain, $password, $plan, $email) {
        $api_endpoint = "https://{$this->whmHostname}:2087/json-api/createacct?api.version=1";

        $account_data = [
            'username' => $username,
            'domain' => $domain,
            'password' => $password,
            'plan' => $plan,
            'contactemail' => $email,
        ];

        $curl = curl_init($api_endpoint);

        curl_setopt_array($curl, [
            CURLOPT_SSL_VERIFYHOST => 2, // Set to 2 for production
            CURLOPT_SSL_VERIFYPEER => true, // Set to true for production
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: whm {$this->whmUsername}:{$this->whmApiToken}"
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($account_data),
            CURLOPT_TIMEOUT => 60,
        ]);

        $result = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($result === false) {
            $error = curl_error($curl);
            curl_close($curl);
            
            return [
                'success' => false,
                'message' => 'Unable to reach WHM server: ' . $error
            ];
        }

        curl_close($curl);

        // Log API response for debugging
        $logEntry = date('Y-m-d H:i:s') . " - HTTP {$httpCode} - {$result}\n";
        file_put_contents(__DIR__ . '/whm_log.txt', $logEntry, FILE_APPEND);

        $decoded_result = json_decode($result, true);

        if (!$decoded_result) {
            return [
                'success' => false,
                'message' => 'Invalid JSON response from WHM API'
            ];
        }

        // Check API response
        $reason = $decoded_result['metadata']['reason'] ?? '';
        $raw_output = $decoded_result['metadata']['output']['raw'] ?? '';

        if (stripos($reason, 'Account Creation Ok') === false) {
            return [
                'success' => false,
                'message' => $reason ?: 'Account creation failed: ' . ($raw_output ?: 'Unknown error')
            ];
        }

        // Extract account details from raw output
        preg_match('/UserName:\s*(\S+)/', $raw_output, $usernameMatch);
        preg_match('/PassWord:\s*\(([^)]+)\)/', $raw_output, $passwordMatch);
        preg_match('/Domain:\s*(\S+)/', $raw_output, $domainMatch);

        $usernameCreated = $usernameMatch[1] ?? $username;
        $passwordCreated = $passwordMatch[1] ?? $password;
        $domainCreated = $domainMatch[1] ?? $domain;
        $ipAddress = $decoded_result['data']['ip'] ?? '';

        // Validate critical data was extracted
        if (empty($usernameCreated) || empty($domainCreated)) {
            return [
                'success' => false,
                'message' => 'Failed to extract account details from WHM response'
            ];
        }

        return [
            'success' => true,
            'username' => $usernameCreated,
            'password' => $passwordCreated,
            'domain' => $domainCreated,
            'ip' => $ipAddress
        ];
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
                        <td><a href='https://{$this->whmHostname}:2083' style='color:#007bff; text-decoration:none;'>https://{$this->whmHostname}:2083</a></td>
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
                    <a href='https://{$this->whmHostname}:2083' style='display:inline-block; background-color:#007bff; color:#fff; text-decoration:none; padding:12px 25px; border-radius:6px; font-weight:bold;'>Login to cPanel</a>
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

    
    // private function sendHostingMessage($usernameCreated, $password, $domainCreated, $ipAddress, $userEmail, $clientName){
    //     try {
    //         $mail = new PHPMailer(true);

    //         // Server settings
    //         $mail->isSMTP();
    //         $mail->Host       = 'iruhost.com';
    //         $mail->SMTPAuth   = true;
    //         $mail->Username   = 'noreply@iruhost.com';
    //         $mail->Password   = $_ENV['MAIL_PASSWORD'];  // Store password in .env
    //         $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;  // SSL encryption for port 465
    //         $mail->Port       = 465; // Use 465 for SSL

    //         // Recipients
    //         $mail->setFrom('noreply@iruhost.com', 'IruHost');
    //         $mail->addAddress($userEmail, $clientName);
    //         $mail->addReplyTo('contact@iruhost.com', 'IruHost Support');

    //         // Content
    //         $mail->isHTML(true);
    //         $mail->Subject = 'Your cPanel Account Information';
    //         $mail->Body    = "
    //         <div style='font-family: Arial, sans-serif; background-color: #f6f8fb; padding: 30px;'>
    //         <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); padding: 30px;'>
                
    //             <h2 style='color: #1a1a1a; text-align: center; margin-bottom: 20px;'>Welcome to IruHost</h2>
                
    //             <p style='color: #333;'>Dear <strong>{$clientName}</strong>,</p>
                
    //             <p style='color: #333; line-height: 1.6;'>
    //             Your hosting account for <strong style='color:#0056b3;'>{$domainCreated}</strong> has been successfully set up.
    //             You can now log in to your cPanel to manage your website, emails, and files using the details below:
    //             </p>
                
    //             <table cellpadding='8' cellspacing='0' style='width:100%; border-collapse: collapse; margin: 20px 0;'>
    //             <tr style='background-color:#f0f4ff;'>
    //                 <td style='width:35%; font-weight:bold; color:#333;'>Login URL:</td>
    //                 <td><a href='https://{$this->whmHostname}:2083' style='color:#007bff; text-decoration:none;'>https://{$this->whmHostname}:2083</a></td>
    //             </tr>
    //             <tr>
    //                 <td style='font-weight:bold; color:#333;'>Username:</td>
    //                 <td style='color:#555;'>{$usernameCreated}</td>
    //             </tr>
    //             <tr style='background-color:#f0f4ff;'>
    //                 <td style='font-weight:bold; color:#333;'>Password:</td>
    //                 <td style='color:#555;'>{$password}</td>
    //             </tr>
    //             </table>

    //             <p style='color:#333; line-height:1.6;'>
    //             <em>Note:</em> If you registered a new domain, please allow up to <strong>72 hours</strong> for DNS propagation.
    //             </p>
                
    //             <div style='text-align:center; margin-top:30px;'>
    //             <a href='https://{$this->whmHostname}:2083' style='display:inline-block; background-color:#007bff; color:#fff; text-decoration:none; padding:12px 25px; border-radius:6px; font-weight:bold;'>Login to cPanel</a>
    //             </div>

    //             <p style='text-align:center; color:#777; font-size:13px; margin-top:30px;'>
    //             Thank you for choosing <strong>IruHost</strong>.<br>
    //             Need help? Contact us at <a href='mailto:contact@iruhost.com' style='color:#007bff;'>contact@iruhost.com</a>
    //             </p>
    //         </div>
    //         </div>
    //         ";

    //         // Plain text version for email clients that don't support HTML
    //         $mail->AltBody = "Dear {$clientName},\n\n"
    //                     . "Your hosting account for {$domainCreated} has been successfully set up.\n\n"
    //                     . "Login URL: https://{$this->whmHostname}:2083\n"
    //                     . "Username: {$usernameCreated}\n"
    //                     . "Password: {$password}\n\n"
    //                     . "Note: If you registered a new domain, please allow up to 72 hours for DNS propagation.\n\n"
    //                     . "Thank you for choosing IruHost.\n"
    //                     . "Need help? Contact us at contact@iruhost.com";

    //         $mail->send();
            
    //         // Log success
    //         error_log("Hosting email sent successfully to: {$userEmail}");
            
    //         return true;

    //     } catch (Exception $e) {
    //         // Log the error for debugging
    //         error_log("Email sending failed: {$mail->ErrorInfo}");
            
    //         // Optionally return false or throw exception
    //         return false;
    //     }
    // }
    
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
        } else {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            return;
        }

        $cpanelUser = $productRow['url'];
        $serverHostname = "server.iruhost.com";
        
        // WHM API credentials (store these securely, preferably in environment variables)
        $whmUsername = $this->whmUsername; // Generate this from WHM
        $whmApiToken = $this->whmApiToken; // Generate this from WHM

        // Create auto-login session using WHM API
        $apiUrl = "https://{$serverHostname}:2087/json-api/create_user_session";
        
        $postData = [
            'api.version' => 1,
            'user' => $cpanelUser,
            'service' => 'cpaneld'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: whm {$whmUsername}:{$whmApiToken}"
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Set to true in production with proper SSL

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
            $result = json_decode($response, true);
            
            if (isset($result['data']['url'])) {
                $loginUrl = $result['data']['url'];
                echo json_encode(['success' => true, 'url' => $loginUrl]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to generate login URL']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'API request failed']);
        }
    }
}