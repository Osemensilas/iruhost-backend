<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS support_user_chats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(36) NOT NULL,
            reciever_id VARCHAR(36) NOT NULL,
            ticket_id VARCHAR(36) NOT NULL,
            user_msg VARCHAR(1000) NOT NULL,
            reciever_msg VARCHAR(1000) NOT NULL,
            image VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
};