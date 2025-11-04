<?php

namespace App\Controllers\API;
use App\Core\DB;
use PDO;
use PHPMailer\PHPMailer\Exception;

class StaticController{

    protected $pdo;

    public function __construct(){
        $this->pdo = DB::connection();
    }

    public function getBlogs(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        header("Content-Type: application/json");

        $stmt = $this->pdo->prepare("SELECT * FROM blogs ORDER BY RAND()");
        $stmt->execute();

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode($rows);
    }

    public function recentBlogs(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        header("Content-Type: application/json");

        $stmt = $this->pdo->prepare("SELECT * FROM blogs ORDER BY id DESC LIMIT 3");
        $stmt->execute();

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode($rows);
    }

    public function todayBlogs(){
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        header("Content-Type: application/json");

        $rows = []; 
        
        $stmt = $this->pdo->prepare("SELECT * FROM blogs WHERE category = ? ORDER BY id DESC LIMIT 3");
        $stmt->execute(['Online Business']);

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode($rows);
    }

    public function singleBlog(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $stmt = $this->pdo->prepare("SELECT * FROM blogs WHERE title = ?");
        $stmt->execute([$data['action']]);

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'result' => $rows
            ]);
        }
    }

    public function getBlogBySlug($slug){

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        try {
            $stmt = $this->pdo->prepare("SELECT * FROM blogs WHERE slug = ?");
            $stmt->execute([$slug]);

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode([
                    'status' => 'success',
                    'result' => $row
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Blog not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }


    public function relatedBlog(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $stmt = $this->pdo->prepare("SELECT * FROM blogs WHERE category = ? AND title != ?");
        $stmt->execute([$data['action'], $data['blog']]);

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'result' => $rows
            ]);
        }
    }

    public function otherBlog(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $stmt = $this->pdo->prepare("SELECT * FROM blogs WHERE title != ? ORDER BY RAND()");
        $stmt->execute([$data['blog']]);

        if ($stmt->rowCount() > 0){
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'result' => $rows
            ]);
        }
    }
}