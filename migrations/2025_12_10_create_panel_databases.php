<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS panel_users_database (
            id int AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(100),
            panel_id VARCHAR(100),
            database_name VARCHAR(100),
            db_user VARCHAR(100),
            db_password VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            pma_token VARCHAR(100),
            expires_at DATETIME
        )
    ");
};