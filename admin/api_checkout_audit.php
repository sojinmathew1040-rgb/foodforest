<?php
// =========================================================================
// Food Forest Sanctuary — Concierge Check-Out & Stay Audit API
// Handles pre-checkout verification of rooms, meals, and experiences
// Supports Food Dish Substitution, Experiences Auditing & Room Inventory Checklist
// =========================================================================

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_admin_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized administrator access.']);
    exit;
}

$pdo = get_db();
ensure_billing_columns($pdo);
ensure_booking_gst_columns($pdo);
ensure_food_menu_table_exists($pdo);
ensure_rooms_pricing_columns($pdo);

$method = $_SERVER['REQUEST_METHOD'];

// Helper for default room inventory checklist
function get_default_room_inventory_checklist($villa_slug = '') {
    return [
        ['item' => 'Physical Room Key & Brass Keychain', 'qty' => 1, 'notes' => 'Handover at reception'],
        ['item' => 'TV Unit & Set-Top Box', 'qty' => 1, 'notes' => 'Screen undamaged, cables intact'],
        ['item' => 'TV Remote & Set-Top Remote', 'qty' => 2, 'notes' => 'Both remotes working with batteries'],
        ['item' => 'Electric Water Kettle & Ceramic Tray', 'qty' => 1, 'notes' => 'Clean & working condition'],
        ['item' => 'Artisan Coffee Mugs & Spoons', 'qty' => 2, 'notes' => 'Earthen/ceramic sets intact'],
        ['item' => 'Glass Water Pitcher / Spring Bottles', 'qty' => 2, 'notes' => 'Sanitized glass carafes'],
        ['item' => 'Hairdryer (Grooming Kit)', 'qty' => 1, 'notes' => 'Kept in bathroom drawer'],
        ['item' => 'Emergency High-Beam LED Torch', 'qty' => 1, 'notes' => 'Rechargeable forest torch'],
        ['item' => 'Heavy-Duty Walking Umbrellas', 'qty' => 2, 'notes' => 'In umbrella stand at entrance'],
        ['item' => 'Mosquito Vaporizer Unit', 'qty' => 1, 'notes' => 'Plugged in bedside socket'],
        ['item' => 'Organic Cotton Bath & Hand Towels', 'qty' => 4, 'notes' => '2 Large Bath + 2 Hand Towels'],
        ['item' => 'Heavy Duck-Down Quilts & Blankets', 'qty' => 2, 'notes' => 'In wardrobe / master bed'],
        ['item' => 'Balcony Cane Chairs & Teak Table', 'qty' => 1, 'notes' => 'Sit-out furniture undamaged'],
        ['item' => 'Fireplace Guard / Tool Set', 'qty' => 1, 'notes' => 'If hearth villa']
    ];
}

