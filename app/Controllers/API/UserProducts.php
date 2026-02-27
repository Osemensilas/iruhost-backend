<?php

namespace App\Controllers\Api;
use Dotenv\Dotenv;
use App\Core\DB;
use PDO;


class UserProducts{

    protected $userId;
    protected $pdo;
    protected $enomUserId;
    protected $enomApiToken;
    protected $whmUsername;
    protected $whmApiToken;
    protected $whmHostname;
    protected $encryptionKey;
    protected $encryptionIV;

    public function __construct(){

        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();

        if (!isset($_ENV['ENOM_USER_ID'])) {
            die("Dotenv failed to load. Path: " . __DIR__ . '/../../../');
        }

        $this->enomUserId = $_ENV['ENOM_USER_ID'] ?? null;
        $this->enomApiToken = $_ENV['ENOM_USER_API_TOKEN'] ?? null;
        $this->userId = $_SESSION['user']['user_id'];
        $this->pdo = DB::connection();
        $this->whmUsername = $_ENV['WHM_USERNAME'] ?? null;
        $this->whmApiToken = $_ENV['WHM_API_TOKEN'] ?? null;
        $this->whmHostname = $_ENV['WHM_HOST'] ?? null;
        $this->encryptionKey = hash('sha256', $_ENV['ENCRYPTION_KEY']);
        $this->encryptionIV = substr(hash('sha256', $_ENV['ENCRYPTION_IV']), 0, 16);
    }

    public function getDashboardProducts(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM `products` WHERE user_id = ?");
        $stmt->execute([$this->userId]);

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll();
        }

