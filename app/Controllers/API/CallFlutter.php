<?php

namespace App\Controllers\API;
use App\Core\DB;
use PDO;

class CallFlutter {

    protected $pdo;
    protected $secretKey;
    protected $userId;
    protected $clientId;

    public function __construct(){
        $this->pdo = DB::connection();
        $this->secretKey = "FLWSECK_TEST-76fca9105670eb0ded6852bc4785f25b-X";
        $this->userId = $_SESSION['user']['user_id'];
        $this->clientId = "200642152";
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
            $productRow = $stmt->fetch(PDO::FETCH_ASSOC);

            foreach($rows as $row){
                $totalPrice += round($row['amount'], 2);
                $content .= rtrim($content, ',');
            }
        }

        $paymentId = $data['id'];
        $status = $data['status'];
        $ref = $data['ref'];
        $userId = $this->userId;
        $amount = $totalPrice;
        $details = "Payment for $content";

        $stmt = $this->pdo->prepare("INSERT INTO `transactions`(`user_id`, `transaction_id`, `reference`, `amount`, `details`) VALUES (?,?,?,?,?)");
        $result = $stmt->execute([$userId, $paymentId, $ref, $amount, $details]);

        if ($result) {

            foreach($rows as $row){
                if ($row['product'] === 'Domain Registration'){
                    $productName = $row['product_name'];
                    $billing = $row['billing'];
                    $cartId = $row['cart_id'];
                    $this->regDomain($productName, $billing, $cartId);
                }else{
                    if ($row['product'] === 'SSL Registration'){
                        $productName = $row['product_name'];
                        $billing = $row['billing'];
                        $cartId = $row['cart_id'];
                        $this->regSsl($productName, $billing, $cartId);
                    }else{
                       if ($row['product_name'] === 'Starter' || $row['product_name'] === 'Growth' || $row['product_name'] === 'Pro' || $row['product_name'] === 'Enterprise'){
                            $productName = $row['product_name'];
                            $billing = $row['billing'];
                            $cartId = $row['cart_id'];
                            $this->regHosting($productName, $billing, $cartId);
                        } else{
                            if ($row['product'] === 'Email Registration'){
                                $productName = $row['product_name'];
                                $billing = $row['billing'];
                                $cartId = $row['cart_id'];
                                $this->regEmail($productName, $billing, $cartId);
                            }else{
                                if ($row['product'] === 'Web application'){
                                    $productName = $row['product_name'];
                                    $cartId = $row['cart_id'];
                                    $this->webApp($productName, $cartId);
                                }else{
                                    echo json_encode([
                                        'status' => 'successful',
                                        'message' => 'Product added successfully',
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    private function regDomain($productName, $billing, $cartId){
        $productId = uniqid('prod_');
        $product = 'domain';

        $stmt = $this->pdo->prepare("INSERT INTO `products`(`user_id`, `product_id`, `product`, `product_name`, `billing`) VALUES (?,?,?,?,?)");
        $result = $stmt->execute([$this->userId, $productId, $product, $productName, $billing]);

        if ($result){
            $stmt = $this->pdo->prepare("DELETE FROM `cart` WHERE cart_id = ? AND user_id = ?");
            $stmt->execute([$cartId, $this->userId]);
        }
    }

    private function regSsl($productName, $billing, $cartId){
        $productId = uniqid('prod_');
        $product = 'SSL';

        $stmt = $this->pdo->prepare("INSERT INTO `products`(`user_id`, `product_id`, `product`, `product_name`, `billing`) VALUES (?,?,?,?,?)");
        $result = $stmt->execute([$this->userId, $productId, $product, $productName, $billing]);

        if ($result){
            $stmt = $this->pdo->prepare("DELETE FROM `cart` WHERE cart_id = ? AND user_id = ?");
            $stmt->execute([$cartId, $this->userId]);
        }
    }

    private function regEmail($productName, $billing, $cartId){
        $productId = uniqid('prod_');
        $product = 'email';

        $stmt = $this->pdo->prepare("INSERT INTO `products`(`user_id`, `product_id`, `product`, `product_name`, `billing`) VALUES (?,?,?,?,?)");
        $result = $stmt->execute([$this->userId, $productId, $product, $productName, $billing]);

        if ($result){
            $stmt = $this->pdo->prepare("DELETE FROM `cart` WHERE cart_id = ? AND user_id = ?");
            $stmt->execute([$cartId, $this->userId]);
        }
    }

    private function regHosting($productName, $billing, $cartId){
        $productId = uniqid('prod_');
        $product = 'hosting';

        $stmt = $this->pdo->prepare("INSERT INTO `products`(`user_id`, `product_id`, `product`, `product_name`, `billing`) VALUES (?,?,?,?,?)");
        $result = $stmt->execute([$this->userId, $productId, $product, $productName, $billing]);

        if ($result){
            $stmt = $this->pdo->prepare("DELETE FROM `cart` WHERE cart_id = ? AND user_id = ?");
            $stmt->execute([$cartId, $this->userId]);
        }
    }

    private function webApp($productName, $cartId){
        $productId = uniqid('prod_');
        $product = 'web app';
        $billing = '';

        $stmt = $this->pdo->prepare("INSERT INTO `products`(`user_id`, `product_id`, `product`, `product_name`, `billing`) VALUES (?,?,?,?,?)");
        $result = $stmt->execute([$this->userId, $productId, $product, $productName, $billing]);

        if ($result){
            $stmt = $this->pdo->prepare("DELETE FROM `cart` WHERE cart_id = ? AND user_id = ?");
            $stmt->execute([$cartId, $this->userId]);
        }
    }
}