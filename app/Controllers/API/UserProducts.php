<?php

namespace App\Controllers\Api;
use Dotenv\Dotenv;
use App\Core\DB;
use PDO;


class UserProducts{

    protected $userId;
    protected $pdo;
    protected $nameSiloKey;
    protected $enomUserId;
    protected $enomApiToken;

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
}