<?php
// =========================================================================
// Food Forest Sanctuary — In-Cottage Food Ordering API
// Endpoint: POST /api/order_food.php
// =========================================================================

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../admin/includes/db.php';
require_once __DIR__ . '/../includes/client_auth.php';

client_session_start();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$action = trim($input['action'] ?? 'order');

try {
    $pdo = get_db();
    ensure_food_menu_table_exists($pdo);
    ensure_users_and_guest_columns($pdo);
    ensure_booking_gst_columns($pdo);

    if ($action === 'get_menu') {
        // Return full active food menu grouped by category
        $stmt = $pdo->query("SELECT id, category, heading, subtitle, price, dietary_type, default_meal_time, inclusions, is_spicy, prep_time_minutes 
                             FROM food_menu 
                             WHERE is_active = 1 
                             ORDER BY category ASC, heading ASC");
        $dishes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($dishes as &$d) {
            $d['price'] = (float)$d['price'];
            $d['inclusions'] = !empty($d['inclusions']) ? json_decode($d['inclusions'], true) : [];
        }
        echo json_encode(['success' => true, 'menu' => $dishes]);
        exit;
    }

    if ($action !== 'order' && $action !== 'quick_add' && $action !== 'batch_order') {
        echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
        exit;
    }

    // 1. Resolve Booking
    $booking_id = (int)($input['booking_id'] ?? 0);
    $ref_code = strtoupper(trim($input['reference_code'] ?? ''));

    if ($booking_id <= 0 && empty($ref_code)) {
        echo json_encode(['success' => false, 'message' => 'Reservation reference code or booking ID is required.']);
        exit;
    }

    if ($booking_id > 0) {
        $bstmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
        $bstmt->execute([$booking_id]);
    } else {
        $bstmt = $pdo->prepare("SELECT * FROM bookings WHERE reference_code = ?");
        $bstmt->execute([$ref_code]);
    }
    $booking = $bstmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Reservation record not found.']);
        exit;
    }

    // 2. Authentication & Authorization Check
    $is_admin = (!empty($_SESSION['admin_id']) || !empty($_SESSION['admin_user'])) && !empty($_SESSION['admin_logged_in']);
    $is_member = is_client_user_logged_in();
    $current_user = $is_member ? get_logged_in_client_user() : null;
    $is_guest_session = is_guest_booking_session_active();
    $session_guest_booking = $is_guest_session ? get_active_guest_booking() : null;

    $authorized = false;

    if ($is_admin) {
        $authorized = true;
    } elseif ($is_member && $current_user) {
        if ((int)($booking['user_id'] ?? 0) === (int)$current_user['id']) {
            $authorized = true;
        } elseif (!empty($booking['guest_email']) && !empty($current_user['email']) && strcasecmp(trim($booking['guest_email']), trim($current_user['email'])) === 0) {
            $authorized = true;
            // Auto link booking to user
            try {
                $pdo->prepare("UPDATE bookings SET user_id = ? WHERE id = ?")->execute([(int)$current_user['id'], $booking['id']]);
            } catch (Exception $e) {}
        }
    } elseif ($is_guest_session && $session_guest_booking && strcasecmp($session_guest_booking['reference_code'], $booking['reference_code']) === 0) {
        $authorized = true;
    } else {
        // Also allow if passcode or phone was supplied directly in payload
        $passcode = trim($input['passcode'] ?? ($input['phone'] ?? ''));
        if (!empty($passcode)) {
            $auth = authenticate_guest_booking($booking['reference_code'], $passcode);
            if ($auth['success']) {
                $authorized = true;
            }
        }
    }

    if (!$authorized) {
        echo json_encode([
            'success' => false, 
            'message' => 'Authorization required to place orders for this reservation. Please log in or verify your passcode.'
        ]);
        exit;
    }

    if ($booking['status'] === 'cancelled') {
        echo json_encode(['success' => false, 'message' => 'Cannot place dining orders for a cancelled reservation.']);
        exit;
    }

    // 3. Security Captcha Challenge (Mandatory for Guests, Bypassed for Admin)
    if (!$is_admin) {
        $captcha = trim($input['captcha'] ?? '');
        if (empty($captcha)) {
            echo json_encode(['success' => false, 'message' => 'Security Captcha verification is required to confirm your dining order.']);
            exit;
        }
        if (!verify_captcha_code($captcha, 'order') && !verify_captcha_code($captcha, 'guest')) {
            echo json_encode(['success' => false, 'message' => 'Security Captcha verification failed. Please enter the characters shown in the security badge.']);
            exit;
        }
    }

    // 4. Resolve Dish Items (Batch or Single)
    $incoming_items = [];
    if (!empty($input['items']) && is_array($input['items'])) {
        $incoming_items = $input['items'];
    } elseif (!empty($input['dish_id'])) {
        $incoming_items[] = [
            'dish_id' => (int)$input['dish_id'],
            'quantity' => (int)($input['quantity'] ?? ($input['qty'] ?? 1)),
            'meal_time' => $input['meal_time'] ?? 'lunch',
            'special_notes' => $input['special_notes'] ?? ($input['notes'] ?? '')
        ];
    }

    if (empty($incoming_items)) {
        echo json_encode(['success' => false, 'message' => 'No dining dishes specified in the order.']);
        exit;
    }

    $f_items = !empty($booking['food_items']) ? json_decode($booking['food_items'], true) : [];
    if (!is_array($f_items)) $f_items = [];

    $dstmt = $pdo->prepare("SELECT * FROM food_menu WHERE id = ? AND is_active = 1");
    $items_added_count = 0;
    $total_added_qty = 0;
    $summary_names = [];

    foreach ($incoming_items as $itm) {
        $d_id = (int)($itm['dish_id'] ?? 0);
        $qty = max(1, min(50, (int)($itm['quantity'] ?? ($itm['qty'] ?? 1))));
        $notes = trim($itm['special_notes'] ?? ($itm['notes'] ?? ''));

        if ($d_id <= 0) continue;

        $dstmt->execute([$d_id]);
        $dish = $dstmt->fetch(PDO::FETCH_ASSOC);
        if (!$dish) continue;

        $meal_time = strtolower(trim($itm['meal_time'] ?? ($dish['default_meal_time'] ?? 'lunch')));
        if (strpos($meal_time, 'snack') !== false || strpos($meal_time, 'tea') !== false) {
            $meal_time = 'snacks';
        } elseif (strpos($meal_time, 'break') !== false || strpos($meal_time, 'morn') !== false) {
            $meal_time = 'breakfast';
        } elseif (strpos($meal_time, 'din') !== false || strpos($meal_time, 'night') !== false) {
            $meal_time = 'dinner';
        } elseif (strpos($meal_time, 'lunch') !== false || strpos($meal_time, 'noon') !== false) {
            $meal_time = 'lunch';
        }
        if (!in_array($meal_time, ['breakfast', 'lunch', 'snacks', 'dinner'])) {
            $meal_time = 'lunch';
        }

        $price = (float)$dish['price'];
        $subtotal = $qty * $price;
        $dish_inclusions = !empty($dish['inclusions']) ? json_decode($dish['inclusions'], true) : [];
        if (!is_array($dish_inclusions)) $dish_inclusions = [];

        $f_items[] = [
            'id' => (int)$dish['id'],
            'category' => $dish['category'],
            'category_title' => ucfirst($dish['category']),
            'heading' => $dish['heading'],
            'subtitle' => $dish['subtitle'] ?? '',
            'price' => $price,
            'quantity' => $qty,
            'subtotal' => $subtotal,
            'meal_time' => $meal_time,
            'dietary_type' => $dish['dietary_type'] ?? 'veg',
            'inclusions' => $dish_inclusions,
            'special_notes' => $notes,
            'ordered_at' => date('Y-m-d H:i:s'),
            'served' => false
        ];

        $items_added_count++;
        $total_added_qty += $qty;
        if (!in_array($dish['heading'], $summary_names)) {
            $summary_names[] = $dish['heading'];
        }
    }

    if ($items_added_count === 0) {
        echo json_encode(['success' => false, 'message' => 'Selected dishes could not be found or are inactive.']);
        exit;
    }

    $new_food_total = 0.00;
    foreach ($f_items as $fi) {
        $new_food_total += (float)($fi['subtotal'] ?? (($fi['price'] ?? 0) * ($fi['quantity'] ?? 1)));
    }

    // Recalculate 5% GST and Total Amount
    $room_amt = (float)($booking['room_amount'] ?? 0);
    $extra_chg = (float)($booking['extra_charges'] ?? 0);
    $disc_amt = (float)($booking['discount_amount'] ?? 0);
    $adv_paid = (float)($booking['advance_paid'] ?? 0);

    $acts = !empty($booking['activities_json']) ? json_decode($booking['activities_json'], true) : [];
    $act_total = 0.00;
    if (is_array($acts)) {
        foreach ($acts as $act) {
            $act_total += (float)($act['subtotal'] ?? (($act['price'] ?? 0) * ($act['quantity'] ?? 1)));
        }
    }

    $cust_items = !empty($booking['billing_items_json']) ? json_decode($booking['billing_items_json'], true) : [];
    $cust_total = 0.00;
    if (is_array($cust_items)) {
        foreach ($cust_items as $ci) {
            if (is_array($ci) && isset($ci['subtotal'])) $cust_total += (float)$ci['subtotal'];
        }
    }

    $gross = $room_amt + $new_food_total + $act_total + $cust_total + $extra_chg;
    $taxable = max(0, $gross - $disc_amt);
    $gst_pct = (float)($booking['gst_percentage'] > 0 ? $booking['gst_percentage'] : 5.00);
    $new_gst = round($taxable * ($gst_pct / 100), 2);
    $new_grand_total = round($taxable + $new_gst, 2);
    $new_balance_due = max(0, $new_grand_total - $adv_paid);

    $upd = $pdo->prepare("UPDATE bookings 
                          SET food_items = ?, 
                              food_amount = ?, 
                              food_status = 'selected', 
                              gst_amount = ?, 
                              tax_amount = ?, 
                              total_amount = ? 
                          WHERE id = ?");
    $upd->execute([
        json_encode($f_items, JSON_UNESCAPED_UNICODE),
        $new_food_total,
        $new_gst,
        $new_gst,
        $new_grand_total,
        $booking['id']
    ]);

    // Return detailed breakdown for instant cart UI update
    echo json_encode([
        'success' => true,
        'message' => 'Successfully ordered ' . $qty . 'x ' . htmlspecialchars($dish['heading']) . ' (' . ucfirst($meal_time) . ') to your cottage! The estate kitchen has received your order.',
        'food_total' => $new_food_total,
        'grand_total' => $new_grand_total,
        'balance_due' => $new_balance_due,
        'food_count' => count($f_items),
        'food_items' => $f_items,
        'cgst_amount' => round($new_gst / 2, 2),
        'sgst_amount' => round($new_gst / 2, 2),
        'gst_amount' => $new_gst,
        'ordered_item' => [
            'id' => (int)$dish['id'],
            'heading' => $dish['heading'],
            'price' => $price,
            'quantity' => $qty,
            'subtotal' => $subtotal,
            'meal_time' => $meal_time
        ],
        'cart_summary' => [
            'room_amount' => $room_amt,
            'food_total' => $new_food_total,
            'activities_total' => $act_total,
            'custom_total' => $cust_total,
            'extra_charges' => $extra_chg,
            'discount_amount' => $disc_amt,
            'taxable_subtotal' => $taxable,
            'gst_percentage' => $gst_pct,
            'cgst_amount' => round($new_gst / 2, 2),
            'sgst_amount' => round($new_gst / 2, 2),
            'gst_amount' => $new_gst,
            'grand_total' => $new_grand_total,
            'advance_paid' => $adv_paid,
            'balance_due' => $new_balance_due,
            'dishes_count' => count($f_items)
        ]
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error while processing dining order: ' . $e->getMessage()
    ]);
    exit;
}
