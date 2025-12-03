<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS whm_users (
            id int AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(100),
            hosting VARCHAR(100),
            email VARCHAR(100) UNIQUE,
            domain VARCHAR(100),
            password VARCHAR(100),
            expiry_date VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
};