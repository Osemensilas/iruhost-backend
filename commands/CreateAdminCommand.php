<?php

class CreateAdminCommand {
    public function run($argv) {
        $pdo = new PDO("mysql:host=localhost;dbname=iruhost", "Banks", "Bank$101");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // CLI arguments (with defaults)
        $userId   = $argv[2] ?? uniqid("I");   
        $role     = "admin";
        $permission = "all";    
        $name     = $argv[3] ?? "Super Admin";
        $email    = $argv[4] ?? "osemen.dev@gmail.com";
        $password = $argv[5] ?? "Onion$101Main";

        // Hash the password
        $hashed = password_hash($password, PASSWORD_BCRYPT);

        $sql = "INSERT INTO users (user_id, role, permission, name, email, password, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $role, $permission, $name, $email, $hashed]);

        echo "Admin created: $email\n";
        echo "User ID: $userId\n";
    }
}