if ($method === 'GET' || (isset($_GET['action']) && $_GET['action'] === 'get_audit_data')) {
    $booking_id = (int)($_GET['booking_id'] ?? 0);
    $ref_code = strtoupper(trim($_GET['ref'] ?? ''));

    if ($booking_id <= 0 && empty($ref_code)) {
        echo json_encode(['success' => false, 'error' => 'Booking ID or Reference required.']);
        exit;
    }

    try {
        if ($booking_id > 0) {
            $stmt = $pdo->prepare("SELECT b.*, r.title AS room_title, r.image_url AS room_image, r.elevation AS room_elevation, r.stay_type AS room_stay_type, r.inventory_checklist AS room_inventory_raw FROM bookings b LEFT JOIN rooms r ON b.villa_type = r.slug WHERE b.id = ?");
            $stmt->execute([$booking_id]);
        } else {
            $stmt = $pdo->prepare("SELECT b.*, r.title AS room_title, r.image_url AS room_image, r.elevation AS room_elevation, r.stay_type AS room_stay_type, r.inventory_checklist AS room_inventory_raw FROM bookings b LEFT JOIN rooms r ON b.villa_type = r.slug WHERE b.reference_code = ?");
            $stmt->execute([$ref_code]);
        }
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            echo json_encode(['success' => false, 'error' => 'Reservation not found.']);
            exit;
        }

        $billing_params = get_billing_params_for_booking($booking);

        // Parse Room Inventory Checklist
        $room_inventory = [];
        if (!empty($booking['room_inventory_raw'])) {
            $raw_inv = trim($booking['room_inventory_raw']);
            if (strpos($raw_inv, '[') === 0) {
                $room_inventory = json_decode($raw_inv, true) ?: [];
            } else {
                // Comma/newline separated
                $lines = preg_split('/[\r\n,]+/', $raw_inv);
                foreach ($lines as $ln) {
                    $ln = trim($ln);
                    if (!empty($ln)) {
                        $room_inventory[] = ['item' => $ln, 'qty' => 1, 'notes' => 'Standard Item'];
                    }
                }
            }
        }
        if (empty($room_inventory)) {
            $room_inventory = get_default_room_inventory_checklist($booking['villa_type']);
        }

        // Fetch all active Food Menu items for substitution dropdown
        $all_dishes = $pdo->query("SELECT id, name, category, price, is_complimentary, description FROM food_menu WHERE is_available = 1 ORDER BY category ASC, display_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Fetch all experiences for substitution
        $all_experiences = $pdo->query("SELECT id, title, badge, timing FROM experiences WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'booking' => $booking,
            'billing_params' => $billing_params,
            'food_items' => $billing_params['food_items'],
            'activities' => $billing_params['activities'],
            'custom_items' => $billing_params['custom_items'],
            'room_inventory' => $room_inventory,
            'available_menu_items' => $all_dishes,
            'available_experiences' => $all_experiences
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $action = $input['action'] ?? 'save_audit_and_checkout';
    $booking_id = (int)($input['booking_id'] ?? 0);

    if ($booking_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Valid Booking ID is required.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            echo json_encode(['success' => false, 'error' => 'Booking not found.']);
            exit;
        }

        // Process updated/verified food items (with substitutions support)
        $verified_food = $input['food_items'] ?? null;
        $food_total = (float)($booking['food_amount'] ?? 0);
        $food_json = $booking['food_items'];

        if (is_array($verified_food)) {
            $food_total = 0.00;
            $clean_food = [];
            foreach ($verified_food as $vf) {
                $h = trim($vf['heading'] ?? '');
                if (empty($h)) continue;
                $qty = max(1, (int)($vf['quantity'] ?? 1));
                $price = max(0, (float)($vf['price'] ?? 0));
                $served = !empty($vf['served']);
                $subtotal = $served ? ($qty * $price) : 0.00;
                $food_total += $subtotal;

                $clean_food[] = [
                    'heading' => $h,
                    'category' => trim($vf['category'] ?? 'Dining'),
                    'quantity' => $qty,
                    'price' => $price,
                    'subtotal' => $subtotal,
                    'served' => $served,
                    'is_substituted' => !empty($vf['is_substituted']),
                    'original_dish' => trim($vf['original_dish'] ?? ''),
                    'substitute_reason' => trim($vf['substitute_reason'] ?? '')
                ];
            }
            $food_json = json_encode($clean_food, JSON_UNESCAPED_UNICODE);
        }

        // Process verified activities (with substitution / cancel support)
        $verified_activities = $input['activities'] ?? null;
        $activities_json = $booking['activities_json'];

        if (is_array($verified_activities)) {
            $clean_act = [];
            foreach ($verified_activities as $va) {
                $t = trim($va['title'] ?? '');
                if (empty($t)) continue;
                $qty = max(1, (int)($va['quantity'] ?? 1));
                $price = max(0, (float)($va['price'] ?? 0));
                $completed = !empty($va['completed']);
                $subtotal = $completed ? ($qty * $price) : 0.00;

                $clean_act[] = [
                    'title' => $t,
                    'timing' => trim($va['timing'] ?? 'Curated Schedule'),
                    'quantity' => $qty,
                    'price' => $price,
                    'subtotal' => $subtotal,
                    'completed' => $completed,
                    'is_substituted' => !empty($va['is_substituted']),
                    'original_title' => trim($va['original_title'] ?? '')
                ];
            }
            $activities_json = json_encode($clean_act, JSON_UNESCAPED_UNICODE);
        }

        // Process missing item damage penalties into custom_items
        $missing_penalties = (float)($input['missing_damage_penalty'] ?? 0);
        $penalty_notes = trim($input['missing_damage_notes'] ?? '');
        $custom_items_json = $booking['custom_items'];

        if ($missing_penalties > 0) {
            $custom_items = [];
            if (!empty($booking['custom_items'])) {
                $custom_items = json_decode($booking['custom_items'], true) ?: [];
            }
            // Remove previous inventory damage fee if re-auditing
            $custom_items = array_filter($custom_items, function($ci) {
                return stripos($ci['name'] ?? '', 'Inventory') === false && stripos($ci['name'] ?? '', 'Damage') === false;
            });
            $custom_items[] = [
                'name' => 'Room Asset Damage / Replacement Fee (' . ($penalty_notes ?: 'Missing Items') . ')',
                'amount' => $missing_penalties
            ];
            $custom_items_json = json_encode(array_values($custom_items), JSON_UNESCAPED_UNICODE);
        }

        // Check if status should be marked completed
        $mark_checkout = !empty($input['mark_checkout']);
        $new_status = $mark_checkout ? 'completed' : $booking['status'];
        $checkout_timestamp = $mark_checkout ? date('Y-m-d H:i:s') : $booking['checked_out_at'];

        // Recalculate room & total
        $room_amt = (float)$booking['room_amount'];
        if ($room_amt <= 0) {
            $room_amt = (float)$booking['total_amount'] - (float)$booking['food_amount'];
        }
        $new_total = $room_amt + $food_total + $missing_penalties;

        $upd = $pdo->prepare("UPDATE bookings SET 
            food_items = ?, 
            food_amount = ?, 
            activities_json = ?, 
            custom_items = ?, 
            status = ?, 
            checked_out_at = COALESCE(?, checked_out_at) 
            WHERE id = ?");
        $upd->execute([
            $food_json,
            $food_total,
            $activities_json,
            $custom_items_json,
            $new_status,
            $checkout_timestamp,
            $booking_id
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Stay audit successfully recorded' . ($mark_checkout ? ' and guest checked-out.' : '.'),
            'booking_id' => $booking_id,
            'reference_code' => $booking['reference_code'],
            'status' => $new_status,
            'billing_url' => 'billing.php?booking_id=' . $booking_id . '&audited=1',
            'print_bill_url' => 'print_bill.php?ref=' . urlencode($booking['reference_code'])
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
