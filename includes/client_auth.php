<?php
// =========================================================================
// Food Forest Sanctuary — Client & Guest Authentication Session Handler
// =========================================================================

require_once __DIR__ . '/../admin/includes/db.php';

function client_session_start() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Check if a permanent client user is currently logged in
 */
function is_client_user_logged_in() {
    client_session_start();
    return !empty($_SESSION['ff_client_user_id']);
}

/**
 * Get current logged in client user details
 */
function get_logged_in_client_user() {
    client_session_start();
    if (!is_client_user_logged_in()) {
        return null;
    }
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare("SELECT id, full_name, email, phone, is_google_verified, created_at, last_login FROM users WHERE id = ?");
        $stmt->execute([(int)$_SESSION['ff_client_user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Check if an active 30-day guest session is present
 */
function is_guest_booking_session_active() {
    client_session_start();
    if (empty($_SESSION['ff_guest_booking_ref'])) {
        return false;
    }
    $booking = get_booking_by_ref($_SESSION['ff_guest_booking_ref']);
    if (!$booking) {
        return false;
    }
    if (!empty($booking['expires_at']) && strtotime($booking['expires_at']) < time()) {
        unset($_SESSION['ff_guest_booking_ref']);
        return false;
    }
    return true;
}

/**
 * Get current active guest booking
 */
function get_active_guest_booking() {
    client_session_start();
    if (empty($_SESSION['ff_guest_booking_ref'])) {
        return null;
    }
    $booking = get_booking_by_ref($_SESSION['ff_guest_booking_ref']);
    if ($booking && !empty($booking['expires_at']) && strtotime($booking['expires_at']) < time()) {
        unset($_SESSION['ff_guest_booking_ref']);
        return null;
    }
    return $booking;
}

/**
 * Set client user session
 */
function set_client_user_session($user) {
    client_session_start();
    $_SESSION['ff_client_user_id'] = (int)$user['id'];
    $_SESSION['ff_client_user_name'] = $user['full_name'];
    $_SESSION['ff_client_user_email'] = $user['email'];
    $_SESSION['ff_client_last_activity'] = time();
    unset($_SESSION['ff_guest_booking_ref']);
}

/**
 * Set guest booking session
 */
function set_guest_booking_session($booking) {
    client_session_start();
    $_SESSION['ff_guest_booking_ref'] = $booking['reference_code'];
    $_SESSION['ff_client_last_activity'] = time();
}

/**
 * Clear all client / guest sessions
 */
function client_logout() {
    client_session_start();
    unset($_SESSION['ff_client_user_id']);
    unset($_SESSION['ff_client_user_name']);
    unset($_SESSION['ff_client_user_email']);
    unset($_SESSION['ff_guest_booking_ref']);
    unset($_SESSION['ff_client_last_activity']);
}

/**
 * Enforce 5-minute (300 seconds) inactivity timeout for client/guest sessions
 */
function check_client_inactivity_timeout() {
    client_session_start();
    if (!empty($_SESSION['ff_client_user_id']) || !empty($_SESSION['ff_guest_booking_ref'])) {
        $now = time();
        $timeout_seconds = 300; // 5 minutes
        if (isset($_SESSION['ff_client_last_activity']) && ($now - $_SESSION['ff_client_last_activity'] > $timeout_seconds)) {
            client_logout();
            $_SESSION['ff_session_timeout'] = true;
            return true;
        }
        $_SESSION['ff_client_last_activity'] = $now;
    }
    return false;
}

/**
 * Verifies captcha code against session
 */
function verify_captcha_code($input_code, $type = 'guest') {
    client_session_start();
    $expected = $_SESSION['captcha_' . $type] ?? '';
    if (empty($expected)) return false;
    $valid = (strcasecmp(trim($input_code), trim($expected)) === 0);
    // Unset on check to prevent replay
    unset($_SESSION['captcha_' . $type]);
    return $valid;
}

/**
 * Dispatch luxury HTML email with 5-digit verification OTP code
 */
function send_sanctuary_otp_email($recipient_email, $otp_code) {
    $subject = "Your Food Forest Sign-In Code: " . $otp_code;
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: Food Forest Sanctuary <concierge@foodforestkanthalloor.com>',
        'Reply-To: concierge@foodforestkanthalloor.com',
        'X-Mailer: PHP/' . phpversion()
    ];

    $body = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Sign-In Verification Code</title>
</head>
<body style="margin:0;padding:30px 15px;background-color:#08100B;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#EAEFED;">
  <table align="center" width="100%" cellpadding="0" cellspacing="0" style="max-width:540px;margin:0 auto;background:#101F15;border:1px solid #C5A059;border-radius:16px;overflow:hidden;box-shadow:0 15px 35px rgba(0,0,0,0.6);">
    <tr>
      <td style="padding:32px 30px 20px;text-align:center;background:linear-gradient(180deg,#15291C 0%,#101F15 100%);border-bottom:1px solid rgba(197,160,89,0.2);">
        <div style="font-size:26px;color:#C5A059;font-weight:700;letter-spacing:3px;font-family:Georgia,serif;">FOOD FOREST</div>
        <div style="font-size:11px;color:#A1B5A9;letter-spacing:4px;text-transform:uppercase;margin-top:4px;">KANTHALLOOR &bull; RESIDENT GUEST PORTAL</div>
      </td>
    </tr>
    <tr>
      <td style="padding:32px 30px;text-align:center;">
        <h2 style="font-size:20px;color:#FFFFFF;margin:0 0 10px;font-family:Georgia,serif;font-weight:600;">Sign-In Verification Code</h2>
        <p style="font-size:13.5px;color:#94A3B8;line-height:1.6;margin:0 0 24px;">
          Use the following 5-digit verification code to securely access your resident guest portal and reservations:
        </p>
        <div style="display:inline-block;padding:16px 36px;background:#09130D;border:2px solid #C5A059;border-radius:12px;font-size:36px;font-weight:800;letter-spacing:14px;color:#F0D59D;font-family:monospace;box-shadow:0 0 20px rgba(197,160,89,0.25);">
          {$otp_code}
        </div>
        <p style="font-size:12px;color:#64748B;margin:24px 0 0;line-height:1.5;">
          This verification code is valid for <strong>10 minutes</strong>.<br>If you did not request this login code, you can safely ignore this email.
        </p>
      </td>
    </tr>
    <tr>
      <td style="padding:20px 30px;background:#08100B;border-top:1px solid rgba(255,255,255,0.06);text-align:center;font-size:11px;color:#64748B;">
        Food Forest Eco Sanctuary &bull; Kanthalloor High Range, Kerala<br>
        24&times;7 Concierge WhatsApp: +91 92345 67890
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

    // Log OTP to file for audit & localhost development
    $log_dir = __DIR__ . '/../admin/data';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0777, true);
    }
    @file_put_contents(
        $log_dir . '/otp_mail_log.txt',
        "[" . date('Y-m-d H:i:s') . "] To: {$recipient_email} | OTP: {$otp_code}\n",
        FILE_APPEND
    );

    // Attempt PHP native mail
    $mail_sent = false;
    try {
        $mail_sent = @mail($recipient_email, $subject, $body, implode("\r\n", $headers));
    } catch (Exception $e) {
        $mail_sent = false;
    }

    return $mail_sent;
}
