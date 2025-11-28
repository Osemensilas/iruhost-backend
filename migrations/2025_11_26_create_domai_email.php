<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS email_domain (
            id INT NOT NULL AUTO_INCREMENT,
            user_id VARCHAR(100),
            domain VARCHAR(255) NOT NULL,
            create_from VARCHAR(50),
            PRIMARY KEY (id)
        )
    ");
};