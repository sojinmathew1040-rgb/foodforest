<?php
// =========================================================================
// Food Forest Sanctuary — Admin Authentication & Security Guard
// =========================================================================

if (session_status() === PHP_SESSION_NONE) {
    // Secure session settings
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

require_once __DIR__ . '/db.php';

// Ensure database tables and initial records exist
get_db();

/**
 * Checks whether an administrator is actively logged in.
 */
function is_admin_logged_in() {
    return (!empty($_SESSION['admin_logged_in']) || !empty($_SESSION['admin_id']));
}

/**
 * Checks and enforces 5-minute (300 seconds) inactivity auto-logout for admin.
 */
function check_admin_inactivity_timeout() {
    if (is_admin_logged_in()) {
        $now = time();
        $timeout_minutes = 15;
        if (function_exists('get_setting')) {
            $timeout_minutes = (int)get_setting('admin_session_timeout_minutes', 15);
        }
        if ($timeout_minutes < 1) $timeout_minutes = 15;
        $timeout_seconds = $timeout_minutes * 60;
        if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity'] > $timeout_seconds)) {
            logout_admin();
            header("Location: login.php?timeout=1");
            exit;
        }
        $_SESSION['last_activity'] = $now;
    }
}

/**
 * Ensures administrator is actively logged in.
 * If not, redirects to login page with return url.
 */
function require_admin_auth() {
    check_admin_inactivity_timeout();
    if (!is_admin_logged_in()) {
        $current_url = $_SERVER['REQUEST_URI'] ?? '';
        header("Location: login.php?return=" . urlencode($current_url));
        exit;
    }
}

/**
 * Returns currently logged-in admin data with avatar and fresh database synchronization.
 */
function current_admin() {
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    static $admin_cache = null;
    if ($admin_cache !== null) {
        return $admin_cache;
    }
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("SELECT id, username, full_name, email, role, avatar_url FROM admins WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$_SESSION['admin_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $_SESSION['admin_username'] = $row['username'];
            $_SESSION['admin_name'] = $row['full_name'];
            $_SESSION['admin_role'] = $row['role'];
            $_SESSION['admin_email'] = $row['email'];
            $_SESSION['admin_avatar'] = $row['avatar_url'] ?? '';
            $admin_cache = $row;
            return $admin_cache;
        }
    } catch (Exception $e) {}

    $admin_cache = [
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'] ?? 'admin',
        'full_name' => $_SESSION['admin_name'] ?? 'Administrator',
        'role' => $_SESSION['admin_role'] ?? 'Concierge Lead',
        'email' => $_SESSION['admin_email'] ?? '',
        'avatar_url' => $_SESSION['admin_avatar'] ?? ''
    ];
    return $admin_cache;
}

/**
 * Attempts authentication with username and password.
 */
function login_admin($username, $password) {
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
    $stmt->execute([trim($username)]);
    $admin = $stmt->fetch();

    $is_valid = false;
    if ($admin) {
        $stored = $admin['password_hash'] ?? '';
        if ($password === $stored || password_verify($password, $stored)) {
            $is_valid = true;
        }
    }

    if ($admin && $is_valid) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_avatar'] = $admin['avatar_url'] ?? '';
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['last_activity'] = time();

        // Update last login timestamp
        $update = $pdo->prepare("UPDATE admins SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
        $update->execute([$admin['id']]);

        return ['success' => true];
    }

    return ['success' => false, 'error' => 'Invalid username or password. Please verify your credentials.'];
}

/**
 * Destroys session completely.
 */
function logout_admin() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * CSRF token helpers
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function generate_csrf_token() {
    return csrf_token();
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf_token($token) {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize helper
 */
if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
    }
}
?>
