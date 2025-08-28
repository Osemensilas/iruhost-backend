<?php

namespace App\Controllers\API;
use App\Core\DB;
use PDO;

class CartItems {

    private $pdo;
    private $userId;
    private $publicKey;

    public function __construct(){
        $this->pdo = DB::connection();
        $this->userId = $_SESSION['user']['user_id'] ?? $_SESSION['guest']['id'] ?? null;
        $this->publicKey = "FLWPUBK_TEST-ea3991777877ae8c494e5d206d286b33-X";
    }
    public function cartItems() {

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!$this->userId) {
            echo json_encode(['status' => 'error', 'message' => 'User ID not found']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM cart WHERE user_id = ?");
        $stmt->execute([$this->userId]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($rows) {
            echo json_encode([
                'status' => 'success',
                'items' => $rows
            ]);
        } else {
            echo json_encode([
                'status' => 'empty',
                'items' => 'No items found in cart.'
            ]);
        }
    }

    public function clearAllItems(){
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        
        if ($data['action'] === 'empty cart'){
            $stmt = $this->pdo->prepare("SELECT * FROM cart WHERE user_id = ?");
            $stmt->execute([$this->userId]);

            if ($stmt->rowCount() > 0){

                $stmt = $this->pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                $execution = $stmt->execute([$this->userId]);

                if($execution){
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'operation successful'
                    ]);
                }else{
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'operation failed'
                    ]);
                }

            }else{
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No item in cart'
                ]);
            }
        }
    }

    public function removeItem(){

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $stmt = $this->pdo->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
        $execution = $stmt->execute([$data['action'], $this->userId]);

        if($execution){
            echo json_encode([
                'status' => 'success',
                'message' => 'operation successful'
            ]);
        }else{
            echo json_encode([
                'status' => 'error',
                'message' => 'operation failed'
            ]);
        }
    }

    public function cartDomain(){

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!$this->userId) {
            echo json_encode(['status' => 'error', 'message' => 'User ID not found']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product = ?");
        $stmt->execute([$this->userId, 'Domain Registration']);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($rows) {
            echo json_encode([
                'status' => 'success',
                'items' => $rows
            ]);
        } else {
            echo json_encode([
                'status' => 'empty',
                'items' => 'No items found in cart.'
            ]);
        }
    }

    public function cartTotal(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!$this->userId) {
            echo json_encode(['status' => 'error', 'message' => 'User ID not found']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM cart WHERE user_id = ?");
        $stmt->execute([$this->userId]);
        $totalPrice = 0;
        $totalDomainPrice = 0;
        $totalSslPrice = 0;
        $totalHostingPrice = 0;
        $totalEmailPrice = 0;

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach($rows as $row){
                if ($row['product'] == 'Domain Registration'){
                    $totalDomainPrice += round($row['amount'], 2);
                }else{
                    if ($row['product'] == 'SSL Registration'){
                        $totalSslPrice += round($row['amount'], 2);
                    }else{
                        if ($row['product'] == 'Email Registrations'){
                            $totalEmailPrice += round($row['amount'], 2);
                        }else{
                            $totalHostingPrice += round($row['amount'], 2);
                        }
                    }
                }
                $totalPrice += round($row['amount'], 2);
            }
        }
        echo json_encode([
            'status' => 'success',
            'totalPrice' => round($totalPrice, 2),
            'totalDomainPrice' => round($totalDomainPrice, 2),
            'totalSslPrice' => round($totalSslPrice, 2),
            'totalHostingPrice' => round($totalHostingPrice, 2),
            'totalEmailPrice' => round($totalEmailPrice, 2),
        ]);
    }

    public function cartSession(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        header("Content-Type: application/json");

        $stmt = $this->pdo->prepare("SELECT * FROM cart WHERE user_id = ?");
        $stmt->execute([$this->userId]);
        $totalPrice = 0;
        $ref = uniqid("ref_");

        if ($stmt->rowCount() > 0) {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $totalPrice += round($row['amount'], 2);
            }
        }

        if (isset($_SESSION['user'])){
            echo json_encode([
                'success' => true,
                'user' => $_SESSION['user'],
                'pbk' => $this->publicKey,
                'totalPrice' => $totalPrice,
                'ref' => $ref
            ]);
        }else{
            echo json_encode([
                "success" => false,
                "message" => "No active session"
            ]);
        }
    }
}