        echo json_encode([
            'status' => 'success',
            'user' => $_SESSION['user']['name'],
            'products' => $rows
        ]);
    }

    public function expiringProduct() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        $dollarRateStmt = $this->pdo->prepare("SELECT * FROM `currency` WHERE currency = ?");
        $dollarRateStmt->execute(['naira']);

        $dollarRate = $dollarRateStmt->fetch(PDO::FETCH_ASSOC);

        $dollarValue = $dollarRate['value'];

        $stmt = $this->pdo->prepare("SELECT * FROM `products` WHERE user_id = ?");
        $stmt->execute([$this->userId]);

        $expiring = [];
        $price = 0;

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach($rows as $row){
                $expiryDate = $row['expiry_date'];
                $twoWeeksBefore = date('Y-m-d', strtotime('-2 weeks', strtotime($expiryDate)));
            
                $now = date('Y-m-d');

                if ($row['product'] === "domain"){

                    $domainName = $row['product_name'];

                    $tdl = substr($domainName, strpos($domainName, '.') + 1);
                    $sld = substr($domainName, 0, strpos($domainName, '.'));
                    
                    $ngTld = [
                        'ng',          // root country code
                        'com.ng',      // for commercial entities
                        'org.ng',      // for non-profits
                        'gov.ng',      // for government institutions
                        'edu.ng',      // for accredited educational institutions
                        'net.ng',      // for network providers and ISPs
                        'sch.ng',      // for primary and secondary schools
                        'name.ng',     // for individuals
                        'mobi.ng',     // for mobile services and websites
                        'mil.ng',      // for military institutions
                        'i.ng',        // for personal or individual projects
                    ];

                    if (in_array($tdl, $ngTld)){
                        $getTdlStmt = $this->pdo->prepare("SELECT * FROM `tlds` WHERE tld = ?");
                        $getTdlStmt->execute([$tdl]);

                        if ($getTdlStmt->rowCount() < 1){
                            $price = 0;
                        } else {
                            $tldRow = $getTdlStmt->fetch(PDO::FETCH_ASSOC);
                            $price = $tldRow['renewal'];
                        }

                        $row['renewal_price'] = $price;
                    }else{
                    
                        $getTdlStmt = $this->pdo->prepare("SELECT * FROM `tlds` WHERE tld = ?");
                        $getTdlStmt->execute([$tdl]);

                        if ($getTdlStmt->rowCount() < 1){
                            $price = 0;
                        } else {
                            $tldRow = $getTdlStmt->fetch(PDO::FETCH_ASSOC);
                            $price = $tldRow['renewal'];
                        }

                        $row['renewal_price'] = $price * $dollarValue;
                    }
                }

                if ($row['product'] === "hosting"){


                    if ($row['product_name'] == "lite"){
                        $price = 500;
                    }

                    if ($row['product_name'] == "essential"){
                        $price = 850;
                    }

                    if ($row['product_name'] == "standard"){
                        $price = 1200;
                    }

                    if ($row['product_name'] == "plus"){
                        $price = 1600;
                    }
                
                    if ($row['product_name'] == "starter"){
                        $price = 2400;
                    }

                    if ($row['product_name'] == "growth"){
                        $price = 3500;
                    }

                    if ($row['product_name'] == "pro"){
                        $price = 5000;
                    }

                    if ($row['product_name'] == "enterprise"){
                        $price = 9000;
                    }

                    $row['renewal_price'] = $price;
                }

                if ($now >= $twoWeeksBefore) {
                    $expiring[] = $row;
                }
            }

            echo json_encode([
                'status' => 'success',
                'user' => $_SESSION['user']['name'],
                'products' => $expiring,
                'total_price' => array_sum(array_column($expiring, 'renewal_price')),
            ]);
        }
    }

    public function domainList(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM `products` WHERE user_id = ? AND product = ?");
        $stmt->execute([$this->userId, 'domain']);

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll();
        }

        echo json_encode([
            'status' => 'success',
            'user' => $_SESSION['user']['name'],
            'products' => $rows
        ]);
    }

    public function hostingList(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM `products` WHERE user_id = ? AND product = ?");
        $stmt->execute([$this->userId, 'hosting']);

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll();
        }

        echo json_encode([
            'status' => 'success',
            'user' => $_SESSION['user']['name'],
            'products' => $rows
        ]);
    }

    public function emailList(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM `products` WHERE user_id = ? AND product = ?");
        $stmt->execute([$this->userId, 'email']);

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll();

            echo json_encode([
                'status' => 'success',
                'user' => $_SESSION['user']['name'],
                'products' => $rows
            ]);
        }
    }

    public function sslList(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM `products` WHERE user_id = ? AND product = ?");
        $stmt->execute([$this->userId, 'ssl']);

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll();

            echo json_encode([
                'status' => 'success',
                'user' => $_SESSION['user']['name'],
                'products' => $rows
            ]);
        }
    }

    public function appList() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM `products` WHERE user_id = ? AND product = ?");
        $stmt->execute([$this->userId, 'web app']);

        if ($stmt->rowCount() > 0){

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'user' => $_SESSION['user']['name'],
                'products' => $rows
            ]);
        }
    }

    public function getIruapDomain(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        // Fetch all domains
        $stmt = $this->pdo->prepare("SELECT domain FROM `products` WHERE user_id = ? AND product = ?");
        $stmt->execute([$this->userId, 'domain']);
        $domainRows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!$domainRows) {
            echo json_encode(['status' => 'error', 'message' => 'No domains found']);
            return;
        }

        // Fetch all hosting domains
        $stmt = $this->pdo->prepare("SELECT domain FROM `products` WHERE user_id = ? AND product = ?");
        $stmt->execute([$this->userId, 'hosting']);
        $hostingRows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $validDomains = [];

        foreach ($domainRows as $iruapDomain) {
            // Check if domain is not in hosting list and is valid
            if (!in_array($iruapDomain, $hostingRows, true) &&
                preg_match('/^(?!-)[A-Za-z0-9-]{1,63}(?<!-)(\.[A-Za-z]{2,})+$/', $iruapDomain)) {
                
                $validDomains[] = $iruapDomain;
            }
        }

        if ($validDomains) {
            echo json_encode([
                'status' => 'success',
                'domains' => $validDomains
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No valid domains found'
            ]);
        }
    }

    public function getDomainProducts(){

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $domain = $data['domain'];

        $stmt = $this->pdo->prepare("
            SELECT * FROM `products` 
            WHERE user_id = ? 
            AND domain = ? 
            AND product_name != ?
        ");
        $stmt->execute([$this->userId, $domain, $domain]);

        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($products)) {
            echo json_encode(['status' => 'error', 'message' => 'No products found']);
            return;
        }

        echo json_encode([
            'status' => 'success',
            'user' => $_SESSION['user']['name'],
            'products' => $products
        ]);
    }

    public function verifyRenewal(){

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        $dollarRateStmt = $this->pdo->prepare("SELECT * FROM `currency` WHERE currency = ?");
        $dollarRateStmt->execute(['naira']);

        $dollarRate = $dollarRateStmt->fetch(PDO::FETCH_ASSOC);

        $dollarValue = $dollarRate['value'];

        $productId = $_GET['product_id'];
        $amout = $_GET['amount'];
        $transactionId = $_GET['transaction_id'];
        $txRef = $_GET['tx_ref'];

        if ($productId == "all"){
            $stmt = $this->pdo->prepare("SELECT * FROM `products` WHERE user_id = ?");
            $stmt->execute([$this->userId]);

            if ($stmt->rowCount() > 0){
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            foreach($rows as $row){
                $expiryDate = $row['expiry_date'];
                $twoWeeksBefore = date('Y-m-d', strtotime('-2 weeks', strtotime($expiryDate)));
        
                $now = date('Y-m-d');

                if ($now >= $twoWeeksBefore) {
                    
                    if ($row['product'] === "domain"){
                        $domainName = $row['product_name'];

                        $tdl = substr($domainName, strpos($domainName, '.') + 1);
                        
                        $ngTld = [
                            'ng',          // root country code
                            'com.ng',      // for commercial entities
                            'org.ng',      // for non-profits
                            'gov.ng',      // for government institutions
                            'edu.ng',      // for accredited educational institutions
                            'net.ng',      // for network providers and ISPs
                            'sch.ng',      // for primary and secondary schools
                            'name.ng',     // for individuals
                            'mobi.ng',     // for mobile services and websites
                            'mil.ng',      // for military institutions
                            'i.ng',        // for personal or individual projects
                        ];

                        if (in_array($tdl, $ngTld)){
                            $getTdlStmt = $this->pdo->prepare("SELECT * FROM `tlds` WHERE tld = ?");
                            $getTdlStmt->execute([$tdl]);

                            if ($getTdlStmt->rowCount() < 1){
                                $price = 0;
                            } else {
                                $tldRow = $getTdlStmt->fetch(PDO::FETCH_ASSOC);
                                $price = $tldRow['renewal'];
                            }
                        }else{
                        
                            $getTdlStmt = $this->pdo->prepare("SELECT * FROM `tlds` WHERE tld = ?");
                            $getTdlStmt->execute([$tdl]);

                            if ($getTdlStmt->rowCount() < 1){
                                $price = 0;
                            } else {
                                $tldRow = $getTdlStmt->fetch(PDO::FETCH_ASSOC);
                                $price = $tldRow['renewal'];
                            }
                        }

                        if ($row['billing'] === "month") {
                            $newExpiry = date('Y-m-d', strtotime('+1 month', strtotime($row['expiry_date'])));
                        } elseif ($row['billing'] === "quarter") {
                            $newExpiry = date('Y-m-d', strtotime('+3 months', strtotime($row['expiry_date'])));
                        } elseif ($row['billing'] === "year") {
                            $newExpiry = date('Y-m-d', strtotime('+1 year', strtotime($row['expiry_date'])));
                        }


                        $stmtUpdate = $this->pdo->prepare("UPDATE `products` SET `expiry_date` = ? WHERE product_name = ?");
                        $result = $stmtUpdate->execute([$newExpiry, $domainName]);

                        $transaction = $this->pdo->prepare("INSERT INTO `transactions`(`user_id`, `transaction_id`, `reference`, `product`, `product_name`, `amount`, `details`, `status`) VALUES (?,?,?,?,?,?,?,?)");
                        $transaction->execute([
                            $this->userId,
                            $transactionId,
                            $txRef,
                            'domain',
                            $domainName,
                            $price,
                            "renewal of $domainName",
                            'success'
                        ]);

                        if (!$result) {
                            echo json_encode(['status' => 'error', 'message' => 'Failed to update expiry date for ' . $domainName]);
                            return;
                        }
                    }

                    if ($row['product'] === "hosting"){
                        $hostingName = $row['product_name'];
                        
                        if ($hostingName == "lite"){
                            $price = 500;
                        }

                        if ($hostingName == "essential"){
                            $price = 850;
                        }

                        if ($hostingName == "standard"){
                            $price = 1200;
                        }

                        if ($hostingName == "plus"){
                            $price = 1600;
                        }

                        if ($hostingName == "starter"){
                            $price = 2400;
                        }

                        if ($hostingName == "growth"){
                            $price = 3500;
                        }

                        if ($hostingName == "pro"){
                            $price = 5000;
                        }

                        if ($hostingName == "enterprise"){
                            $price = 9000;
                        }

                        if ($row['billing'] === "month") {
                            $newExpiry = date('Y-m-d', strtotime('+1 month', strtotime($row['expiry_date'])));
                        } elseif ($row['billing'] === "quarter") {
                            $newExpiry = date('Y-m-d', strtotime('+3 months', strtotime($row['expiry_date'])));
                        } elseif ($row['billing'] === "year") {
                            $newExpiry = date('Y-m-d', strtotime('+1 year', strtotime($row['expiry_date'])));
                        }

                        $stmtUpdate = $this->pdo->prepare("UPDATE `products` SET `expiry_date` = ? WHERE product_name = ?");
                        $result = $stmtUpdate->execute([$newExpiry, $hostingName]);

                        $transaction = $this->pdo->prepare("INSERT INTO `transactions`(`user_id`, `transaction_id`, `reference`, `product`, `product_name`, `amount`, `details`, `status`) VALUES (?,?,?,?,?,?,?,?)");
                        $transaction->execute([
                            $this->userId,
                            $transactionId,
                            $txRef,
                            'hosting',
                            $hostingName,
                            $price,
                            "renewal of $hostingName",
                            'success'
                        ]);

                        if (!$result) {
                            echo json_encode(['status' => 'error', 'message' => 'Failed to update expiry date for ' . $hostingName]);
                            return;
                        }
                    }
                }
            }

            echo json_encode([
                'status' => 'success',
                'message' => "Payment verified and renewal processed successfully"
            ]);

            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM `products` WHERE product_id = ?");
        $stmt->execute([$productId]);

        if ($stmt->rowCount() > 0){
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row['product'] === "domain"){
                $domainName = $row['product_name'];

                $tdl = substr($domainName, strpos($domainName, '.') + 1);
                
                $ngTld = [
                    'ng',          // root country code
                    'com.ng',      // for commercial entities
                    'org.ng',      // for non-profits
                    'gov.ng',      // for government institutions
                    'edu.ng',      // for accredited educational institutions
                    'net.ng',      // for network providers and ISPs
                    'sch.ng',      // for primary and secondary schools
                    'name.ng',     // for individuals
                    'mobi.ng',     // for mobile services and websites
                    'mil.ng',      // for military institutions
                    'i.ng',        // for personal or individual projects
                ];

                if (in_array($tdl, $ngTld)){
                    $getTdlStmt = $this->pdo->prepare("SELECT * FROM `tlds` WHERE tld = ?");
                    $getTdlStmt->execute([$tdl]);

                    if ($getTdlStmt->rowCount() < 1){
                        $price = 0;
                    } else {
                        $tldRow = $getTdlStmt->fetch(PDO::FETCH_ASSOC);
                        $price = $tldRow['renewal'];
                    }
                }else{
                
                    $getTdlStmt = $this->pdo->prepare("SELECT * FROM `tlds` WHERE tld = ?");
                    $getTdlStmt->execute([$tdl]);

                    if ($getTdlStmt->rowCount() < 1){
                        $price = 0;
                    } else {
                        $tldRow = $getTdlStmt->fetch(PDO::FETCH_ASSOC);
                        $price = $tldRow['renewal'];
                    }
                }

                if ($row['billing'] === "month") {
                    $newExpiry = date('Y-m-d', strtotime('+1 month', strtotime($row['expiry_date'])));
                } elseif ($row['billing'] === "quarter") {
                    $newExpiry = date('Y-m-d', strtotime('+3 months', strtotime($row['expiry_date'])));
                } elseif ($row['billing'] === "year") {
                    $newExpiry = date('Y-m-d', strtotime('+1 year', strtotime($row['expiry_date'])));
                }

                $stmtUpdate = $this->pdo->prepare("UPDATE `products` SET `expiry_date` = ? WHERE product_id = ?");
                $result = $stmtUpdate->execute([$newExpiry, $row['product_id']]);

                $transaction = $this->pdo->prepare("INSERT INTO `transactions`(`user_id`, `transaction_id`, `reference`, `product`, `product_name`, `amount`, `details`, `status`) VALUES (?,?,?,?,?,?,?,?)");
                $transaction->execute([
                    $this->userId,
                    $transactionId,
                    $txRef,
                    'domain',
                    $domainName,
                    $price,
                    "renewal of $domainName",
                    'success'
                ]);

                if (!$result) {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to update expiry date for ' . $domainName]);
                    return;
                }
            }

            if ($row['product'] === "hosting"){
                $hostingName = $row['product_name'];
                
                if ($hostingName == "lite"){
                    $price = 500;
                }

                if ($hostingName == "essential"){
                    $price = 850;
                }

                if ($hostingName == "standard"){
                    $price = 1200;
                }

                if ($hostingName == "plus"){
                    $price = 1600;
                }

                if ($hostingName == "starter"){
                    $price = 2400;
                }

                if ($hostingName == "growth"){
                    $price = 3500;
                }

                if ($hostingName == "pro"){
                    $price = 5000;
                }

                if ($hostingName == "enterprise"){
                    $price = 9000;
                }

                if ($row['billing'] === "month") {
                    $newExpiry = date('Y-m-d', strtotime('+1 month', strtotime($row['expiry_date'])));
                } elseif ($row['billing'] === "quarter") {
                    $newExpiry = date('Y-m-d', strtotime('+3 months', strtotime($row['expiry_date'])));
                } elseif ($row['billing'] === "year") {
                    $newExpiry = date('Y-m-d', strtotime('+1 year', strtotime($row['expiry_date'])));
                }

                $stmtUpdate = $this->pdo->prepare("UPDATE `products` SET `expiry_date` = ? WHERE product_id = ?");
                $result = $stmtUpdate->execute([$newExpiry, $row['product_id']]);

                $transaction = $this->pdo->prepare("INSERT INTO `transactions`(`user_id`, `transaction_id`, `reference`, `product`, `product_name`, `amount`, `details`, `status`) VALUES (?,?,?,?,?,?,?,?)");
                $transaction->execute([
                    $this->userId,
                    $transactionId,
                    $txRef,
                    'hosting',
                    $hostingName,
                    $price,
                    "renewal of $hostingName",
                    'success'
                ]);

                if (!$result) {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to update expiry date for ' . $domainName]);
                    return;
                }
            }
        }

        echo json_encode([
            'status' => 'success',
            'message' => "Payment verified and renewal processed successfully"
        ]);
    }

    public function createCpanelEmail() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user'])){
            echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $domain = $data['domain'];
        $username = $data['username'];
        $password = $data['password'];
        $encryptedPassword = openssl_encrypt(
                $password, 
                'AES-256-CBC', 
                $this->encryptionKey, 
                0, 
                $this->encryptionIV
            );

        if (empty($domain) || empty($username) || empty($password)){
            echo json_encode([
                'status' => 'error',
                'message' => 'All Fields Required'
            ]);
            return;
        }

        $emailResponse = $this->createMail($domain, $username, $password);
        $email_status = $emailResponse['status'] ?? 'unknown';
        $email_message = $emailResponse['message'] ?? 'unknown';

        if ($email_status === "success"){
            $mailToDb = $this->pdo->prepare("INSERT INTO cpanel_emails (user_id, email_id, username, domain, password) VALUES (?,?,?,?,?)");
            $result = $mailToDb->execute([$this->userId, uniqid(), $username, $domain, $encryptedPassword]);

            if (!$result){
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Could not create at this time'
                ]);
                return;
            }

            echo json_encode([
                'status' => 'success',
                'domain' => $domain,
                'username' => $username,
                'password' => $password,
                'message' => 'Email created'
            ]);
        }
    }

    private function createMail($domain, $username, $password){
        
        $cpanelUser = $this->whmUsername;
        $apiToken   = $this->whmApiToken;

        $url = "https://{$this->whmHostname}:2087/json-api/cpanel";

        $data = [
            "cpanel_jsonapi_user"    => $cpanelUser,  // the cPanel account username
            "cpanel_jsonapi_module"  => "Email",
            "cpanel_jsonapi_func"    => "add_pop",
            "cpanel_jsonapi_apiversion" => 2,
            "email"    => $username,
            "domain"   => $domain,
            "password" => $password,
            "quota"    => 1024
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url . "?" . http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: whm root:{$apiToken}"  // WHM root token
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        echo $response;

        if ($response === "success"){
            return [
                'status' => 'success',
                'message' => 'email created'
            ];
        }else{
            echo json_encode([
                'status' => 'error',
                'message' => 'could not create at this time'
            ]);
        }
    }
}