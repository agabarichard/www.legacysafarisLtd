<?php
// ================================================================
//  config/db.php – Database connection (PDO)
// ================================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'legacy_safaris');
define('DB_USER', 'root');       // Change to your DB user
define('DB_PASS', '');           // Change to your DB password
define('DB_CHARSET', 'utf8mb4');

function getPDO(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (\PDOException $e) {
            // Never expose DB credentials in error messages
            error_log('DB Connection failed: ' . $e->getMessage());
            die('<h2 style="font-family:sans-serif;color:#c0392b;padding:2rem;">
                  Database connection error. Please try again later.</h2>');
        }
    }
    return $pdo;
}
