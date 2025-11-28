<?php

return function ($pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mailboxes_user (
            id INT NOT NULL AUTO_INCREMENT,
            email VARCHAR(255) NOT NULL,
            domain_id INT NOT NULL,
            password VARCHAR(255) NOT NULL,
            PRIMARY KEY (id)
        )
    ");
};