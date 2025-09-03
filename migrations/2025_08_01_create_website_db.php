<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS websites (
            id int AUTO_INCREMENT PRIMARY KEY,
            web_id VARCHAR(100),
            category VARCHAR(255),
            web_name VARCHAR(255),
            image VARCHAR(255),
            description VARCHAR(800),
            price VARCHAR(20),
            url VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
};