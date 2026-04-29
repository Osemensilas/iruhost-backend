<?php

namespace App\Controllers\API;
use App\Core\DB;
use PDO;
use Dotenv\Dotenv;
class SessionController{

    protected $pdo;
    private $publicKey;
    public function __construct(){
        $this->pdo = DB::connection();

        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
        $dotenv->load();

        $this->publicKey = $_ENV['FLUTTERWAVE_PUBLIC_KEY'] ?? null;
        //$this->publicKey = "FLWPUBK_TEST-ea3991777877ae8c494e5d206d286b33-X";
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

            $ref = uniqid("ref_");

            if ($stmt->rowCount() > 0){
                $userData = $stmt->fetch();

                echo json_encode([
                    'success' => true,
                    'user' => [
                        'name' => ($userData['name']),
                        'email' => $userData['email'],
                        'user_id' => $userData['user_id'],
                        'pbk' => $this->publicKey,
                        'ref' => $ref,
                    ]
                    ]);
            }
        }
    }

    public function userAddress(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        header("Content-Type: application/json");

        if (isset($_SESSION['user'])){
            $user = $_SESSION['user']['user_id'];

            $stmt = $this->pdo->prepare("SELECT * FROM address WHERE user_id = ?");
            $stmt->execute([$user]);

            if ($stmt->rowCount() > 0){
                $userData = $stmt->fetch();

                echo json_encode([
                    'success' => true,
                    'user' => [
                        'address' => ($userData['address1']),
                        'phone' => $userData['cCode'] . $userData['phone'],
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

    public function webList(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $stmt = $this->pdo->prepare("SELECT * FROM `websites` WHERE category = ? LIMIT 4");
        $stmt->execute([$data['website']]);

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'result' => $rows
            ]);
        }
    }

    public function webListAll(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $stmt = $this->pdo->prepare("SELECT * FROM `websites` WHERE category = ?");
        $stmt->execute([$data['website']]);

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'result' => $rows
            ]);
        }
    }

    public function webApp(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $productId = $_GET['productId'] ?? null;

        $stmt = $this->pdo->prepare("SELECT * FROM `websites` WHERE web_id = ?");
        $stmt->execute([$productId]);

        if ($stmt->rowCount() > 0){
            $row = $stmt->fetch();

            echo json_encode([
                'status' => 'success',
                'result' => $row,
                'stack' => $row['stack']
            ]);
        }
    }

    public function getSingleWeb(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $stmt = $this->pdo->prepare("SELECT * FROM `websites` WHERE web_id = ?");
        $stmt->execute([$data['website']]);

        if ($stmt->rowCount() > 0){
            $row = $stmt->fetch();

            echo json_encode([
                'status' => 'success',
                'result' => $row
            ]);
        }
    }

    public function checkPanelUser(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        header("Content-Type: application/json");

        if (isset($_SESSION['panel_user'])){
            echo json_encode([
                'success' => true,
                'user' => $_SESSION['panel_user']
            ]);
        }else{
            echo json_encode([
                "success" => false,
                "message" => "No active session"
            ]);
        }
    }
}