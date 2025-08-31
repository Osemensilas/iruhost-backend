<?php

namespace App\Controllers\API;
use App\Core\DB;
class SessionController{

    protected $pdo;

    public function __construct(){
        $this->pdo = DB::connection();
    }
    public function userSession(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        header("Content-Type: application/json");

        if (isset($_SESSION['user'])){
            echo json_encode([
                'success' => true,
                'user' => $_SESSION['user']
            ]);
        }else{
            echo json_encode([
                "success" => false,
                "message" => "No active session"
            ]);
        }
    }

    public function userData(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        header("Content-Type: application/json");

        if (isset($_SESSION['user'])){
            $user = $_SESSION['user']['user_id'];

            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$user]);

            if ($stmt->rowCount() > 0){
                $userData = $stmt->fetch();

                echo json_encode([
                    'success' => true,
                    'user' => [
                        'name' => ($userData['name']),
                        'email' => $userData['email'],
                    ]
                    ]);
            }
        }
    }

    public function acctBal(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        header("Content-Type: application/json");

        if (isset($_SESSION['user'])){
            $user = $_SESSION['user']['user_id'];

            $stmt = $this->pdo->prepare("SELECT * FROM account_balance WHERE user_id = ?");
            $stmt->execute([$user]);

            if ($stmt->rowCount() > 0){
                $userData = $stmt->fetch();

                echo json_encode([
                    'success' => true,
                    'user' => [
                        'balance' => ($userData['balance']),
                    ]
                    ]);
            }
        }
    }
}