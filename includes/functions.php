<?php
// ================================================================
//  includes/functions.php – Shared helper functions
// ================================================================
require_once dirname(__DIR__) . '/config/config.php';

// ── Security ──────────────────────────────────────────────────
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function sanitizeInput(string $str): string {
    return trim(strip_tags($str));
}

function generateToken(): string {
    return bin2hex(random_bytes(32));
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken();
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

// ── Auth ──────────────────────────────────────────────────────
function isAdminLoggedIn(): bool {
    return !empty($_SESSION['admin_id']);
}

function requireAdmin(): void {
    if (!isAdminLoggedIn()) {
        header('Location: ' . ADMIN_URL . 'login.php');
        exit;
    }
}

function currentAdmin(): array {
    return $_SESSION['admin_user'] ?? [];
}

// ── Database helpers ─────────────────────────────────────────
function slugify(string $text): string {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function uniqueSlug(PDO $pdo, string $table, string $slug, ?int $excludeId = null): string {
    $base = $slug;
    $i    = 1;
    while (true) {
        $sql = "SELECT COUNT(*) FROM `$table` WHERE slug = ?";
        $params = [$slug];
        if ($excludeId) {
            $sql    .= ' AND id != ?';
            $params[] = $excludeId;
        }
        $count = $pdo->prepare($sql);
        $count->execute($params);
        if ($count->fetchColumn() == 0) break;
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

// ── Image / File Upload ───────────────────────────────────────
function handleImageUpload(string $inputName, string $subfolder = 'general'): ?string {
    if (empty($_FILES[$inputName]['name'])) return null;

    $file     = $_FILES[$inputName];
    $allowed  = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
    $maxSize  = 5 * 1024 * 1024; // 5 MB

    if ($file['error'] !== UPLOAD_ERR_OK)      return null;
    if (!in_array($file['type'], $allowed))     return null;
    if ($file['size'] > $maxSize)               return null;

    // Validate actual image
    if (!getimagesize($file['tmp_name']))       return null;

    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $newName = uniqid('img_', true) . '.' . $ext;
    $dir     = UPLOAD_PATH . $subfolder . '/';

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $dest = $dir . $newName;
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return UPLOAD_URL . $subfolder . '/' . $newName;
    }
    return null;
}

// ── Pagination ────────────────────────────────────────────────
function paginate(int $total, int $perPage, int $current): array {
    $totalPages = (int) ceil($total / $perPage);
    $offset     = ($current - 1) * $perPage;
    return [
        'total'       => $total,
        'per_page'    => $perPage,
        'current'     => $current,
        'total_pages' => $totalPages,
        'offset'      => max(0, $offset),
    ];
}

// ── Flash messages ────────────────────────────────────────────
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function renderFlash(): string {
    $f = getFlash();
    if (!$f) return '';
    $color = $f['type'] === 'success' ? '#27ae60' : '#c0392b';
    return '<div class="flash-msg" style="background:' . $color . ';color:#fff;padding:12px 20px;border-radius:8px;margin-bottom:1rem;">'
         . e($f['msg']) . '</div>';
}

// ── Email (PHPMailer-compatible wrapper) ──────────────────────
function sendMail(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = '', bool $isHtml = true): bool {
    $smtpHost   = setting('smtp_host');
    $smtpUser   = setting('smtp_username');
    $smtpPass   = setting('smtp_password');
    $fromEmail  = setting('smtp_from_email', 'no-reply@legacysafaris.com');
    $fromName   = setting('smtp_from_name',  'Legacy Safaris Ltd');

    // If PHPMailer is installed (via Composer), use it
    $phpmailerPath = dirname(__DIR__) . '/vendor/autoload.php';
    if (file_exists($phpmailerPath)) {
        require_once $phpmailerPath;
        try {
            $mailClass = 'PHPMailer\\PHPMailer\\PHPMailer';
            $mail = new $mailClass(true);
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPass;
            $mail->SMTPSecure = setting('smtp_encryption') === 'ssl' ? 'ssl' : 'tls';
            $mail->Port       = (int) setting('smtp_port', '587');
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail, $toName);
            $mail->addReplyTo($fromEmail, $fromName);
            $mail->isHTML($isHtml);
            $mail->Subject = $subject;
            $mail->Body    = $isHtml ? $htmlBody : ($textBody ?: $htmlBody);
            $mail->AltBody = $textBody ?: strip_tags($htmlBody);
            return $mail->send();
        } catch (\Throwable $e) {
            error_log('Mail error: ' . $e->getMessage());
            return false;
        }
    }

    // Fallback: PHP mail()
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= $isHtml
        ? "Content-type: text/html; charset=UTF-8\r\n"
        : "Content-type: text/plain; charset=UTF-8\r\n";
    $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
    $body = $isHtml ? $htmlBody : ($textBody ?: $htmlBody);
    return mail($toEmail, $subject, $body, $headers);
}

// ── Format helpers ────────────────────────────────────────────
function formatPrice(float $amount, string $currency = 'USD'): string {
    if ($currency === 'USD') {
        return '$' . number_format($amount, 0);
    }

    if ($currency === 'UGX') {
        return 'UGX ' . number_format($amount, 0);
    }

    return number_format($amount, 0) . ' ' . $currency;
}

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)       return 'just now';
    if ($diff < 3600)     return floor($diff / 60) . ' min ago';
    if ($diff < 86400)    return floor($diff / 3600) . ' hrs ago';
    if ($diff < 2592000)  return floor($diff / 86400) . ' days ago';
    return date('M j, Y', strtotime($datetime));
}
