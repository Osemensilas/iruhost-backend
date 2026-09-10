<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS hosting_list (
            id int AUTO_INCREMENT PRIMARY KEY,
            hosting_id VARCHAR(100),
            hosting_name VARCHAR(100),
            hosting_price VARCHAR(100),
            category VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
};