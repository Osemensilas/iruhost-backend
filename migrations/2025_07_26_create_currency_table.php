<?php

return function ($pdo) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS currency (
                id INT AUTO_INCREMENT PRIMARY KEY,
                currency VARCHAR(100),
                naira VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo "Users table created\n";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
};
