<?php
// =========================================================================
// Food Forest Sanctuary — Client & Guest Authentication API
// Endpoint: POST /api/guest_auth.php
// =========================================================================

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../admin/includes/db.php';
require_once __DIR__ . '/../includes/client_auth.php';

client_session_start();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$action = trim($input['action'] ?? ($_GET['action'] ?? 'status'));

try {
    switch ($action) {
        case 'status':
            $is_user = is_client_user_logged_in();
            $is_guest = is_guest_booking_session_active();
            $data = [
                'success' => true,
                'is_logged_in' => $is_user || $is_guest,
                'auth_type' => $is_user ? 'permanent_user' : ($is_guest ? 'guest_pass' : 'none'),
                'user' => $is_user ? get_logged_in_client_user() : null,
                'guest_booking' => $is_guest ? get_active_guest_booking() : null
            ];
            echo json_encode($data);
            exit;

        case 'send_otp':
            $email = strtolower(trim($input['email'] ?? ''));
            $captcha = trim($input['captcha'] ?? '');

            if (!empty($captcha)) {
                if (!verify_captcha_code($captcha, 'guest')) {
                    echo json_encode(['success' => false, 'message' => 'Security Captcha verification failed. Please enter the characters shown in the security badge.']);
                    exit;
                }
            }

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Please provide a valid Google Mail or Email address.']);
                exit;
            }

            // Generate 5-Digit Verification Code
            $otp = str_pad((string)random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
            $_SESSION['ff_email_otp'] = $otp;
            $_SESSION['ff_email_otp_email'] = $email;
            $_SESSION['ff_email_otp_expiry'] = time() + 600; // 10 minutes

            // Send Email
            send_sanctuary_otp_email($email, $otp);

            $is_local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

            $resp = [
                'success' => true,
                'message' => 'A 5-digit verification code has been sent to ' . htmlspecialchars($email) . '. Please check your inbox.',
                'email' => $email
            ];
            if ($is_local) {
                $resp['dev_otp'] = $otp;
            }
            echo json_encode($resp);
            exit;

        case 'verify_otp':
            $email = strtolower(trim($input['email'] ?? ''));
            $otp = trim($input['otp'] ?? '');

            $session_otp = $_SESSION['ff_email_otp'] ?? '';
            $session_email = $_SESSION['ff_email_otp_email'] ?? '';
            $session_expiry = (int)($_SESSION['ff_email_otp_expiry'] ?? 0);

            if (empty($session_otp) || empty($session_email)) {
                echo json_encode(['success' => false, 'message' => 'No active OTP verification pending. Please request a new code.']);
                exit;
            }

            if (time() > $session_expiry) {
                echo json_encode(['success' => false, 'message' => 'Your 5-digit verification code has expired. Please request a new code.']);
                exit;
            }

            if (strcasecmp($email, $session_email) !== 0 || $otp !== (string)$session_otp) {
                echo json_encode(['success' => false, 'message' => 'Invalid 5-digit verification code. Please check your email and enter the correct code.']);
                exit;
            }

            // Successfully verified! Clear OTP
            unset($_SESSION['ff_email_otp']);
            unset($_SESSION['ff_email_otp_email']);
            unset($_SESSION['ff_email_otp_expiry']);

            $pdo = get_db();
            ensure_users_and_guest_columns($pdo);

            // 1. Check if user already exists
            $u_stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $u_stmt->execute([$email]);
            $user = $u_stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
                set_client_user_session($user);
                echo json_encode([
                    'success' => true,
                    'message' => 'Welcome back, ' . htmlspecialchars($user['full_name']) . '! Access granted.',
                    'user' => $user
                ]);
                exit;
            }

            // 2. Check if bookings exist for this email
            $b_stmt = $pdo->prepare("SELECT * FROM bookings WHERE LOWER(guest_email) = ? ORDER BY id DESC");
            $b_stmt->execute([$email]);
            $bookings = $b_stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($bookings)) {
                $latest = $bookings[0];
                $full_name = trim($latest['guest_name'] ?? 'Resident Guest');
                $phone = trim($latest['guest_phone'] ?? '');

                $rand_hash = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT);
                $ins = $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, last_login) VALUES (?, ?, ?, ?, NOW())");
                $ins->execute([$full_name, $email, $phone, $rand_hash]);
                $new_id = (int)$pdo->lastInsertId();

                $pdo->prepare("UPDATE bookings SET user_id = ? WHERE LOWER(guest_email) = ?")->execute([$new_id, $email]);

                $user = [
                    'id' => $new_id,
                    'full_name' => $full_name,
                    'email' => $email,
                    'phone' => $phone
                ];
                set_client_user_session($user);
                echo json_encode([
                    'success' => true,
                    'message' => 'Reservation verified! Welcome, ' . htmlspecialchars($full_name) . '.',
                    'user' => $user
                ]);
                exit;
            }

            // 3. New guest with no prior bookings
            $name_part = explode('@', $email)[0];
            $full_name = ucwords(str_replace(['.', '_', '-'], ' ', $name_part));
            $rand_hash = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT);
            $ins = $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, last_login) VALUES (?, ?, ?, ?, NOW())");
            $ins->execute([$full_name, $email, '', $rand_hash]);
            $new_id = (int)$pdo->lastInsertId();

            $user = [
                'id' => $new_id,
                'full_name' => $full_name,
                'email' => $email,
                'phone' => ''
            ];
            set_client_user_session($user);
            echo json_encode([
                'success' => true,
                'message' => 'Welcome to Food Forest Sanctuary, ' . htmlspecialchars($full_name) . '!',
                'user' => $user
            ]);
            exit;

        case 'login':
            $email = trim($input['email'] ?? '');
            $password = trim($input['password'] ?? '');
            $captcha = trim($input['captcha'] ?? '');

            if (!verify_captcha_code($captcha, 'guest')) {
                echo json_encode(['success' => false, 'message' => 'Security Captcha verification failed. Please enter the characters shown in the security badge.']);
                exit;
            }

            if (empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Please provide email and password.']);
                exit;
            }
            $auth = authenticate_client_user($email, $password);
            if ($auth['success']) {
                set_client_user_session($auth['user']);
                echo json_encode([
                    'success' => true,
                    'message' => 'Welcome back, ' . htmlspecialchars($auth['user']['full_name']) . '!',
                    'user' => $auth['user']
                ]);
            } else {
                echo json_encode($auth);
            }
            exit;

        case 'guest_access':
            $ref = strtoupper(trim($input['reference_code'] ?? ($input['guest_id'] ?? '')));
            $passcode = trim($input['passcode'] ?? ($input['phone'] ?? ''));
            $captcha = trim($input['captcha'] ?? '');

            if (!verify_captcha_code($captcha, 'guest')) {
                echo json_encode(['success' => false, 'message' => 'Security Captcha verification failed. Please enter the characters shown in the security badge.']);
                exit;
            }

            if (empty($ref) || empty($passcode)) {
                echo json_encode(['success' => false, 'message' => 'Please provide both Reservation Reference / Guest ID and Passcode / Phone.']);
                exit;
            }
            $auth = authenticate_guest_booking($ref, $passcode);
            if ($auth['success']) {
                set_guest_booking_session($auth['booking']);
                echo json_encode([
                    'success' => true,
                    'message' => 'Guest reservation identified successfully.',
                    'booking' => $auth['booking']
                ]);
            } else {
                echo json_encode($auth);
            }
            exit;

        case 'forgot_password_send_otp':
            $email = strtolower(trim($input['email'] ?? ''));
            $captcha = trim($input['captcha'] ?? '');

            if (!empty($captcha)) {
                if (!verify_captcha_code($captcha, 'guest')) {
                    echo json_encode(['success' => false, 'message' => 'Security Captcha verification failed. Please enter the characters shown in the security badge.']);
                    exit;
                }
            }

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Please provide a valid registered email address.']);
                exit;
            }

            $pdo = get_db();
            ensure_users_and_guest_columns($pdo);
            $u_stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ?");
            $u_stmt->execute([$email]);
            $usr = $u_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usr) {
                echo json_encode(['success' => false, 'message' => 'No Food Forest account found matching this email address.']);
                exit;
            }

            // Generate 5-Digit Verification Code
            $otp = str_pad((string)random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
            $_SESSION['ff_reset_otp'] = $otp;
            $_SESSION['ff_reset_email'] = $email;
            $_SESSION['ff_reset_expiry'] = time() + 600; // 10 minutes

            send_sanctuary_otp_email($email, $otp);

            $is_local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

            $resp = [
                'success' => true,
                'message' => 'A 5-digit password reset code has been sent to ' . htmlspecialchars($email) . '. Valid for 10 minutes.',
                'email' => $email
            ];
            if ($is_local) {
                $resp['dev_otp'] = $otp;
            }
            echo json_encode($resp);
            exit;

        case 'forgot_password_reset':
            $email = strtolower(trim($input['email'] ?? ''));
            $otp = trim($input['otp'] ?? '');
            $new_password = trim($input['new_password'] ?? '');

            $session_otp = $_SESSION['ff_reset_otp'] ?? '';
            $session_email = $_SESSION['ff_reset_email'] ?? '';
            $session_expiry = (int)($_SESSION['ff_reset_expiry'] ?? 0);

            if (empty($session_otp) || empty($session_email)) {
                echo json_encode(['success' => false, 'message' => 'No active password reset request found. Please request a new code.']);
                exit;
            }

            if (time() > $session_expiry) {
                echo json_encode(['success' => false, 'message' => 'Your 5-digit verification code has expired. Please request a new code.']);
                exit;
            }

            if (strcasecmp($email, $session_email) !== 0 || $otp !== (string)$session_otp) {
                echo json_encode(['success' => false, 'message' => 'Invalid 5-digit verification code. Please check your email.']);
                exit;
            }

            if (empty($new_password) || strlen($new_password) < 4) {
                echo json_encode(['success' => false, 'message' => 'New password must be at least 4 characters.']);
                exit;
            }

            // Update password
            $pdo = get_db();
            ensure_users_and_guest_columns($pdo);
            $u_stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            $u_stmt->execute([$new_password, $email]);

            unset($_SESSION['ff_reset_otp']);
            unset($_SESSION['ff_reset_email']);
            unset($_SESSION['ff_reset_expiry']);

            // Auto-login
            $stmt = $pdo->prepare("SELECT id, full_name, email, phone, is_google_verified FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $logged_user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($logged_user) {
                set_client_user_session($logged_user);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Password reset successfully! Loading your Food Forest portal...',
                'user' => $logged_user
            ]);
            exit;

        case 'reg_send_otp':
            $email = strtolower(trim($input['email'] ?? ''));
            $captcha = trim($input['captcha'] ?? '');

            if (!empty($captcha)) {
                if (!verify_captcha_code($captcha, 'guest')) {
                    echo json_encode(['success' => false, 'message' => 'Security Captcha verification failed. Please enter the characters shown in the security badge.']);
                    exit;
                }
            }

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Please provide a valid Google Mail or Email address.']);
                exit;
            }

            // Generate 5-Digit Verification Code
            $otp = str_pad((string)random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
            $_SESSION['ff_reg_otp'] = $otp;
            $_SESSION['ff_reg_email'] = $email;
            $_SESSION['ff_reg_expiry'] = time() + 600; // 10 minutes

            send_sanctuary_otp_email($email, $otp);

            $is_local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

            $resp = [
                'success' => true,
                'message' => 'A 5-digit verification code has been sent to ' . htmlspecialchars($email) . '. Valid for 10 minutes.',
                'email' => $email
            ];
            if ($is_local) {
                $resp['dev_otp'] = $otp;
            }
            echo json_encode($resp);
            exit;

        case 'reg_verify_otp':
            $email = strtolower(trim($input['email'] ?? ''));
            $otp = trim($input['otp'] ?? '');

            $session_otp = $_SESSION['ff_reg_otp'] ?? '';
            $session_email = $_SESSION['ff_reg_email'] ?? '';
            $session_expiry = (int)($_SESSION['ff_reg_expiry'] ?? 0);

            if (empty($session_otp) || empty($session_email)) {
                echo json_encode(['success' => false, 'message' => 'No active verification code pending. Please click Send Code.']);
                exit;
            }

            if (time() > $session_expiry) {
                echo json_encode(['success' => false, 'message' => 'Verification code expired. Please request a new code.']);
                exit;
            }

            if (strcasecmp($email, $session_email) !== 0 || $otp !== (string)$session_otp) {
                echo json_encode(['success' => false, 'message' => 'Invalid 5-digit verification code. Please check your email.']);
                exit;
            }

            // Marked as verified!
            $_SESSION['ff_reg_verified'] = true;
            $_SESSION['ff_reg_verified_email'] = $email;

            echo json_encode([
                'success' => true,
                'message' => 'Email verified successfully! Blue Tick badge unlocked for your profile.',
                'verified' => true
            ]);
            exit;

        case 'register':
            $name = trim($input['full_name'] ?? '');
            $email = strtolower(trim($input['email'] ?? ''));
            $phone = trim($input['phone'] ?? '');
            $password = trim($input['password'] ?? '');
            $captcha = trim($input['captcha'] ?? '');

            if (!verify_captcha_code($captcha, 'guest')) {
                echo json_encode(['success' => false, 'message' => 'Security Captcha verification failed. Please enter the characters shown in the security badge.']);
                exit;
            }

            if (empty($name) || empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Please complete all required fields.']);
                exit;
            }

            // Check if Google OTP was verified (OPTIONAL)
            $is_verified = (!empty($_SESSION['ff_reg_verified']) && strcasecmp($_SESSION['ff_reg_verified_email'] ?? '', $email) === 0) ? 1 : 0;

            $reg = register_client_user($name, $email, $phone, $password, $is_verified);
            if ($reg['success']) {
                unset($_SESSION['ff_reg_verified']);
                unset($_SESSION['ff_reg_verified_email']);
                unset($_SESSION['ff_reg_otp']);

                set_client_user_session($reg['user']);
                echo json_encode([
                    'success' => true,
                    'message' => 'Account created successfully! Welcome to Food Forest.',
                    'user' => $reg['user'],
                    'blue_tick' => ($is_verified === 1)
                ]);
            } else {
                echo json_encode($reg);
            }
            exit;

        case 'upgrade':
            $ref = strtoupper(trim($input['reference_code'] ?? ''));
            $password = trim($input['password'] ?? '');
            if (empty($ref) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Missing booking reference or desired password.']);
                exit;
            }
            $upg = upgrade_guest_to_user($ref, $password);
            if ($upg['success']) {
                // Fetch new user
                $pdo = get_db();
                $u_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $u_stmt->execute([(int)$upg['user_id']]);
                $user = $u_stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    set_client_user_session($user);
                }
                echo json_encode($upg);
            } else {
                echo json_encode($upg);
            }
            exit;

        case 'logout':
            client_logout();
            echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
            exit;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action.']);
            exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
