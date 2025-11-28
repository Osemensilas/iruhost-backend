<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS panel_users (
            id int AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(100),
            username VARCHAR(100),
            email VARCHAR(100) UNIQUE,
            domain VARCHAR(100),
            password VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
};