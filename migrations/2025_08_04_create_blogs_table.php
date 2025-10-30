<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS blogs (
            id int AUTO_INCREMENT PRIMARY KEY,
            blog_id VARCHAR(100),
            title VARCHAR(255),
            slug VARCHAR(255),
            content LONGTEXT,
            image VARCHAR(300),
            writer VARCHAR(50),
            category VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
};