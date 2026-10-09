<?php
// =========================================================================
// Food Forest Sanctuary — In-Cottage Food Ordering API
// Endpoint: POST /api/order_food.php
// =========================================================================

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../admin/includes/db.php';
require_once __DIR__ . '/../admin/includes/auth.php';
require_once __DIR__ . '/../includes/client_auth.php';

client_session_start();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$action = trim($input['action'] ?? ($_GET['action'] ?? 'order'));

try {
    $pdo = get_db();
    ensure_food_menu_table_exists($pdo);
    ensure_users_and_guest_columns($pdo);
    ensure_booking_gst_columns($pdo);

    if ($action === 'get_menu') {
        $food_ordering_enabled = get_setting('food_ordering_enabled', '1');
        if (($food_ordering_enabled === '0' || $food_ordering_enabled === 'false') && !is_admin_logged_in()) {
            echo json_encode(['success' => false, 'ordering_disabled' => true, 'menu' => [], 'message' => 'Guest food ordering is currently paused by estate concierge.']);
            exit;
        }

        // Return full active food menu grouped by category
        $stmt = $pdo->query("SELECT id, category, heading, subtitle, price, dietary_type, default_meal_time, inclusions 
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

    if ($action !== 'order' && $action !== 'quick_add' && $action !== 'batch_order' && $action !== 'cancel_item' && $action !== 'cancel_order' && $action !== 'update_food_status' && $action !== 'update_status') {
        echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
        exit;
    }

    // Check if in-cottage food ordering is enabled on the estate for guests
    if (in_array($action, ['order', 'quick_add', 'batch_order']) && !is_admin_logged_in()) {
        $food_ordering_enabled = get_setting('food_ordering_enabled', '1');
        if ($food_ordering_enabled === '0' || $food_ordering_enabled === 'false') {
            echo json_encode([
                'success' => false,
                'ordering_disabled' => true,
                'message' => 'Guest in-cottage food ordering is currently disabled by estate management. Please contact the front desk or concierge for dining requests.'
            ]);
            exit;
        }
    }

    // 1. Resolve Booking
    $booking_id = (int)($input['booking_id'] ?? ($_GET['booking_id'] ?? 0));
    $ref_code = strtoupper(trim($input['reference_code'] ?? ($_GET['reference_code'] ?? '')));

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
    $is_admin = is_admin_logged_in();
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
            'message' => 'Authorization required to manage orders for this reservation. Please log in or verify your passcode.'
        ]);
        exit;
    }

    if ($booking['status'] === 'cancelled') {
        echo json_encode(['success' => false, 'message' => 'Cannot modify dining orders for a cancelled reservation.']);
        exit;
    }

    // 3. Handle Admin Food Status Update (update_food_status or update_status)
    if ($action === 'update_food_status' || $action === 'update_status') {
        if (!$is_admin) {
            echo json_encode(['success' => false, 'message' => 'Administrator authorization required to update kitchen order status.']);
            exit;
        }

        $new_status = strtolower(trim($input['new_food_status'] ?? ($input['new_status'] ?? ($input['status'] ?? 'selected'))));
        $valid_statuses = ['selected', 'preparing', 'ready', 'served', 'cancelled'];
        if (!in_array($new_status, $valid_statuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid kitchen status code specified.']);
            exit;
        }

        $f_items = !empty($booking['food_items']) ? json_decode($booking['food_items'], true) : [];
        if (!is_array($f_items)) $f_items = [];

        $item_idx = isset($input['item_index']) ? (int)$input['item_index'] : -1;

        if ($item_idx >= 0 && isset($f_items[$item_idx])) {
            // Update individual item
            $f_items[$item_idx]['status'] = $new_status;
            $f_items[$item_idx]['served'] = ($new_status === 'served');

            // Aggregate overall status
            $all_served = true;
            $any_preparing = false;
            $any_ready = false;
            foreach ($f_items as $chk_fi) {
                $st = strtolower(trim($chk_fi['status'] ?? ''));
                if (empty($chk_fi['served']) && $st !== 'served') $all_served = false;
                if ($st === 'preparing') $any_preparing = true;
                if ($st === 'ready') $any_ready = true;
            }

            if ($all_served && !empty($f_items)) {
                $overall_status = 'served';
            } elseif ($any_ready) {
                $overall_status = 'ready';
            } elseif ($any_preparing) {
                $overall_status = 'preparing';
            } else {
                $overall_status = $booking['food_status'] ?? 'selected';
            }
        } else {
            // Update entire ticket
            $overall_status = $new_status;
            foreach ($f_items as &$fi) {
                $fi['status'] = $new_status;
                $fi['served'] = ($new_status === 'served');
            }
            unset($fi);
        }

        $upd = $pdo->prepare("UPDATE bookings SET food_status = ?, food_items = ? WHERE id = ?");
        $upd->execute([
            $overall_status,
            !empty($f_items) ? json_encode($f_items, JSON_UNESCAPED_UNICODE) : null,
            $booking['id']
        ]);

        $status_labels = [
            'selected' => 'Order Placed (Queued)',
            'preparing' => 'Preparing to Cook',
            'ready' => 'Ready to Serve',
            'served' => 'Delivered / Served',
            'cancelled' => 'Cancelled'
        ];
        $label = $status_labels[$overall_status] ?? ucfirst($overall_status);

        echo json_encode([
            'success' => true,
            'message' => "Kitchen status updated to '{$label}'.",
            'food_status' => $overall_status,
            'food_status_label' => $label,
            'food_items' => $f_items,
            'booking_id' => $booking['id'],
            'reference_code' => $booking['reference_code']
        ]);
        exit;
    }

    // 4. Handle Order Cancellation Actions (cancel_item or cancel_order)
    if ($action === 'cancel_item' || $action === 'cancel_order') {
        $f_items = !empty($booking['food_items']) ? json_decode($booking['food_items'], true) : [];
        if (!is_array($f_items)) $f_items = [];

        if (empty($f_items)) {
            echo json_encode(['success' => false, 'message' => 'No active food orders found to cancel for this reservation.']);
            exit;
        }

        // Check if overall order has already entered cooking preparation or ready/served state
        $curr_food_status = strtolower(trim($booking['food_status'] ?? 'selected'));
        if (in_array($curr_food_status, ['preparing', 'cooking', 'ready', 'served'])) {
            $status_name = ($curr_food_status === 'preparing' || $curr_food_status === 'cooking')
                ? 'Preparing to Cook'
                : (($curr_food_status === 'ready') ? 'Ready to Serve' : 'Delivered / Served');
            echo json_encode([
                'success' => false,
                'message' => "Order cannot be cancelled: The Estate Kitchen has already started preparation (Status: {$status_name}). Once food preparation has begun, dishes cannot be cancelled. Please contact the concierge if you need assistance."
            ]);
            exit;
        }

        $cancelled_names = [];
        $cancelled_count = 0;

        if ($action === 'cancel_item') {
            $item_idx = isset($input['item_index']) ? (int)$input['item_index'] : -1;
            if ($item_idx < 0 || !isset($f_items[$item_idx])) {
                echo json_encode(['success' => false, 'message' => 'Specified food item could not be found in active order.']);
                exit;
            }

            $target_item = $f_items[$item_idx];
            $target_st = strtolower(trim($target_item['status'] ?? ''));
            if (!empty($target_item['served']) || in_array($target_st, ['preparing', 'cooking', 'ready', 'served'])) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'This dish has already entered kitchen preparation or has been served and cannot be cancelled.'
                ]);
                exit;
            }

            $cancelled_names[] = $target_item['heading'] ?? 'Custom Meal';
            $cancelled_count = (int)($target_item['quantity'] ?? 1);
            array_splice($f_items, $item_idx, 1);
            $resp_msg = 'Cancelled ' . $cancelled_names[0] . ' from your cottage dining order. Folio updated.';

        } else {
            // cancel_order: cancel all unserved and unstarted items
            $remaining = [];
            foreach ($f_items as $it) {
                $it_st = strtolower(trim($it['status'] ?? ''));
                if (!empty($it['served']) || in_array($it_st, ['preparing', 'cooking', 'ready', 'served'])) {
                    $remaining[] = $it;
                } else {
                    $cancelled_count += (int)($it['quantity'] ?? 1);
                    $cancelled_names[] = $it['heading'] ?? 'Meal';
                }
            }

            if ($cancelled_count === 0) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'All dishes in this order have already been prepared or served to your cottage and cannot be cancelled.'
                ]);
                exit;
            }

            $f_items = $remaining;
            $resp_msg = 'Successfully cancelled ' . $cancelled_count . ' pending meal(s) from your cottage order. Folio updated.';
        }

        // Recalculate billing components
        $new_food_total = 0.00;
        foreach ($f_items as $fi) {
            $new_food_total += (float)($fi['subtotal'] ?? (($fi['price'] ?? 0) * ($fi['quantity'] ?? 1)));
        }

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
        $raw_r_cottage = get_setting('gst_rate_cottage', null);
        $r_cottage = ($raw_r_cottage !== null && $raw_r_cottage !== '' && is_numeric($raw_r_cottage)) ? max(0.0, (float)$raw_r_cottage) : 12.00;
        $raw_r_food = get_setting('gst_rate_food', null);
        $r_food = ($raw_r_food !== null && $raw_r_food !== '' && is_numeric($raw_r_food)) ? max(0.0, (float)$raw_r_food) : 5.00;
        $raw_r_other = get_setting('gst_rate_other', null);
        $r_other = ($raw_r_other !== null && $raw_r_other !== '' && is_numeric($raw_r_other)) ? max(0.0, (float)$raw_r_other) : 18.00;

        $stay_gst = round(max(0, $room_amt - min($disc_amt, $room_amt)) * ($r_cottage / 100), 2);
        $food_gst = round($new_food_total * ($r_food / 100), 2);
        $other_gst = round(($act_total + $cust_total + $extra_chg) * ($r_other / 100), 2);
        $new_gst = round($stay_gst + $food_gst + $other_gst, 2);
        $gst_pct = $r_food;
        $new_grand_total = round($taxable + $new_gst, 2);
        $new_balance_due = max(0, $new_grand_total - $adv_paid);
        $food_status = empty($f_items) ? 'none' : ($booking['food_status'] ?? 'selected');

        $upd = $pdo->prepare("UPDATE bookings 
                              SET food_items = ?, 
                                  food_amount = ?, 
                                  food_status = ?, 
                                  gst_amount = ?, 
                                  tax_amount = ?, 
                                  total_amount = ? 
                              WHERE id = ?");
        $upd->execute([
            !empty($f_items) ? json_encode($f_items, JSON_UNESCAPED_UNICODE) : null,
            $new_food_total,
            $food_status,
            $new_gst,
            $new_gst,
            $new_grand_total,
            $booking['id']
        ]);

        echo json_encode([
            'success' => true,
            'message' => $resp_msg,
            'food_total' => $new_food_total,
            'grand_total' => $new_grand_total,
            'balance_due' => $new_balance_due,
            'food_count' => count($f_items),
            'food_items' => $f_items,
            'cgst_amount' => round($new_gst / 2, 2),
            'sgst_amount' => round($new_gst / 2, 2),
            'gst_amount' => $new_gst,
            'cancelled_count' => $cancelled_count,
            'cancelled_dishes' => $cancelled_names,
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
    $raw_r_cottage = get_setting('gst_rate_cottage', null);
    $r_cottage = ($raw_r_cottage !== null && $raw_r_cottage !== '' && is_numeric($raw_r_cottage)) ? max(0.0, (float)$raw_r_cottage) : 12.00;
    $raw_r_food = get_setting('gst_rate_food', null);
    $r_food = ($raw_r_food !== null && $raw_r_food !== '' && is_numeric($raw_r_food)) ? max(0.0, (float)$raw_r_food) : 5.00;
    $raw_r_other = get_setting('gst_rate_other', null);
    $r_other = ($raw_r_other !== null && $raw_r_other !== '' && is_numeric($raw_r_other)) ? max(0.0, (float)$raw_r_other) : 18.00;

    $stay_gst = round(max(0, $room_amt - min($disc_amt, $room_amt)) * ($r_cottage / 100), 2);
    $food_gst = round($new_food_total * ($r_food / 100), 2);
    $other_gst = round(($act_total + $cust_total + $extra_chg) * ($r_other / 100), 2);
    $new_gst = round($stay_gst + $food_gst + $other_gst, 2);
    $gst_pct = $r_food;
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
        'message' => '✓ Successfully confirmed & dispatched ' . $total_added_qty . ' item' . ($total_added_qty > 1 ? 's' : '') . ' to Cottage #' . htmlspecialchars($booking['reference_code']) . '! The Estate Kitchen has received your order.',
        'food_total' => $new_food_total,
        'grand_total' => $new_grand_total,
        'balance_due' => $new_balance_due,
        'food_count' => count($f_items),
        'food_items' => $f_items,
        'cgst_amount' => round($new_gst / 2, 2),
        'sgst_amount' => round($new_gst / 2, 2),
        'gst_amount' => $new_gst,
        'ordered_count' => $total_added_qty,
        'ordered_dishes' => $summary_names,
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
