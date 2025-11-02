<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS forget_password (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(225) NOT NULL,
            code VARCHAR(36) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
};