<?php

namespace App\Controllers\Api;
use App\Core\DB;


class UserProducts{

    protected $userId;
    protected $pdo;

    public function __construct(){
        $this->userId = $_SESSION['user']['user_id'];
        $this->pdo = DB::connection();
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
            'user' => $_SESSION['user']['name'],
            'product' => $rows
        ]);
    }
}