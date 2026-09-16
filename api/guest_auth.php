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

        case 'login':
            $email = trim($input['email'] ?? '');
            $password = trim($input['password'] ?? '');
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

        case 'register':
            $name = trim($input['full_name'] ?? '');
            $email = trim($input['email'] ?? '');
            $phone = trim($input['phone'] ?? '');
            $password = trim($input['password'] ?? '');

            if (empty($name) || empty($email) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Please complete all required fields.']);
                exit;
            }

            $reg = register_client_user($name, $email, $phone, $password);
            if ($reg['success']) {
                set_client_user_session($reg['user']);
                echo json_encode([
                    'success' => true,
                    'message' => 'Account created successfully! Welcome to Food Forest Sanctuary.',
                    'user' => $reg['user']
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
