<?php

namespace App\Controllers\API;
use App\Core\DB;
use PDO;

class AdminDashboard {

    protected $pdo;

    public function __construct(){
        $this->pdo = DB::connection();
    }

    public function signUps(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['admin'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT created_at FROM users WHERE role = ?");
        $stmt->execute(['user']);

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Prepare array for past 8 months (including current month)
            $months = [];
            for ($i = 0; $i < 8; $i++) {
                $timestamp = strtotime("-$i months");
                $key = date("Y-m", $timestamp);
                $label = date("M", $timestamp);
                $months[$key] = ['label' => $label, 'count' => 0];
            }

            // Count signups
            foreach ($rows as $row) {
                $dateReg = $row['created_at'];
                $key = date("Y-m", strtotime($dateReg));
                if (isset($months[$key])) {
                    $months[$key]['count']++;
                }
            }

            // Keep order: most recent first
            $months = array_values($months);

            echo json_encode([
                'status' => 'success',
                'data' => $months,
                'sign_ups' => $stmt->rowCount()
            ]);
        }
    }

    public function activeUsers(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['admin'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT DISTINCT user_id FROM products");
        $stmt->execute();

        $activeUsers = $stmt->rowCount();

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE role = ?");
        $stmt->execute(['user']);

        $totalUsers = $stmt->rowCount() - $activeUsers;

        echo json_encode([
            'status' => 'success',
            'active_user' => $activeUsers,
            'total_user' => $totalUsers
        ]);
    }

    public function getTotalSalesNum(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['admin'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE product = ?");
        $stmt->execute(['hosting']);

        $hosting = $stmt->rowCount();

        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE product = ?");
        $stmt->execute(['domain']);

        $domain = $stmt->rowCount();

        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE product = ?");
        $stmt->execute(['SSL']);

        $ssl = $stmt->rowCount();

        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE product = ?");
        $stmt->execute(['email']);

        $email = $stmt->rowCount();

        echo json_encode([
            'domain' => $domain,
            'email' => $email,
            'hosting' => $hosting,
            'ssl' => $ssl,
            'status' => 'success'
        ]);
    }

    public function getTotalWebSalesNum(){
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['admin'])){
            echo json_encode(['status' => 'error', 'message' => 'You do not have permission']);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE product = ?");
        $stmt->execute(['web app']);

        $website = $stmt->rowCount();

        $stmt = $this->pdo->prepare("SELECT * FROM products");
        $stmt->execute();

        $product = $stmt->rowCount() - $website;

        echo json_encode([
            'website' => $website,
            'others' => $product,
            'status' => 'success'
        ]);
    }
}
