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
        $stmt = $pdo->prepare("SELECT id, full_name, email, phone, created_at, last_login FROM users WHERE id = ?");
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
    unset($_SESSION['ff_guest_booking_ref']);
}

/**
 * Set guest booking session
 */
function set_guest_booking_session($booking) {
    client_session_start();
    $_SESSION['ff_guest_booking_ref'] = $booking['reference_code'];
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
}
