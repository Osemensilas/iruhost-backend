<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_currency (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(36) NOT NULL,
            currency VARCHAR(36) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
};