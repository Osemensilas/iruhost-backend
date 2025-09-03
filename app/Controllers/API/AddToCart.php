<?php

namespace App\Controllers\API;
use App\Core\DB;
use Exception;

class AddToCart {

    private $pdo;
    private $userId;
    private $myKey;

    public function __construct() {
        $this->pdo = DB::connection();
        $this->myKey = "3079601359d46e924bfbab85"; 
        $this->userId = $_SESSION['user']['user_id'] ?? $_SESSION['guest']['id'] ?? null;
    }

    public function addDomain() {
       
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $domainName = $data['cartDomainName'] ?? null;
        $domainPrice = $data['cartDomainPrice'] ?? null;
        $domainRenew = $data['cartDomainRenew'] ?? null;
        $domainDuration = $data['cartDomainDuration'] ?? null;
        $product = 'Domain Registration';
        $billing = 'year';
        $productId = uniqid("domain_");

        if (!$domainName || !$domainPrice || !$domainRenew || !$domainDuration) {
            echo json_encode(['status' => 'error', 'message' => 'Missing domain details']);
            return;
        }

        // Prevent duplicate domain for same user
        $stmt = $this->pdo->prepare("SELECT * FROM cart WHERE product_name = ? AND user_id = ?");
        $stmt->execute([$domainName, $this->userId]);

        if ($stmt->fetch()) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Domain already exists in your cart'
            ]);
            return;
        }

        $stmt = $this->pdo->prepare("INSERT INTO `cart`
            (`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`, `domain`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        try {
            $stmt->execute([$this->userId, $productId, $product, $domainName, $domainPrice, $domainRenew, $billing, $domainName]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Domain added to cart'
            ]);
        } catch (Exception $err) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Database Error: ' . $err->getMessage()
            ]);
        }
    }

    public function addHosting(){
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $domainName = $data['domainName'] ?? null;
        $domainPrice = $data['domainPrice'] ?? null;
        $domainRenew = $data['domainRenew'] ?? null;
        $domainDuration = 1;
        $domainId = uniqid("domain_");
        $domainProduct = 'Domain Registration';
        $domainBilling = 'year';

        $hostingName = $data['hosting'] ?? null;
        $hostingPrice = $data['hostingPrice'] ?? null;
        $hostingRenew = $data['billing'] ?? null;

        if (!$domainName || !$domainPrice || !$domainRenew || !$domainDuration) {
            echo json_encode(['status' => 'error', 'message' => 'Missing domain details']);
            return;
        }

        if ($domainName == '-'){
            echo json_encode(['status' => 'error', 'message' => 'Missing domain details']);
            return;
        }

        
        // Prevent duplicate domain for same user
        $stmt = $this->pdo->prepare("SELECT * FROM cart WHERE product_name = ? AND user_id = ?");
        $stmt->execute([$domainName, $this->userId]);

        if ($stmt->fetch()) {
            $this->addAny($this->userId, $domainName, $hostingName, $hostingPrice, $hostingRenew);
            return;
        }

        $stmt = $this->pdo->prepare("INSERT INTO `cart`
            (`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`, `domain`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        try {
            $stmt->execute([$this->userId, $domainId, $domainProduct, $domainName, $domainPrice, $domainRenew, $domainBilling, $domainName]);

            $this->addAny($this->userId, $domainName, $hostingName, $hostingPrice, $hostingRenew);
        
            
        } catch (Exception $err) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Database Error: ' . $err->getMessage()
            ]);
        }
    }

    private function addAny($userId, $domainName, $hostingName, $hostingPrice, $hostingRenew) {

        $productId = uniqid('hosting_');
        $product = "Hosting Registration";
        
        $stmt = $this->pdo->prepare("INSERT INTO `cart`
            (`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`, `domain`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        try{
            $stmt->execute([$userId, $productId, $product, $hostingName, $hostingPrice, $hostingPrice, $hostingRenew, $domainName]);
        
            echo json_encode([
                'status' => 'success',
                'message' => 'Hosting added to cart'
            ]);
        }catch(Exception $err){
            echo json_encode([
                'status' => 'error',
                'message' => 'Database Error: ' . $err->getMessage()
            ]);
        }
    }

    public function addSSL(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $sslName = $data['product_name'] ?? null;
        $sslPrice = $data['price'] ?? null;
        $sslRenew = $data['price'] ?? null;
        $sslDuration = 1;
        $sslId = uniqid("ssl_");
        $sslProduct = 'SSL Registration';
        $sslBilling = 'year';
        $sslDomain = '';

        if (!$sslName || !$sslPrice || !$sslRenew || !$sslDuration) {
            echo json_encode(['status' => 'error', 'message' => 'Missing SSL details']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM cart WHERE product_name = ? AND user_id = ?");
        $stmt->execute([$sslName, $this->userId]);

        if ($stmt->fetch()) {
            echo json_encode([
                'status' => 'success',
                'mesage' => 'SSL added to cart'
            ]);
            return;
        }

        $stmt = $this->pdo->prepare("INSERT INTO `cart`
            (`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`, `domain`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        try {
            $stmt->execute([$this->userId, $sslId, $sslProduct, $sslName, $sslPrice, $sslRenew, $sslBilling, $sslDomain]);
            
            echo json_encode([
                'status' => 'success',
                'mesage' => 'SSL added to cart'
            ]);
        } catch (Exception $err) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Database Error: ' . $err->getMessage()
            ]);
        }
    }

    public function addWebsite(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $stmt = $this->pdo->prepare("SELECT * FROM `websites` WHERE web_id = ?");
        $stmt->execute([$data['website']]);

        if ($stmt->rowCount() > 0){
            $website = $stmt->fetch();

            $cartId = uniqid('prod_');

            $stmt = $this->pdo->prepare("SELECT * FROM `cart` WHERE domain = ? AND user_id = ?");
            $stmt->execute([$data['website'], $this->userId]);
        
            if (!$stmt->rowCount() > 0){
                $stmt = $this->pdo->prepare("INSERT INTO `cart`(`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`, `domain`) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->execute([$this->userId, $cartId, 'Web application', 'web app', $website['price'], '', '', $data['website']]);

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Product added to cart'
                ]);
            }else{
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Product already in cart'
                ]);
            }
        }
    }

    public function tranferDomain(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $domainName = $data['action'] ?? null;
        $auth = $data['auth'] ?? null;
        $product = 'Domain Transfer';
        $billing = 'year';
        $productId = uniqid("domain_");

        $tdl = substr($domainName, strpos($domainName, '.') + 1);
        $sld = substr($domainName, 0, strpos($domainName, '.'));

        $api = "https://www.namesilo.com/api/getPrices?version=1&type=xml&key=$this->myKey";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        $xml = simplexml_load_string($response);

        $tldNode = $xml->reply->$tdl;
        $currency = 'naira';

        $stmt = $this->pdo->prepare("SELECT * FROM currency WHERE currency = ?");
        $stmt->execute([$currency]);

        if ($stmt->rowCount() > 0){
            $row = $stmt->fetch();
        }

        $domainPrice = isset($tldNode->transfer, $row['value']) 
            ? number_format($tldNode->transfer * $row['value'], 2, '.', '') 
            : null;

        $domainRenewal = isset($tldNode->renew, $row['value']) 
            ? number_format($tldNode->renew * $row['value'], 2, '.', '') 
            : null;
        
        $stmt = $this->pdo->prepare("INSERT INTO `cart`(`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`, `domain`) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$this->userId, $productId, $product, $domainName, $domainPrice, $domainRenewal, $billing, $auth]);


        echo json_encode([
            'status' => 'successful',
            'message' => 'Added to cart',
        ]);
    }
}