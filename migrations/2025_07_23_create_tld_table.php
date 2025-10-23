<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tlds (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tld_id VARCHAR(36) NOT NULL,
            tld VARCHAR(255) NOT NULL,
            registration VARCHAR(255) NOT NULL,
            renewal VARCHAR(255) NOT NULL,
            transfer VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
};