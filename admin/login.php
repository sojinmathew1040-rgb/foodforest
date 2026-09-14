<?php
// =========================================================================
// Food Forest Sanctuary — Luxury Admin Login Portal
// =========================================================================
require_once __DIR__ . '/includes/auth.php';

// If already logged in, go directly to dashboard
if (!empty($_SESSION['admin_id']) && !empty($_SESSION['admin_logged_in'])) {
    header("Location: index.php");
    exit;
}

$error_message = '';
$success_message = '';

if (isset($_GET['logged_out'])) {
    $success_message = 'You have safely logged out of the Food Forest Concierge.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error_message = 'Please provide both username and password.';
    } else {
        $auth_res = login_admin($username, $password);
        if ($auth_res['success']) {
            $return_url = !empty($_GET['return']) ? $_GET['return'] : 'index.php';
            // Protect against external redirects
            if (strpos($return_url, 'http://') !== false || strpos($return_url, 'https://') !== false) {
                $return_url = 'index.php';
            }
            header("Location: " . $return_url);
            exit;
        } else {
            $error_message = $auth_res['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Authentication — Food Forest Sanctuary</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Dedicated Separate Admin Stylesheet -->
    <link rel="stylesheet" href="assets/css/admin.css?v=<?php echo time(); ?>">
</head>
<body class="adm-body">

<div class="adm-login-wrapper">
    <!-- Atmospheric Ambient Glow -->
    <div class="adm-login-glow"></div>

    <!-- Login Glass Card -->
    <div class="adm-login-card">
        <div class="adm-login-header">
            <div class="adm-brand-emblem">
                <i class="fa-solid fa-seedling"></i>
            </div>
            <h1 class="adm-brand-title">FOOD FOREST</h1>
            <div class="adm-brand-sub">KANTHALLOOR SANCTUARY</div>
            <p class="adm-login-tagline">Executive Concierge & Reservation Portal</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="adm-alert adm-alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo e($error_message); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="adm-alert adm-alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <span><?php echo e($success_message); ?></span>
            </div>
        <?php endif; ?>

        <form action="login.php<?php echo !empty($_GET['return']) ? '?return=' . urlencode($_GET['return']) : ''; ?>" method="POST">
            <!-- Username -->
            <div class="adm-form-group">
                <label for="adm-username" class="adm-label">Administrator Username</label>
                <div class="adm-input-icon-wrap">
                    <input type="text" id="adm-username" name="username" class="adm-input" placeholder="e.g. admin" required autofocus autocomplete="username" value="<?php echo e($_POST['username'] ?? ''); ?>">
                    <i class="fa-regular fa-user adm-input-icon"></i>
                </div>
            </div>

            <!-- Password -->
            <div class="adm-form-group">
                <label for="adm-password" class="adm-label">Secure Access Key</label>
                <div class="adm-input-icon-wrap">
                    <input type="password" id="adm-password" name="password" class="adm-input" placeholder="••••••••••••" required autocomplete="current-password">
                    <i class="fa-solid fa-lock adm-input-icon"></i>
                    <button type="button" class="adm-toggle-pwd" data-target="adm-password" title="Toggle password visibility">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="adm-btn-primary">
                <span>Access Concierge Console</span>
                <i class="fa-solid fa-arrow-right"></i>
            </button>
        </form>

        <!-- Default Credentials Quick Helper -->
        <div class="adm-login-hint">
            <i class="fa-solid fa-key" style="color: var(--adm-gold); margin-right: 4px;"></i>
            Default Credentials: User: <code>admin</code> • Key: <code>admin123</code>
        </div>

        <div class="adm-login-footer-links">
            <a href="../index.php"><i class="fa-solid fa-arrow-left"></i> Return to Main Sanctuary Website</a>
        </div>
    </div>
</div>

<script src="assets/js/admin.js?v=<?php echo time(); ?>"></script>
</body>
</html>
