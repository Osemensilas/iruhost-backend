<?php

namespace App\Controllers\API;

class LogoutController{
    public function userLogout(){
        if (isset($_SESSION['user'])){
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
                return;
            }

            $data = json_decode(file_get_contents("php://input"), true);

            print_r($data['logout']);

            session_unset();
            session_destroy();
            echo json_encode(['status' => 'success', 'message' => 'Logged out']);
        }
    }
}