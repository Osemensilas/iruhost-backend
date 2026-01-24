<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS hosting_products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(36) NOT NULL,
            product_id VARCHAR(36) NOT NULL,
            product VARCHAR(255) NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            billing VARCHAR(50) NOT NULL,
            domain VARCHAR(50) NOT NULL,
            username VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            expiry_date VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
};