<?php

namespace App\Controllers\Api;
use App\Core\DB;
use PDO;


class UserProducts{

    protected $userId;
    protected $pdo;
    protected $nameSiloKey;

    public function __construct(){
        $this->userId = $_SESSION['user']['user_id'];
        $this->pdo = DB::connection();
        $this->nameSiloKey = "3079601359d46e924bfbab85"; 
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

        $stmt = $this->pdo->prepare("SELECT * FROM `products` WHERE user_id = ?");
        $stmt->execute([$this->userId]);

        $expiring = [];

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach($rows as $row){
                $expiryDate = $row['expiry_date'];
                $twoWeeksBefore = date('Y-m-d', strtotime('-2 weeks', strtotime($expiryDate)));
            
                $now = date('Y-m-d');

                if ($now >= $twoWeeksBefore) {
                    $expiring[] = $row;
                }
            }

            echo json_encode([
                'status' => 'success',
                'user' => $_SESSION['user']['name'],
                'products' => $expiring
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
        $stmt->execute([$this->userId, 'app']);

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll();

            echo json_encode([
                'status' => 'success',
                'user' => $_SESSION['user']['name'],
                'products' => $rows
            ]);
        }
    }
}