<?php
// =========================================================================
// Food Forest Sanctuary — Theme Preference API Endpoint
// Persists Dark / Light Theme Preference in Database & Session
// =========================================================================
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json; charset=utf-8');

if (function_exists('is_admin_logged_in') ? !is_admin_logged_in() : (empty($_SESSION['admin_logged_in']) && empty($_SESSION['admin_id']))) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $theme = trim($input['theme'] ?? ($_POST['theme'] ?? 'dark'));

    if (!in_array($theme, ['dark', 'light', 'auto'])) {
        $theme = 'dark';
    }

    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('admin_theme', ?)");
        $stmt->execute([$theme]);

        $_SESSION['admin_theme'] = $theme;

        echo json_encode([
            'success' => true,
            'theme' => $theme,
            'message' => 'Theme preference saved successfully.'
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// GET request returns current theme
$current_theme = get_setting('admin_theme', 'dark');
echo json_encode([
    'success' => true,
    'theme' => $current_theme
]);
exit;
