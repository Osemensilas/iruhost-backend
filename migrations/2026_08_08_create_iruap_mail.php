<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS iruap_professional_mails (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(36) NOT NULL,
            product_id VARCHAR(36) NOT NULL,
            email_id VARCHAR(36) NOT NULL,
            mailbox VARCHAR(50) NOT NULL,
            domain VARCHAR(50) NOT NULL,
            password VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
};