<?php
// =========================================================================
// Food Forest Sanctuary — Admin & User Profile Management API
// Handles:
// 1. Changing password, username, full name, email, and avatar for active admin
// 2. Listing all admins and users
// 3. Changing password, username, email, and avatar for ANY admin or user
// 4. Creating and deleting admin and user accounts
// =========================================================================

require_once __DIR__ . '/../includes/auth.php';
require_admin_auth();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/upload.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = get_db();
ensure_users_and_guest_columns($pdo);

$current_user = current_admin();
$action = trim($_POST['action'] ?? ($_GET['action'] ?? ''));

try {
    switch ($action) {
        // -----------------------------------------------------------------
        // 1. Fetch Current Logged-in Admin Profile
        // -----------------------------------------------------------------
        case 'get_my_profile':
            $stmt = $pdo->prepare("SELECT id, username, full_name, email, role, avatar_url, last_login, created_at FROM admins WHERE id = ?");
            $stmt->execute([(int)$current_user['id']]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$admin) {
                echo json_encode(['success' => false, 'message' => 'Administrator account not found.']);
                exit;
            }
            echo json_encode(['success' => true, 'profile' => $admin]);
            exit;

        // -----------------------------------------------------------------
        // 2. Update Current Logged-in Admin Profile (Username, Avatar, Password)
        // -----------------------------------------------------------------
        case 'update_my_profile':
            $admin_id = (int)$current_user['id'];
            $username = trim($_POST['username'] ?? '');
            $full_name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (empty($username)) {
                echo json_encode(['success' => false, 'message' => 'Username cannot be empty.']);
                exit;
            }
            if (empty($full_name)) {
                echo json_encode(['success' => false, 'message' => 'Full name cannot be empty.']);
                exit;
            }

            // Check if username taken by another admin
            $chk = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
            $chk->execute([$username, $admin_id]);
            if ($chk->fetch()) {
                echo json_encode(['success' => false, 'message' => "The username '{$username}' is already in use by another admin."]);
                exit;
            }

            // Check if email taken by another admin (if provided)
            if (!empty($email)) {
                $chk_email = $pdo->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
                $chk_email->execute([$email, $admin_id]);
                if ($chk_email->fetch()) {
                    echo json_encode(['success' => false, 'message' => "The email '{$email}' is already in use by another admin."]);
                    exit;
                }
            }

            // Fetch current stored password
            $pwd_stmt = $pdo->prepare("SELECT password_hash, avatar_url FROM admins WHERE id = ?");
            $pwd_stmt->execute([$admin_id]);
            $admin_record = $pwd_stmt->fetch(PDO::FETCH_ASSOC);

            // Handle Password Change if new_password is provided
            $update_password = false;
            $new_password_hash = null;
            if (!empty($new_password) || !empty($current_password)) {
                if (empty($current_password)) {
                    echo json_encode(['success' => false, 'message' => 'Please enter your current password to verify your identity.']);
                    exit;
                }
                $stored_hash = $admin_record['password_hash'] ?? '';
                $is_current_valid = ($current_password === $stored_hash || password_verify($current_password, $stored_hash));
                if (!$is_current_valid) {
                    echo json_encode(['success' => false, 'message' => 'Current password is incorrect. Please double check.']);
                    exit;
                }
                if (strlen($new_password) < 6) {
                    echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long.']);
                    exit;
                }
                if ($new_password !== $confirm_password) {
                    echo json_encode(['success' => false, 'message' => 'New password and confirm password do not match.']);
                    exit;
                }
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update_password = true;
            }

            // Handle Avatar Upload or Removal or Preset
            $avatar_url = $admin_record['avatar_url'] ?? null;
            if (!empty($_POST['remove_avatar']) && $_POST['remove_avatar'] === '1') {
                $avatar_url = null;
            } elseif (!empty($_FILES['avatar_file']['name'])) {
                $upload_res = handle_image_upload($_FILES['avatar_file'], 'admin_avatar', [
                    'max_dimension' => 600,
                    'quality' => 88
                ]);
                if (!$upload_res['success']) {
                    echo json_encode(['success' => false, 'message' => 'Avatar upload failed: ' . $upload_res['error']]);
                    exit;
                }
                $avatar_url = $upload_res['path'];
            } elseif (!empty($_POST['avatar_preset'])) {
                $avatar_url = trim($_POST['avatar_preset']);
            }

            // Perform Update
            if ($update_password) {
                $upd = $pdo->prepare("UPDATE admins SET username = ?, full_name = ?, email = ?, avatar_url = ?, password_hash = ? WHERE id = ?");
                $upd->execute([$username, $full_name, $email, $avatar_url, $new_password_hash, $admin_id]);
            } else {
                $upd = $pdo->prepare("UPDATE admins SET username = ?, full_name = ?, email = ?, avatar_url = ? WHERE id = ?");
                $upd->execute([$username, $full_name, $email, $avatar_url, $admin_id]);
            }

            // Refresh Session
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_name'] = $full_name;
            $_SESSION['admin_email'] = $email;
            $_SESSION['admin_avatar'] = $avatar_url;

            echo json_encode([
                'success' => true,
                'message' => 'Your profile credentials & avatar have been successfully updated!',
                'profile' => [
                    'id' => $admin_id,
                    'username' => $username,
                    'full_name' => $full_name,
                    'email' => $email,
                    'avatar_url' => $avatar_url
                ]
            ]);
            exit;

        // -----------------------------------------------------------------
        // 3. List All Admins and All Users
        // -----------------------------------------------------------------
        case 'get_all_users':
            // 1. Admins
            $admins_stmt = $pdo->query("SELECT id, username, full_name, email, role, avatar_url, last_login, created_at, 'admin' AS account_type FROM admins ORDER BY id ASC");
            $admins_list = $admins_stmt->fetchAll(PDO::FETCH_ASSOC);

            // 2. Client / Staff Users
            $search = trim($_POST['search'] ?? ($_GET['search'] ?? ''));
            if (!empty($search)) {
                $u_query = "SELECT id, username, full_name, email, phone, avatar_url, is_google_verified, is_verified, status, admin_notes, last_login, created_at, 'user' AS account_type FROM users WHERE phone LIKE ? OR full_name LIKE ? OR email LIKE ? ORDER BY id DESC";
                $like = "%{$search}%";
                $users_stmt = $pdo->prepare($u_query);
                $users_stmt->execute([$like, $like, $like]);
            } else {
                $users_stmt = $pdo->query("SELECT id, username, full_name, email, phone, avatar_url, is_google_verified, is_verified, status, admin_notes, last_login, created_at, 'user' AS account_type FROM users ORDER BY id DESC");
            }
            $users_list = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'current_admin_id' => (int)$current_user['id'],
                'admins' => $admins_list,
                'users' => $users_list,
                'total_admins' => count($admins_list),
                'total_users' => count($users_list)
            ]);
            exit;

        // -----------------------------------------------------------------
        // 3b. Toggle User Blue Tick Verification Status
        // -----------------------------------------------------------------
        case 'toggle_user_verify':
            $user_id = (int)($_POST['user_id'] ?? 0);
            if ($user_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
                exit;
            }
            $chk = $pdo->prepare("SELECT is_verified, full_name FROM users WHERE id = ?");
            $chk->execute([$user_id]);
            $u_row = $chk->fetch(PDO::FETCH_ASSOC);
            if (!$u_row) {
                echo json_encode(['success' => false, 'message' => 'User profile not found.']);
                exit;
            }
            $new_verified = ($u_row['is_verified'] == 1) ? 0 : 1;
            $upd = $pdo->prepare("UPDATE users SET is_verified = ?, verified_at = " . ($new_verified ? "NOW()" : "NULL") . " WHERE id = ?");
            $upd->execute([$new_verified, $user_id]);
            echo json_encode([
                'success' => true,
                'is_verified' => $new_verified,
                'message' => ($new_verified ? "Verified blue tick granted to '{$u_row['full_name']}'." : "Verification blue tick removed from '{$u_row['full_name']}'.")
            ]);
            exit;

        // -----------------------------------------------------------------
        // 3c. Toggle User Active / Disabled Status
        // -----------------------------------------------------------------
        case 'toggle_user_status':
            $user_id = (int)($_POST['user_id'] ?? 0);
            if ($user_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
                exit;
            }
            $chk = $pdo->prepare("SELECT status, full_name FROM users WHERE id = ?");
            $chk->execute([$user_id]);
            $u_row = $chk->fetch(PDO::FETCH_ASSOC);
            if (!$u_row) {
                echo json_encode(['success' => false, 'message' => 'User profile not found.']);
                exit;
            }
            $new_status = ($u_row['status'] == 1) ? 0 : 1;
            $upd = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
            $upd->execute([$new_status, $user_id]);
            echo json_encode([
                'success' => true,
                'status' => $new_status,
                'message' => ($new_status ? "Profile for '{$u_row['full_name']}' is now ACTIVE." : "Profile for '{$u_row['full_name']}' is now DISABLED.")
            ]);
            exit;

        // -----------------------------------------------------------------
        // 3d. Save Admin Notes / Guest Feedback for Profile
        // -----------------------------------------------------------------
        case 'save_user_notes':
            $user_id = (int)($_POST['user_id'] ?? 0);
            $notes = trim($_POST['admin_notes'] ?? '');
            if ($user_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
                exit;
            }
            $upd = $pdo->prepare("UPDATE users SET admin_notes = ? WHERE id = ?");
            $upd->execute([$notes, $user_id]);
            echo json_encode([
                'success' => true,
                'message' => 'Internal concierge feedback & guest notes saved successfully.'
            ]);
            exit;

        // -----------------------------------------------------------------
        // 3e. Reset User Password
        // -----------------------------------------------------------------
        case 'reset_user_password':
            $user_id = (int)($_POST['user_id'] ?? 0);
            $new_pwd = $_POST['new_password'] ?? '';
            if ($user_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
                exit;
            }
            if (empty($new_pwd) || strlen($new_pwd) < 4) {
                echo json_encode(['success' => false, 'message' => 'New password must be at least 4 characters long.']);
                exit;
            }
            $hash = password_hash($new_pwd, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $upd->execute([$hash, $user_id]);
            echo json_encode([
                'success' => true,
                'message' => 'Password for this user profile has been reset successfully.'
            ]);
            exit;

        // -----------------------------------------------------------------
        // 4. Save User or Admin (Edit Existing or Create New)
        // -----------------------------------------------------------------
        case 'save_user':
            $target_type = trim($_POST['target_type'] ?? 'user'); // 'admin' or 'user'
            $target_id = (int)($_POST['target_id'] ?? 0);
            $full_name = trim($_POST['full_name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $role = trim($_POST['role'] ?? 'Concierge Lead');
            $new_password = $_POST['new_password'] ?? '';

            if (empty($full_name)) {
                echo json_encode(['success' => false, 'message' => 'Full Name is required.']);
                exit;
            }

            if ($target_type === 'admin') {
                if (empty($username)) {
                    echo json_encode(['success' => false, 'message' => 'Username is required for Admin accounts.']);
                    exit;
                }
                // Check username collision
                $chk_u = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
                $chk_u->execute([$username, $target_id]);
                if ($chk_u->fetch()) {
                    echo json_encode(['success' => false, 'message' => "The username '{$username}' is already in use by another admin."]);
                    exit;
                }

                // Check email collision
                if (!empty($email)) {
                    $chk_e = $pdo->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
                    $chk_e->execute([$email, $target_id]);
                    if ($chk_e->fetch()) {
                        echo json_encode(['success' => false, 'message' => "The email '{$email}' is already in use by another admin."]);
                        exit;
                    }
                }

                // Handle Avatar
                $avatar_url = null;
                if ($target_id > 0) {
                    $cur_av = $pdo->prepare("SELECT avatar_url FROM admins WHERE id = ?");
                    $cur_av->execute([$target_id]);
                    $avatar_url = $cur_av->fetchColumn();
                }
                if (!empty($_POST['remove_avatar']) && $_POST['remove_avatar'] === '1') {
                    $avatar_url = null;
                } elseif (!empty($_FILES['avatar_file']['name'])) {
                    $up = handle_image_upload($_FILES['avatar_file'], 'admin_avatar', ['max_dimension' => 600, 'quality' => 88]);
                    if ($up['success']) $avatar_url = $up['path'];
                } elseif (!empty($_POST['avatar_preset'])) {
                    $avatar_url = trim($_POST['avatar_preset']);
                }

                if ($target_id > 0) {
                    // Update existing admin
                    if (!empty($new_password)) {
                        if (strlen($new_password) < 6) {
                            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
                            exit;
                        }
                        $hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE admins SET full_name = ?, username = ?, email = ?, role = ?, avatar_url = ?, password_hash = ? WHERE id = ?");
                        $stmt->execute([$full_name, $username, $email, $role, $avatar_url, $hash, $target_id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE admins SET full_name = ?, username = ?, email = ?, role = ?, avatar_url = ? WHERE id = ?");
                        $stmt->execute([$full_name, $username, $email, $role, $avatar_url, $target_id]);
                    }
                    $msg = "Administrator '{$username}' updated successfully.";
                } else {
                    // Create new admin
                    if (empty($new_password) || strlen($new_password) < 6) {
                        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters for a new account.']);
                        exit;
                    }
                    $hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO admins (full_name, username, email, role, avatar_url, password_hash) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$full_name, $username, $email, $role, $avatar_url, $hash]);
                    $msg = "New administrator '{$username}' created successfully.";
                }

                // If editing self, update active session
                if ($target_id === (int)$current_user['id']) {
                    $_SESSION['admin_username'] = $username;
                    $_SESSION['admin_name'] = $full_name;
                    $_SESSION['admin_email'] = $email;
                    $_SESSION['admin_role'] = $role;
                    $_SESSION['admin_avatar'] = $avatar_url;
                }

            } else {
                // User account (table `users`)
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo json_encode(['success' => false, 'message' => 'A valid Email address is required for user accounts.']);
                    exit;
                }

                // Check email collision in users table
                $chk_e = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $chk_e->execute([$email, $target_id]);
                if ($chk_e->fetch()) {
                    echo json_encode(['success' => false, 'message' => "The email '{$email}' is already registered to another user account."]);
                    exit;
                }

                // Handle Username for user if provided
                if (!empty($username)) {
                    $chk_u = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                    $chk_u->execute([$username, $target_id]);
                    if ($chk_u->fetch()) {
                        echo json_encode(['success' => false, 'message' => "The username '{$username}' is already in use by another user."]);
                        exit;
                    }
                } else {
                    // Default username to email prefix
                    $username = explode('@', $email)[0];
                }

                // Handle Avatar
                $avatar_url = null;
                if ($target_id > 0) {
                    $cur_av = $pdo->prepare("SELECT avatar_url FROM users WHERE id = ?");
                    $cur_av->execute([$target_id]);
                    $avatar_url = $cur_av->fetchColumn();
                }
                if (!empty($_POST['remove_avatar']) && $_POST['remove_avatar'] === '1') {
                    $avatar_url = null;
                } elseif (!empty($_FILES['avatar_file']['name'])) {
                    $up = handle_image_upload($_FILES['avatar_file'], 'user_avatar', ['max_dimension' => 600, 'quality' => 88]);
                    if ($up['success']) $avatar_url = $up['path'];
                } elseif (!empty($_POST['avatar_preset'])) {
                    $avatar_url = trim($_POST['avatar_preset']);
                }

                if ($target_id > 0) {
                    // Update user
                    if (!empty($new_password)) {
                        if (strlen($new_password) < 6) {
                            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
                            exit;
                        }
                        $hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, email = ?, phone = ?, avatar_url = ?, password_hash = ? WHERE id = ?");
                        $stmt->execute([$full_name, $username, $email, $phone, $avatar_url, $hash, $target_id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, email = ?, phone = ?, avatar_url = ? WHERE id = ?");
                        $stmt->execute([$full_name, $username, $email, $phone, $avatar_url, $target_id]);
                    }
                    $msg = "User '{$full_name}' updated successfully.";
                } else {
                    // Create new user
                    if (empty($new_password) || strlen($new_password) < 6) {
                        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters for a new account.']);
                        exit;
                    }
                    $hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, phone, avatar_url, password_hash) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$full_name, $username, $email, $phone, $avatar_url, $hash]);
                    $msg = "New user '{$full_name}' created successfully.";
                }
            }

            echo json_encode(['success' => true, 'message' => $msg]);
            exit;

        // -----------------------------------------------------------------
        // 5. Delete User or Admin
        // -----------------------------------------------------------------
        case 'delete_user':
            $target_type = trim($_POST['target_type'] ?? 'user');
            $target_id = (int)($_POST['target_id'] ?? 0);

            if ($target_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid account ID.']);
                exit;
            }

            if ($target_type === 'admin') {
                if ($target_id === (int)$current_user['id']) {
                    echo json_encode(['success' => false, 'message' => 'You cannot delete your own active administrator account!']);
                    exit;
                }
                $admin_count = (int)$pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
                if ($admin_count <= 1) {
                    echo json_encode(['success' => false, 'message' => 'Cannot delete the only remaining administrator account in the system.']);
                    exit;
                }
                $del = $pdo->prepare("DELETE FROM admins WHERE id = ?");
                $del->execute([$target_id]);
                echo json_encode(['success' => true, 'message' => 'Administrator account successfully deleted.']);
                exit;
            } else {
                $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $del->execute([$target_id]);
                echo json_encode(['success' => true, 'message' => 'User account successfully deleted.']);
                exit;
            }

        default:
            echo json_encode(['success' => false, 'message' => 'Unrecognized action request.']);
            exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
    exit;
}
