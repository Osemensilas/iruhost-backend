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
        $currency = $data['currency'] ?? null;
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
            (`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`, `domain`,`currency`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?,?)");

        try {
            $stmt->execute([$this->userId, $productId, $product, $domainName, $domainPrice, $domainRenew, $billing, $domainName, $currency]);

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
        $domainOp = $data['domainOperation'] ?? null;
        $currency = $data['currency'] ?? null;
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

        if ($domainOp === 'existing'){
            $this->addOld($this->userId, $domainName, $hostingName, $hostingPrice, $hostingRenew, $currency);
            return;
        }
        
        // Prevent duplicate domain for same user
        $stmt = $this->pdo->prepare("SELECT * FROM cart WHERE product_name = ? AND user_id = ?");
        $stmt->execute([$domainName, $this->userId]);

        if ($stmt->fetch()) {
            $this->addAny($this->userId, $domainName, $hostingName, $hostingPrice, $hostingRenew, $currency);
            return;
        }

        $stmt = $this->pdo->prepare("INSERT INTO `cart`
            (`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`, `domain`, `currency`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        try {
            $stmt->execute([$this->userId, $domainId, $domainProduct, $domainName, $domainPrice, $domainRenew, $domainBilling, $domainName, $currency]);

            $this->addAny($this->userId, $domainName, $hostingName, $hostingPrice, $hostingRenew, $currency);
        
        } catch (Exception $err) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Database Error: ' . $err->getMessage()
            ]);
        }
    }

    private function addAny($userId, $domainName, $hostingName, $hostingPrice, $hostingRenew, $currency) {

        $productId = uniqid('hosting_');
        $product = "Hosting Registration";
        $renewPrice = $hostingPrice;

        if ($hostingName === "Lite" && $hostingRenew === "month"){
            $renewPrice = 500;
        }

        if ($hostingName === "Standard" && $hostingRenew === "month"){
            $renewPrice = 850;
        }

        if ($hostingName === "Essential" && $hostingRenew === "month"){
            $renewPrice = 1200;
        }

        if ($hostingName === "Plus" && $hostingRenew === "month"){
            $renewPrice = 1600;
        }
        
        $stmt = $this->pdo->prepare("INSERT INTO `cart`
            (`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`, `domain`, `currency`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        try{
            $stmt->execute([$userId, $productId, $product, $hostingName, $hostingPrice, $renewPrice, $hostingRenew, $domainName, $currency]);
        
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

    private function addOld($userId, $domainName, $hostingName, $hostingPrice, $hostingRenew, $currency) {

        $productId = uniqid('hosting_');
        $product = "Hosting Registration";
        $renewPrice = $hostingPrice;

        if ($hostingName === "Lite" && $hostingRenew === "month"){
            $renewPrice = 500;
        }

        if ($hostingName === "Standard" && $hostingRenew === "month"){
            $renewPrice = 850;
        }

        if ($hostingName === "Essential" && $hostingRenew === "month"){
            $renewPrice = 1200;
        }

        if ($hostingName === "Plus" && $hostingRenew === "month"){
            $renewPrice = 1600;
        }
        
        $stmt = $this->pdo->prepare("INSERT INTO `cart`
            (`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`, `domain`, `currency`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        try{
            $stmt->execute([$userId, $productId, $product, $hostingName, $hostingPrice, $renewPrice, $hostingRenew, $domainName, $currency]);
        
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
        $currency = $data['currency'] ?? null;
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
            (`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`, `domain`, `currency`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        try {
            $stmt->execute([$this->userId, $sslId, $sslProduct, $sslName, $sslPrice, $sslRenew, $sslBilling, $sslDomain, $currency]);
            
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

    public function addEmail(){

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        print_r($data);

        $emailName = $data['package'];
        $currency = "NGN";
        $emailDuration = 1;
        $emailId = uniqid("email_");
        $emailProduct = 'Email Registration';
        $emailBilling = 'year';
        $emailDomain = "";
        $emailPrice = $data['price'] ?? null;
        $emailRenew = $data['price'] ?? null;

        echo json_encode([
            "package" => $emailName,
            "currency" => $currency,
            "duration" => $emailDuration,
            "id" => $emailId,
            "product" => $emailProduct,
            "billing" => $emailBilling,
            "domain" => $emailDomain,
            "price" => $emailPrice,
            "renewPrice" => $emailRenew,
        ]);

        // if (!$emailName || !$emailPrice || !$emailRenew || !$emailDuration) {
        //     echo json_encode(['status' => 'error', 'message' => 'Missing Email details']);
        //     return;
        // }

        // $stmt = $this->pdo->prepare("SELECT * FROM cart WHERE product_name = ? AND user_id = ?");
        // $stmt->execute([$emailName, $this->userId]);

        // if ($stmt->fetch()) {
        //     echo json_encode([
        //         'status' => 'success',
        //         'mesage' => 'Email added to cart'
        //     ]);
        //     return;
        // }

        // $stmt = $this->pdo->prepare("INSERT INTO `cart`
        //     (`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`, `domain`, `currency`)
        //     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // try {
        //     $stmt->execute([$this->userId, $emailId, $emailProduct, $emailName, $emailPrice, $emailRenew, $emailBilling, $emailDomain, $currency]);
            
        //     echo json_encode([
        //         'status' => 'success',
        //         'mesage' => 'Email added to cart'
        //     ]);
        // } catch (Exception $err) {
        //     echo json_encode([
        //         'status' => 'error',
        //         'message' => 'Database Error: ' . $err->getMessage()
        //     ]);
        // }
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
            $currency = "NGN";
            $renew = 0;

            $stmt = $this->pdo->prepare("SELECT * FROM `cart` WHERE domain = ? AND user_id = ?");
            $stmt->execute([$data['website'], $this->userId]);
        
            if (!$stmt->rowCount() > 0){
                $stmt = $this->pdo->prepare("INSERT INTO `cart`(`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`, `domain`, `currency`) VALUES (?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$this->userId, $cartId, 'Web application', 'web app', $website['price'], $renew, '', $data['website'], $currency]);

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

    public function addCustomWebsite(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $cartId = uniqid('prod_');
        $price = $data['websitePrice'];
        $renew = $data['renewPrice'];
        $currency = $data['currency'];

        $stmt = $this->pdo->prepare("INSERT INTO `cart`(`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`, `domain`, `currency`) VALUES (?,?,?,?,?,?,?,?,?)");
        $result = $stmt->execute([$this->userId, $cartId, 'Custom web application', 'web app', $price, $renew, 'year', 'website', $currency]);

        if (!$result){
            echo json_encode([
                'status' => 'error',
                'message' => 'Error adding to cart'
            ]);
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Product added to cart',
            'data' => $data
        ]);
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