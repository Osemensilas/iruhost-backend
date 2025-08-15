<?php

namespace App\Controllers\API;
use App\Core\DB;
use Exception;

class AddToCart {

    private $pdo;
    private $userId;

    public function __construct() {
        $this->pdo = DB::connection();
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
            (`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`)
            VALUES (?, ?, ?, ?, ?, ?, ?)");

        try {
            $stmt->execute([$this->userId, $productId, $product, $domainName, $domainPrice, $domainRenew, $billing]);

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
            (`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`)
            VALUES (?, ?, ?, ?, ?, ?, ?)");

        try {
            $stmt->execute([$this->userId, $domainId, $domainProduct, $domainName, $domainPrice, $domainRenew, $domainBilling]);

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
        $product = "Hosting for $domainName";

        
        $stmt = $this->pdo->prepare("INSERT INTO `cart`
            (`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        try{
            $stmt->execute([$userId, $productId, $product, $hostingName, $hostingPrice, $hostingPrice, $hostingRenew]);
        
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
            (`user_id`, `cart_id`, `product`, `product_name`, `amount`, `renew`, `billing`)
            VALUES (?, ?, ?, ?, ?, ?, ?)");

        try {
            $stmt->execute([$this->userId, $sslId, $sslProduct, $sslName, $sslPrice, $sslRenew, $sslBilling]);
            
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
}
