<?php

namespace App\Core;

use PDO;
use PDOException;

class DB {
    protected static $pdo;

    public static function connection() {
        if (!self::$pdo) {
            $hostname = "localhost";
            $username = "Banks";
            $password = "Bank$101";
            $dbname   = "iruhost";

            try {
                // Connect without specifying database
                $pdo = new PDO("mysql:host=$hostname", $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);

                // Create database if not exists
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

                // Now connect to that database
                $pdo->exec("USE `$dbname`");

                self::$pdo = $pdo;

            } catch (PDOException $err) {
                die("DB Connection failed: " . $err->getMessage());
            }
        }

        return self::$pdo;
    }
}
