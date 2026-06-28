<?php
// ================================================================
//  config/config.php – Central site configuration loader
// ================================================================
require_once __DIR__ . '/db.php';

// Load all site settings into a global array
function getSettings(): array {
    static $settings = null;
    if ($settings === null) {
        try {
            $pdo  = getPDO();
            $rows = $pdo->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll();
            $settings = array_column($rows, 'setting_value', 'setting_key');
        } catch (\Exception $e) {
            $settings = [];
        }
    }
    return $settings;
}

function setting(string $key, string $default = ''): string {
    $s = getSettings();
    return $s[$key] ?? $default;
}

// ── Session ───────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// ── Path helpers ─────────────────────────────────────────────
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads/');
define('UPLOAD_URL',  '/uploads/');
define('ADMIN_URL',   '/admin/');

// ── Site URL (auto-detect) ───────────────────────────────────
$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('SITE_URL', $protocol . '://' . $host);
