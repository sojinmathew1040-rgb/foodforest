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
 * Ensures administrator is actively logged in.
 * If not, redirects to login page with return url.
 */
function require_admin_auth() {
    if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_logged_in'])) {
        $current_url = $_SERVER['REQUEST_URI'];
        header("Location: login.php?return=" . urlencode($current_url));
        exit;
    }
}

/**
 * Returns currently logged-in admin data.
 */
function current_admin() {
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    return [
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'] ?? 'admin',
        'full_name' => $_SESSION['admin_name'] ?? 'Administrator',
        'role' => $_SESSION['admin_role'] ?? 'Concierge Lead',
        'email' => $_SESSION['admin_email'] ?? ''
    ];
}

/**
 * Attempts authentication with username and password.
 */
function login_admin($username, $password) {
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
    $stmt->execute([trim($username)]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['admin_email'] = $admin['email'];
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
