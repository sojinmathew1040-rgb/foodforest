<?php
// =========================================================================
// Food Forest Sanctuary — Kitchen & Chef Orders Hub
// =========================================================================
require_once __DIR__ . '/includes/auth.php';
require_admin_auth();

$pdo = get_db();
ensure_food_menu_table_exists($pdo);

// -------------------------------------------------------------
// 1. Process Date & Meal Filters
// -------------------------------------------------------------
$today = date('Y-m-d');
$filter = trim($_GET['filter'] ?? 'today');
$meal_filter = strtolower(trim($_GET['meal'] ?? 'all'));

if ($filter === 'this_week') {
    // Current week: Monday to Sunday
    $from_date = date('Y-m-d', strtotime('monday this week'));
    $to_date = date('Y-m-d', strtotime('sunday this week'));
} elseif ($filter === 'tomorrow') {
    $from_date = date('Y-m-d', strtotime('+1 day'));
    $to_date = date('Y-m-d', strtotime('+1 day'));
} elseif ($filter === 'all_active' || $filter === 'all') {
    $filter = 'all_active';
    $from_date = '2000-01-01';
    $to_date = '2099-12-31';
} elseif ($filter === 'custom') {
    $from_date = !empty($_GET['from_date']) ? $_GET['from_date'] : $today;
    $to_date = !empty($_GET['to_date']) ? $_GET['to_date'] : $today;
} else {
    // Default: Today
    $filter = 'today';
    $from_date = $today;
    $to_date = $today;
}

// -------------------------------------------------------------
// 2. Handle POST Actions (Status Update, Quick Dish Add)
// -------------------------------------------------------------
$alert_message = '';
$alert_type = 'success';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Security validation failed. Please try again.']);
            exit;
        }
        $alert_message = 'Security validation failed. Please try again.';
        $alert_type = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_food_status') {
            $booking_id = (int)($_POST['booking_id'] ?? 0);
            $new_status = trim($_POST['new_food_status'] ?? 'selected');
            if ($booking_id > 0) {
                $bstmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
                $bstmt->execute([$booking_id]);
                $curr_b = $bstmt->fetch(PDO::FETCH_ASSOC);
                if ($curr_b) {
                    $f_items = !empty($curr_b['food_items']) ? json_decode($curr_b['food_items'], true) : [];
                    if (is_array($f_items)) {
                        foreach ($f_items as &$fi) {
                            $fi['status'] = $new_status;
                            $fi['served'] = ($new_status === 'served');
                        }
                        unset($fi);
                    }
                    $stmt = $pdo->prepare("UPDATE bookings SET food_status = ?, food_items = ? WHERE id = ?");
                    $stmt->execute([
                        $new_status,
                        !empty($f_items) ? json_encode($f_items, JSON_UNESCAPED_UNICODE) : null,
                        $booking_id
                    ]);

                    $status_labels = [
                        'selected' => 'Order Placed (Queued)',
                        'preparing' => 'Preparing to Cook',
                        'ready' => 'Ready to Serve',
                        'served' => 'Delivered / Served'
                    ];
                    $lbl = $status_labels[$new_status] ?? ucfirst($new_status);
                    $alert_message = "Kitchen status for Reservation #{$curr_b['reference_code']} updated to '{$lbl}'.";

                    if ($is_ajax) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => true,
                            'message' => $alert_message,
                            'food_status' => $new_status,
                            'food_status_label' => $lbl,
                            'booking_id' => $booking_id,
                            'reference_code' => $curr_b['reference_code']
                        ]);
                        exit;
                    }
                }
            }
        } elseif ($action === 'update_dish_status') {
            $booking_id = (int)($_POST['booking_id'] ?? 0);
            $item_idx = (int)($_POST['item_index'] ?? -1);
            $new_dish_status = trim($_POST['new_dish_status'] ?? 'selected');
            if ($booking_id > 0 && $item_idx >= 0) {
                $bstmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
                $bstmt->execute([$booking_id]);
                $curr_b = $bstmt->fetch(PDO::FETCH_ASSOC);
                if ($curr_b) {
                    $f_items = !empty($curr_b['food_items']) ? json_decode($curr_b['food_items'], true) : [];
                    if (is_array($f_items) && isset($f_items[$item_idx])) {
                        $f_items[$item_idx]['status'] = $new_dish_status;
                        $f_items[$item_idx]['served'] = ($new_dish_status === 'served');

                        // Recalculate overall status
                        $all_served = true;
                        $any_preparing = false;
                        $any_ready = false;
                        foreach ($f_items as $chk_f) {
                            $st = strtolower(trim($chk_f['status'] ?? ''));
                            if (empty($chk_f['served']) && $st !== 'served') $all_served = false;
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
                            $overall_status = $curr_b['food_status'] ?? 'selected';
                        }

                        $stmt = $pdo->prepare("UPDATE bookings SET food_status = ?, food_items = ? WHERE id = ?");
                        $stmt->execute([
                            $overall_status,
                            json_encode($f_items, JSON_UNESCAPED_UNICODE),
                            $booking_id
                        ]);
                        $dish_name = $f_items[$item_idx]['heading'] ?? 'Dish';
                        $alert_message = "Status for '{$dish_name}' updated.";

                        if ($is_ajax) {
                            header('Content-Type: application/json');
                            echo json_encode([
                                'success' => true,
                                'message' => $alert_message,
                                'dish_status' => $new_dish_status,
                                'overall_status' => $overall_status,
                                'booking_id' => $booking_id
                            ]);
                            exit;
                        }
                    }
                }
            }
        } elseif ($action === 'quick_add_batch_dishes' || $action === 'quick_add_dish') {
            $booking_id = (int)($_POST['booking_id'] ?? 0);
            $raw_items = $_POST['items_payload'] ?? ($_POST['items'] ?? '');
            $items_to_add = [];

            if (!empty($raw_items)) {
                if (is_string($raw_items)) {
                    $decoded = json_decode($raw_items, true);
                    if (is_array($decoded)) {
                        $items_to_add = $decoded;
                    }
                } elseif (is_array($raw_items)) {
                    $items_to_add = $raw_items;
                }
            }

            // Fallback for single dish (backwards compatibility)
            if (empty($items_to_add)) {
                $single_dish_id = (int)($_POST['dish_id'] ?? 0);
                if ($single_dish_id > 0) {
                    $items_to_add[] = [
                        'dish_id' => $single_dish_id,
                        'quantity' => max(1, (int)($_POST['dish_qty'] ?? 1)),
                        'meal_time' => strtolower(trim($_POST['dish_meal_time'] ?? 'lunch'))
                    ];
                }
            }

            if ($booking_id > 0 && !empty($items_to_add)) {
                $bstmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
                $bstmt->execute([$booking_id]);
                $b = $bstmt->fetch(PDO::FETCH_ASSOC);

                if ($b) {
                    $f_items = !empty($b['food_items']) ? json_decode($b['food_items'], true) : [];
                    if (!is_array($f_items)) $f_items = [];

                    $dstmt = $pdo->prepare("SELECT * FROM food_menu WHERE id = ?");
                    $added_summary_names = [];
                    $total_batch_qty = 0;
                    $batch_added_subtotal = 0;
                    $kitchen_notes = trim($_POST['kitchen_notes'] ?? '');

                    foreach ($items_to_add as $it) {
                        $dish_id = (int)($it['dishId'] ?? ($it['dish_id'] ?? ($it['id'] ?? 0)));
                        $qty = max(1, min(50, (int)($it['quantity'] ?? ($it['qty'] ?? 1))));
                        $chosen_meal = strtolower(trim($it['mealSlot'] ?? ($it['meal_time'] ?? ($it['dish_meal_time'] ?? 'lunch'))));
                        if (strpos($chosen_meal, 'snack') !== false || strpos($chosen_meal, 'tea') !== false) {
                            $chosen_meal = 'snacks';
                        } elseif (strpos($chosen_meal, 'break') !== false || strpos($chosen_meal, 'morn') !== false) {
                            $chosen_meal = 'breakfast';
                        } elseif (strpos($chosen_meal, 'din') !== false || strpos($chosen_meal, 'night') !== false) {
                            $chosen_meal = 'dinner';
                        } elseif (strpos($chosen_meal, 'lunch') !== false || strpos($chosen_meal, 'noon') !== false) {
                            $chosen_meal = 'lunch';
                        }
                        if (!in_array($chosen_meal, ['breakfast', 'lunch', 'snacks', 'dinner'])) {
                            $chosen_meal = 'lunch';
                        }
                        $item_notes = trim($it['special_notes'] ?? ($it['notes'] ?? $kitchen_notes));

                        if ($dish_id <= 0) continue;

                        $dstmt->execute([$dish_id]);
                        $dish = $dstmt->fetch(PDO::FETCH_ASSOC);
                        if (!$dish) continue;

                        $price = (float)$dish['price'];
                        $subtotal = round($qty * $price, 2);
                        $batch_added_subtotal += $subtotal;
                        $total_batch_qty += $qty;
                        $added_summary_names[] = "{$qty}x {$dish['heading']}";

                        $inclusions = !empty($dish['inclusions']) ? json_decode($dish['inclusions'], true) : [];
                        if (!is_array($inclusions)) $inclusions = [];

                        $f_items[] = [
                            'id' => (int)$dish['id'],
                            'category' => $dish['category'],
                            'category_title' => ucfirst($dish['category']),
                            'heading' => $dish['heading'],
                            'subtitle' => $dish['subtitle'] ?? '',
                            'price' => $price,
                            'quantity' => $qty,
                            'subtotal' => $subtotal,
                            'meal_time' => $chosen_meal,
                            'dietary_type' => $dish['dietary_type'] ?? 'veg',
                            'inclusions' => $inclusions,
                            'special_notes' => $item_notes,
                            'ordered_at' => date('Y-m-d H:i:s'),
                            'status' => 'selected',
                            'served' => false
                        ];
                    }

                    if ($total_batch_qty <= 0) {
                        if ($is_ajax) {
                            header('Content-Type: application/json');
                            echo json_encode([
                                'success' => false,
                                'message' => 'No valid dishes found in cart to dispatch. Please choose dishes from the menu.'
                            ]);
                            exit;
                        }
                        $alert_message = 'No valid dishes found in cart to dispatch. Please choose dishes from the menu.';
                        $alert_type = 'error';
                    } else {
                        // Recalculate total food amount
                        $new_food_total = 0.00;
                        foreach ($f_items as $fi) {
                            $new_food_total += (float)($fi['subtotal'] ?? (($fi['price'] ?? 0) * ($fi['quantity'] ?? 1)));
                        }

                        // Recalculate billing components with GST settings
                        $room_amt = (float)($b['room_amount'] ?? 0);
                        $extra_chg = (float)($b['extra_charges'] ?? 0);
                        $disc_amt = (float)($b['discount_amount'] ?? 0);

                        $acts = !empty($b['activities_json']) ? json_decode($b['activities_json'], true) : [];
                        $act_total = 0.00;
                        if (is_array($acts)) {
                            foreach ($acts as $act) {
                                $act_total += (float)($act['subtotal'] ?? (($act['price'] ?? 0) * ($act['quantity'] ?? 1)));
                            }
                        }

                        $cust_items = !empty($b['billing_items_json']) ? json_decode($b['billing_items_json'], true) : [];
                        $cust_total = 0.00;
                        if (is_array($cust_items)) {
                            foreach ($cust_items as $ci) {
                                if (is_array($ci) && isset($ci['subtotal'])) $cust_total += (float)$ci['subtotal'];
                            }
                        }

                        $gross = $room_amt + $new_food_total + $act_total + $cust_total + $extra_chg;
                        $taxable = max(0, $gross - $disc_amt);
                        $raw_r_cottage = get_setting('gst_rate_cottage', null);
                        $r_cottage = ($raw_r_cottage !== null && $raw_r_cottage !== '' && is_numeric($raw_r_cottage)) ? max(0.0, (float)$raw_r_cottage) : 5.00;
                        $raw_r_food = get_setting('gst_rate_food', null);
                        $r_food = ($raw_r_food !== null && $raw_r_food !== '' && is_numeric($raw_r_food)) ? max(0.0, (float)$raw_r_food) : 0.00;
                        $raw_r_other = get_setting('gst_rate_other', null);
                        $r_other = ($raw_r_other !== null && $raw_r_other !== '' && is_numeric($raw_r_other)) ? max(0.0, (float)$raw_r_other) : 0.00;

                        $stay_gst = round(max(0, $room_amt - min($disc_amt, $room_amt)) * ($r_cottage / 100), 2);
                        $food_gst = round($new_food_total * ($r_food / 100), 2);
                        $other_gst = round(($act_total + $cust_total + $extra_chg) * ($r_other / 100), 2);
                        $new_gst = round($stay_gst + $food_gst + $other_gst, 2);
                        $new_grand_total = round($taxable + $new_gst, 2);

                        $upd = $pdo->prepare("UPDATE bookings SET food_items = ?, food_amount = ?, food_status = 'selected', gst_amount = ?, tax_amount = ?, total_amount = ? WHERE id = ?");
                        $upd->execute([
                            json_encode($f_items, JSON_UNESCAPED_UNICODE),
                            $new_food_total,
                            $new_gst,
                            $new_gst,
                            $new_grand_total,
                            $booking_id
                        ]);

                        $summary_txt = implode(', ', array_slice($added_summary_names, 0, 3));
                        if (count($added_summary_names) > 3) {
                            $summary_txt .= ' and ' . (count($added_summary_names) - 3) . ' more';
                        }
                        $alert_message = "Dispatched {$total_batch_qty} items ({$summary_txt}) to Reservation #{$b['reference_code']}. Folio updated with ₹" . number_format($batch_added_subtotal, 2) . ".";

                        if ($is_ajax) {
                            header('Content-Type: application/json');
                            echo json_encode([
                                'success' => true,
                                'message' => $alert_message,
                                'booking_id' => $booking_id,
                                'reference_code' => $b['reference_code'],
                                'food_total' => $new_food_total,
                                'grand_total' => $new_grand_total,
                                'food_items' => $f_items,
                                'items_count' => count($items_to_add)
                            ]);
                            exit;
                        }
                    }
                }
            } else {
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Please select an in-house room and add at least one dish to the cart.']);
                    exit;
                }
            }
        }
    }
}

$page_title = 'Kitchen & Chef Orders';
$page_subtitle = 'Live food prep schedules, guest meal selections & kitchen batch management';
require_once __DIR__ . '/includes/header.php';

// -------------------------------------------------------------
// 3. Query Bookings with Food for Date Range
// -------------------------------------------------------------
if ($filter === 'today' || $filter === 'all_active') {
    // Strictly in-house guests currently staying at the estate (exclude completed/past and future arrivals)
    $sql = "SELECT id, reference_code, guest_name, guest_phone, guest_email, villa_type, adults_count, kids_count, 
                   checkin_date, checkout_date, nights, status, food_status, food_amount, food_items, special_notes 
            FROM bookings 
            WHERE status = 'inhouse' 
            ORDER BY checkin_date ASC, id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
} else {
    // For tomorrow / this week / custom date range: active bookings (in-house or upcoming confirmed), excluding past completed and cancelled
    $sql = "SELECT id, reference_code, guest_name, guest_phone, guest_email, villa_type, adults_count, kids_count, 
                   checkin_date, checkout_date, nights, status, food_status, food_amount, food_items, special_notes 
            FROM bookings 
            WHERE status IN ('inhouse', 'confirmed') 
              AND checkin_date <= :to_date 
              AND checkout_date >= :from_date 
            ORDER BY checkin_date ASC, id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':from_date' => $from_date,
        ':to_date' => $to_date
    ]);
}
$all_matching_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch strictly in-house reservations for the walk-in / order dispatch cottage selector (no past, no future)
$inhouse_sql = "SELECT id, reference_code, guest_name, guest_phone, villa_type, checkin_date, checkout_date 
                FROM bookings 
                WHERE status = 'inhouse' 
                ORDER BY checkin_date ASC, id ASC";
$all_inhouse_bookings = $pdo->query($inhouse_sql)->fetchAll(PDO::FETCH_ASSOC);

// Multi-tier Food GST rate for calculations
$raw_r_food = get_setting('gst_rate_food', null);
$gst_rate_food = ($raw_r_food !== null && $raw_r_food !== '' && is_numeric($raw_r_food)) ? max(0.0, (float)$raw_r_food) : 0.00;

// Load all dishes for quick add dropdown
$all_dishes = $pdo->query("SELECT id, heading, category, price, default_meal_time, dietary_type FROM food_menu WHERE is_active = 1 ORDER BY category ASC, heading ASC")->fetchAll(PDO::FETCH_ASSOC);

// Load room mapping for official suite/chalet titles
$room_map = [];
try {
    $room_map = $pdo->query("SELECT slug, title FROM rooms")->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    $room_map = [];
}
$GLOBALS['room_map'] = $room_map;

// Clean chalet name formatting using official room names
if (!function_exists('format_chalet_label')) {
    function format_chalet_label($slug, $custom_room_map = null) {
        if (empty($slug)) return 'Unassigned Chalet';
        $map = $custom_room_map ?? $GLOBALS['room_map'] ?? [];
        $slugs = array_map('trim', explode(',', $slug));
        $out = [];
        foreach ($slugs as $s) {
            $title = (!empty($map) && isset($map[$s])) ? $map[$s] : ucwords(str_replace(['-stay', '-'], ['', ' '], $s));
            if (stripos($s, 'duplex') !== false) {
                $out[] = '🏡 ' . $title;
            } elseif (stripos($s, 'mud') !== false) {
                $out[] = '🌿 ' . $title;
            } elseif (stripos($s, 'cottage') !== false || stripos($s, 'hut') !== false) {
                $out[] = '🪵 ' . $title;
            } else {
                $out[] = '🌲 ' . $title;
            }
        }
        return implode(' + ', $out);
    }
}

// -------------------------------------------------------------
// 4. Parse Orders & Aggregate Chef Preparation Quantities
// -------------------------------------------------------------
$kitchen_bookings = [];
$total_portions = 0;
$total_food_revenue = 0.0;
$total_guests_dining = 0;

// Prep aggregation buckets by meal slot
$prep_summary = [
    'breakfast' => ['title' => 'Breakfast (09:00 AM – 10:00 AM)', 'icon' => 'fa-mug-saucer', 'items' => [], 'count' => 0],
    'lunch' => ['title' => 'Lunch (12:30 PM – 02:30 PM)', 'icon' => 'fa-bowl-rice', 'items' => [], 'count' => 0],
    'snacks' => ['title' => 'Evening Specials (04:30 PM – 06:30 PM)', 'icon' => 'fa-cookie-bite', 'items' => [], 'count' => 0],
    'dinner' => ['title' => 'Dinner (07:00 PM – 09:00 PM)', 'icon' => 'fa-fire-burner', 'items' => [], 'count' => 0]
];

foreach ($all_matching_bookings as $b) {
    $f_list = !empty($b['food_items']) ? json_decode($b['food_items'], true) : [];
    if (!is_array($f_list)) $f_list = [];

    // Filter dishes by meal slot if meal_filter is not 'all'
    $all_dishes_with_slots = [];
    $filtered_dishes = [];
    $b_has_food = !empty($f_list);
    $b_room_name = format_chalet_label($b['villa_type'], $room_map);

    foreach ($f_list as $item) {
        $slot = strtolower(trim($item['meal_time'] ?? $item['category'] ?? 'lunch'));
        if (strpos($slot, 'snack') !== false || strpos($slot, 'evening') !== false || strpos($slot, 'tea') !== false) {
            $slot = 'snacks';
        } elseif (strpos($slot, 'break') !== false || strpos($slot, 'morn') !== false) {
            $slot = 'breakfast';
        } elseif (strpos($slot, 'din') !== false || strpos($slot, 'night') !== false) {
            $slot = 'dinner';
        } elseif (strpos($slot, 'lunch') !== false || strpos($slot, 'noon') !== false) {
            $slot = 'lunch';
        }
        if (!in_array($slot, ['breakfast', 'lunch', 'snacks', 'dinner'])) {
            $slot = 'lunch';
        }

        $h = trim($item['heading'] ?? 'Dish');
        $qty = max(1, (int)($item['quantity'] ?? 1));
        $price = (float)($item['price'] ?? 0);
        $subtotal = (float)($item['subtotal'] ?? ($qty * $price));
        $diet = strtolower(trim($item['dietary_type'] ?? 'veg'));

        // Add to prep summary for this slot
        if (!isset($prep_summary[$slot]['items'][$h])) {
            $prep_summary[$slot]['items'][$h] = [
                'name' => $h,
                'quantity' => 0,
                'dietary_type' => $diet,
                'price' => $price,
                'bookings_count' => 0,
                'rooms' => []
            ];
        }
        $prep_summary[$slot]['items'][$h]['quantity'] += $qty;
        $prep_summary[$slot]['count'] += $qty;

        if (!isset($prep_summary[$slot]['items'][$h]['rooms'][$b['id']])) {
            $prep_summary[$slot]['items'][$h]['rooms'][$b['id']] = [
                'booking_id' => $b['id'],
                'ref' => $b['reference_code'] ?? '',
                'guest' => $b['guest_name'] ?? 'Resident Guest',
                'room_name' => $b_room_name,
                'qty' => 0
            ];
            $prep_summary[$slot]['items'][$h]['bookings_count'] += 1;
        }
        $prep_summary[$slot]['items'][$h]['rooms'][$b['id']]['qty'] += $qty;

        $item_with_slot = $item;
        $item_with_slot['resolved_slot'] = $slot;
        $all_dishes_with_slots[] = $item_with_slot;

        if ($meal_filter === 'all' || $meal_filter === $slot) {
            $filtered_dishes[] = $item_with_slot;
            $total_portions += $qty;
            $total_food_revenue += $subtotal;
        }
    }

    if ($b_has_food) {
        $b['parsed_dishes'] = $all_dishes_with_slots;
        $b['filtered_dishes'] = $filtered_dishes;
        if (count($filtered_dishes) > 0 || $meal_filter === 'all') {
            $kitchen_bookings[] = $b;
            $total_guests_dining += ((int)($b['adults_count'] ?? 2) + (int)($b['kids_count'] ?? 0));
        }
    }
}
?>

<div class="adm-kitchen-wrap" style="padding-bottom: 50px;">

    <?php if (!empty($alert_message)): ?>
        <div class="adm-alert <?php echo $alert_type === 'error' ? 'adm-alert-error' : 'adm-alert-success'; ?>" style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; border-radius: 8px; padding: 12px 18px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid <?php echo $alert_type === 'error' ? 'fa-triangle-exclamation' : 'fa-circle-check'; ?>"></i>
                <span><?php echo htmlspecialchars($alert_message); ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove();" style="background: none; border: none; color: inherit; cursor: pointer; font-size: 16px;">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Header Section with Live Status & Action Buttons -->
    <div class="adm-section-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <h1 class="adm-page-title font-serif" style="margin: 0; font-size: 1.85rem; color: var(--accent-green); display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-kitchen-set" style="color: var(--accent-gold);"></i>
                    Kitchen &amp; Chef Orders
                </h1>
                <span class="adm-badge-live" style="background: #ECFDF5; color: #065F46; border: 1px solid #10B981; padding: 3px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;">
                    <span style="width: 7px; height: 7px; background: #10B981; border-radius: 50%; display: inline-block; animation: pulse 1.8s infinite;"></span>
                    Live Hearth
                </span>
            </div>
            <p class="adm-page-subtitle font-sans" style="margin: 4px 0 0 0; color: #64748B; font-size: 13.5px;">
                Showing food orders for: <strong style="color: var(--accent-green);"><?php echo date('d M Y', strtotime($from_date)); ?><?php echo ($from_date !== $to_date) ? (' — ' . date('d M Y', strtotime($to_date))) : ''; ?></strong>
            </p>
        </div>

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <!-- Print Shift KOT / Prep Sheet Button -->
            <button type="button" onclick="window.print();" class="adm-btn-action" style="background: #1C3826; color: #FFFFFF; border: none; padding: 9px 18px; border-radius: 6px; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.12);">
                <i class="fa-solid fa-print"></i>
                <span>Print Kitchen Sheet / KOT</span>
            </button>

            <!-- Refresh Button -->
            <a href="kitchen.php?filter=<?php echo urlencode($filter); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&meal=<?php echo urlencode($meal_filter); ?>" class="adm-btn-action outline" style="padding: 9px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;" title="Refresh orders">
                <i class="fa-solid fa-rotate-right"></i>
                <span>Refresh</span>
            </a>
        </div>
    </div>

    <!-- Quick Date Filter Toolbar (Today, This Week, Tomorrow, Custom Range) -->
    <div class="adm-card" style="margin-bottom: 22px; padding: 16px 20px; background: var(--bg-card, #FFFFFF); border: 1.5px solid rgba(28, 56, 38, 0.12); border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
        <form method="GET" action="kitchen.php" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
            <input type="hidden" name="meal" value="<?php echo htmlspecialchars($meal_filter); ?>">

            <!-- Quick Pill Buttons -->
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <span class="font-sans" style="font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.5px; margin-right: 4px;">
                    <i class="fa-regular fa-calendar"></i> Shift Date:
                </span>
                
                <a href="kitchen.php?filter=today&meal=<?php echo urlencode($meal_filter); ?>" 
                   class="btn-date-filter-pill <?php echo ($filter === 'today') ? 'active' : ''; ?>"
                   style="padding: 6px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.15s ease; <?php echo ($filter === 'today') ? 'background: #1C3826; color: #FFFFFF; border: 1.5px solid #1C3826;' : 'background: #F1F5F9; color: #334155; border: 1.5px solid #CBD5E1;'; ?>">
                    🌟 Today (<?php echo date('d M'); ?>)
                </a>

                <a href="kitchen.php?filter=tomorrow&meal=<?php echo urlencode($meal_filter); ?>" 
                   class="btn-date-filter-pill <?php echo ($filter === 'tomorrow') ? 'active' : ''; ?>"
                   style="padding: 6px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.15s ease; <?php echo ($filter === 'tomorrow') ? 'background: #1C3826; color: #FFFFFF; border: 1.5px solid #1C3826;' : 'background: #F1F5F9; color: #334155; border: 1.5px solid #CBD5E1;'; ?>">
                    📆 Tomorrow
                </a>

                <a href="kitchen.php?filter=this_week&meal=<?php echo urlencode($meal_filter); ?>" 
                   class="btn-date-filter-pill <?php echo ($filter === 'this_week') ? 'active' : ''; ?>"
                   style="padding: 6px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.15s ease; <?php echo ($filter === 'this_week') ? 'background: #1C3826; color: #FFFFFF; border: 1.5px solid #1C3826;' : 'background: #F1F5F9; color: #334155; border: 1.5px solid #CBD5E1;'; ?>">
                    📅 This Week
                </a>

                <a href="kitchen.php?filter=all_active&meal=<?php echo urlencode($meal_filter); ?>" 
                   class="btn-date-filter-pill <?php echo ($filter === 'all_active') ? 'active' : ''; ?>"
                   style="padding: 6px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.15s ease; <?php echo ($filter === 'all_active') ? 'background: #1C3826; color: #FFFFFF; border: 1.5px solid #1C3826;' : 'background: #F1F5F9; color: #334155; border: 1.5px solid #CBD5E1;'; ?>">
                    🌿 All In-House &amp; Active
                </a>
            </div>

            <!-- Custom From Date - To Date Form -->
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <input type="hidden" name="filter" value="custom">
                <div style="display: flex; align-items: center; gap: 6px;">
                    <label for="k_from_date" style="font-size: 12px; font-weight: 600; color: #64748B;">From:</label>
                    <input type="date" id="k_from_date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>" class="adm-form-input font-sans" style="padding: 5px 10px; font-size: 12.5px; border-radius: 6px; border: 1px solid #CBD5E1;">
                </div>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <label for="k_to_date" style="font-size: 12px; font-weight: 600; color: #64748B;">To:</label>
                    <input type="date" id="k_to_date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>" class="adm-form-input font-sans" style="padding: 5px 10px; font-size: 12.5px; border-radius: 6px; border: 1px solid #CBD5E1;">
                </div>
                <button type="submit" class="adm-btn-action" style="padding: 6px 14px; font-size: 12.5px; background: #0E7490; color: #fff; border: none; border-radius: 6px; font-weight: 700; cursor: pointer;">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
                <?php if ($filter !== 'today'): ?>
                    <a href="kitchen.php?filter=today" style="font-size: 12px; color: #EF4444; font-weight: 600; text-decoration: underline;">Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Top KPI Highlights -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px; margin-bottom: 24px;">
        <div class="adm-card" style="padding: 16px 18px; border-radius: 10px; background: #FFFFFF; border: 1.5px solid rgba(28, 56, 38, 0.1); border-left: 4px solid #10B981; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
            <span style="font-size: 11.5px; font-weight: 700; color: #64748B; text-transform: uppercase;">Active Food Bookings</span>
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-top: 4px;">
                <span style="font-size: 26px; font-weight: 800; color: #1C3826; font-family: monospace;"><?php echo count($kitchen_bookings); ?></span>
                <span style="font-size: 12px; color: #059669; font-weight: 600;"><i class="fa-solid fa-bed"></i> Rooms</span>
            </div>
        </div>

        <div class="adm-card" style="padding: 16px 18px; border-radius: 10px; background: #FFFFFF; border: 1.5px solid rgba(28, 56, 38, 0.1); border-left: 4px solid #F59E0B; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
            <span style="font-size: 11.5px; font-weight: 700; color: #64748B; text-transform: uppercase;">Total Dishes / Portions</span>
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-top: 4px;">
                <span style="font-size: 26px; font-weight: 800; color: #92400E; font-family: monospace;"><?php echo $total_portions; ?></span>
                <span style="font-size: 12px; color: #D97706; font-weight: 600;"><i class="fa-solid fa-utensils"></i> Total Items</span>
            </div>
        </div>

        <div class="adm-card" style="padding: 16px 18px; border-radius: 10px; background: #FFFFFF; border: 1.5px solid rgba(28, 56, 38, 0.1); border-left: 4px solid #0284C7; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
            <span style="font-size: 11.5px; font-weight: 700; color: #64748B; text-transform: uppercase;">Guests Dining</span>
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-top: 4px;">
                <span style="font-size: 26px; font-weight: 800; color: #0369A1; font-family: monospace;"><?php echo $total_guests_dining; ?></span>
                <span style="font-size: 12px; color: #0284C7; font-weight: 600;"><i class="fa-solid fa-users"></i> Persons</span>
            </div>
        </div>

        <div class="adm-card" style="padding: 16px 18px; border-radius: 10px; background: #FFFFFF; border: 1.5px solid rgba(28, 56, 38, 0.1); border-left: 4px solid #8B5CF6; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
            <span style="font-size: 11.5px; font-weight: 700; color: #64748B; text-transform: uppercase;">Total Food Value</span>
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-top: 4px;">
                <span style="font-size: 26px; font-weight: 800; color: #6D28D9; font-family: monospace;">₹<?php echo number_format($total_food_revenue, 0); ?></span>
                <span style="font-size: 12px; color: #7C3AED; font-weight: 600;"><i class="fa-solid fa-receipt"></i> Billed</span>
            </div>
        </div>
    </div>

    <!-- Meal Time Filter Tabs (All, Breakfast, Lunch, Evening Snacks, Dinner) -->
    <div class="adm-meal-tabs-bar" style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 12px; margin-bottom: 22px; -webkit-overflow-scrolling: touch;">
        <a href="kitchen.php?filter=<?php echo urlencode($filter); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&meal=all" 
           style="padding: 8px 18px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; <?php echo ($meal_filter === 'all') ? 'background: #1C3826; color: #fff; box-shadow: 0 2px 6px rgba(28,56,38,0.25);' : 'background: #FFFFFF; color: #475569; border: 1px solid #CBD5E1;'; ?>">
            <i class="fa-solid fa-layer-group"></i>
            <span>All Meal Times</span>
            <span style="background: rgba(255,255,255,0.25); padding: 2px 7px; border-radius: 12px; font-size: 11px; <?php echo ($meal_filter === 'all') ? 'color: #fff;' : 'background: #E2E8F0; color: #1E293B;'; ?>">
                <?php echo array_sum(array_column($prep_summary, 'count')); ?>
            </span>
        </a>

        <?php foreach ($prep_summary as $s_key => $s_data): ?>
            <a href="kitchen.php?filter=<?php echo urlencode($filter); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&meal=<?php echo $s_key; ?>" 
               style="padding: 8px 18px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; <?php echo ($meal_filter === $s_key) ? 'background: #1C3826; color: #fff; box-shadow: 0 2px 6px rgba(28,56,38,0.25);' : 'background: #FFFFFF; color: #475569; border: 1px solid #CBD5E1;'; ?>">
                <i class="fa-solid <?php echo $s_data['icon']; ?>"></i>
                <span><?php echo ucfirst($s_key); ?></span>
                <span style="padding: 2px 7px; border-radius: 12px; font-size: 11px; <?php echo ($meal_filter === $s_key) ? 'background: rgba(255,255,255,0.25); color: #fff;' : 'background: #E2E8F0; color: #1E293B;'; ?>">
                    <?php echo $s_data['count']; ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Master Quick Section Toggle & Accordion Controls Bar -->
    <div class="adm-card kitchen-sections-quicknav" style="margin-bottom: 20px; padding: 12px 18px; background: #F8FAFC; border: 1.5px solid #CBD5E1; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <span style="font-size: 11.5px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">
                <i class="fa-solid fa-sliders"></i> Quick View Sections:
            </span>
            <button type="button" onclick="toggleKitchenMainSection('prep_summary', true, true);" class="adm-btn-section-shortcut" id="shortcut_btn_prep_summary" title="Toggle Chef Preparation Batch Summary">
                <i class="fa-solid fa-fire-burner" style="color: #D97706;"></i>
                <span>1. Batch Prep (<?php echo $total_portions; ?> Portions)</span>
            </button>
            <button type="button" onclick="toggleKitchenMainSection('order_tickets', true, true);" class="adm-btn-section-shortcut" id="shortcut_btn_order_tickets" title="Toggle Guest Order Tickets">
                <i class="fa-solid fa-receipt" style="color: #059669;"></i>
                <span>2. Order Tickets (<?php echo count($kitchen_bookings); ?> Rooms)</span>
            </button>
            <button type="button" onclick="toggleKitchenMainSection('walkin_order', true, true);" class="adm-btn-section-shortcut order-btn-highlight" id="shortcut_btn_walkin_order" title="Open Multi-Item Order Creator">
                <i class="fa-solid fa-cart-shopping" style="color: #0E7490;"></i>
                <span>3. Take Food Order (Multi-Item Cart)</span>
            </button>
        </div>
        <div style="display: flex; align-items: center; gap: 6px;">
            <button type="button" onclick="expandAllKitchenSections();" class="adm-btn" style="padding: 5px 12px; font-size: 11.5px; background: #FFFFFF; color: #1E293B; border: 1px solid #CBD5E1; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;" title="Expand all sections">
                <i class="fa-solid fa-angles-down"></i> Expand All
            </button>
            <button type="button" onclick="collapseAllKitchenSections();" class="adm-btn" style="padding: 5px 12px; font-size: 11.5px; background: #FFFFFF; color: #1E293B; border: 1px solid #CBD5E1; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;" title="Collapse all sections">
                <i class="fa-solid fa-angles-up"></i> Collapse All
            </button>
        </div>
    </div>

    <!-- 1. Chef's Kitchen Preparation Batch Summary (Aggregated Prep List) -->
    <div class="adm-card kitchen-collapsible-card" id="section_prep_summary" style="margin-bottom: 22px; background: #FFFFFF; border: 1.5px solid rgba(28, 56, 38, 0.15); border-radius: 12px; overflow: hidden; box-shadow: 0 3px 10px rgba(0,0,0,0.03);">
        <div class="kitchen-section-header" id="header_prep_summary" onclick="toggleKitchenMainSection('prep_summary');" style="cursor: pointer; user-select: none; padding: 18px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; background: #FFFFFF; transition: background 0.2s ease;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0;">
                    <i class="fa-solid fa-fire-burner"></i>
                </div>
                <div>
                    <h3 class="font-serif" style="margin: 0; font-size: 1.35rem; color: #1C3826; display: flex; align-items: center; gap: 8px;">
                        Chef's Preparation Batch Summary
                    </h3>
                    <span class="font-sans" style="font-size: 12px; color: #64748B;">Aggregated dish quantities needed by kitchen staff for current shift</span>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <span style="font-size: 12px; font-weight: 700; color: #92400E; background: #FEF3C7; border: 1px solid #FDE68A; padding: 4px 10px; border-radius: 6px;">
                    <i class="fa-solid fa-utensils"></i> <?php echo $total_portions; ?> Portions
                </span>
                <span style="font-size: 12px; font-weight: 700; color: #047857; background: #ECFDF5; border: 1px solid #A7F3D0; padding: 4px 10px; border-radius: 6px;">
                    <i class="fa-solid fa-clipboard-check"></i> <?php echo ($meal_filter === 'all') ? 'All Shifts' : (ucfirst($meal_filter) . ' Shift'); ?>
                </span>
                <button type="button" class="section-toggle-btn" id="btn_toggle_prep_summary" onclick="event.stopPropagation(); toggleKitchenMainSection('prep_summary');">
                    <span id="txt_toggle_prep_summary">Expand</span>
                    <i class="fa-solid fa-chevron-down toggle-icon" id="icon_toggle_prep_summary"></i>
                </button>
            </div>
        </div>

        <!-- Collapsible Content for Preparation Summary -->
        <div class="kitchen-section-body" id="body_prep_summary" style="display: none; padding: 20px; border-top: 1.5px solid #F1F5F9; background: #FFFFFF;">

        <?php
        $any_prep_found = false;
        $slots_to_show = ($meal_filter === 'all') ? array_keys($prep_summary) : [$meal_filter];
        ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px;">
            <?php foreach ($slots_to_show as $slot_key): 
                $slot_meta = $prep_summary[$slot_key];
                if (empty($slot_meta['items'])) continue;
                $any_prep_found = true;
            ?>
                <div style="background: #F8FAF8; border: 1.5px solid rgba(28, 56, 38, 0.12); border-radius: 10px; padding: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px dashed rgba(28, 56, 38, 0.18);">
                        <strong style="color: #1C3826; font-size: 14px; display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid <?php echo $slot_meta['icon']; ?>" style="color: var(--accent-gold);"></i>
                            <?php echo $slot_meta['title']; ?>
                        </strong>
                        <span style="font-size: 11px; background: rgba(28, 56, 38, 0.1); color: #1C3826; font-weight: 700; padding: 2px 7px; border-radius: 10px;">
                            <?php echo $slot_meta['count']; ?> Portions
                        </span>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <?php foreach ($slot_meta['items'] as $item_name => $item_info): 
                            $is_v = ($item_info['dietary_type'] === 'veg');
                            $rooms_list = array_values($item_info['rooms'] ?? []);
                            $is_multi_room = count($rooms_list) > 1;
                        ?>
                            <div style="background: #FFFFFF; border: 1px solid rgba(0,0,0,0.06); padding: 9px 12px; border-radius: 6px; display: flex; flex-direction: column; gap: 6px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                                    <div style="display: flex; align-items: center; gap: 8px; min-width: 0; flex: 1;">
                                        <span style="font-size: 10px; color: <?php echo $is_v ? '#059669' : '#DC2626'; ?>; font-weight: 700; flex-shrink: 0;">
                                            ●
                                        </span>
                                        <span style="font-size: 13.5px; font-weight: 600; color: #1E293B;">
                                            <?php echo htmlspecialchars($item_name); ?>
                                        </span>
                                    </div>
                                    <strong style="font-size: 14px; background: #FEF3C7; color: #92400E; padding: 2px 8px; border-radius: 4px; font-family: monospace; white-space: nowrap; flex-shrink: 0;">
                                        × <?php echo $item_info['quantity']; ?>
                                    </strong>
                                </div>

                                <!-- Room badge(s) -->
                                <div style="display: flex; flex-wrap: wrap; gap: 4px; align-items: center; padding-left: 18px;">
                                    <?php if (!$is_multi_room && !empty($rooms_list)): 
                                        $r0 = $rooms_list[0];
                                    ?>
                                        <span style="font-size: 11px; font-weight: 600; color: #047857; background: #ECFDF5; border: 1px solid #A7F3D0; padding: 2px 8px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;" title="Guest: <?php echo htmlspecialchars($r0['guest'] . ' (REF #' . $r0['ref'] . ')'); ?>">
                                            <?php echo htmlspecialchars($r0['room_name']); ?>
                                        </span>
                                    <?php elseif ($is_multi_room): ?>
                                        <?php foreach ($rooms_list as $r): ?>
                                            <span style="font-size: 10.5px; font-weight: 600; color: #047857; background: #ECFDF5; border: 1px solid #A7F3D0; padding: 2px 7px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;" title="Guest: <?php echo htmlspecialchars($r['guest'] . ' (REF #' . $r['ref'] . ')'); ?>">
                                                <span><?php echo htmlspecialchars($r['room_name']); ?></span>
                                                <span style="background: #10B981; color: #FFFFFF; padding: 0 4px; border-radius: 3px; font-size: 9.5px; font-family: monospace; font-weight: 700;">×<?php echo $r['qty']; ?></span>
                                            </span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (!$any_prep_found): ?>
                <div style="grid-column: 1 / -1; padding: 30px; text-align: center; color: #64748B;">
                    <i class="fa-solid fa-mug-hot" style="font-size: 32px; color: #CBD5E1; margin-bottom: 8px; display: block;"></i>
                    <p style="margin: 0; font-size: 14px;">No advance kitchen orders recorded for this selection. Guests may order a la carte upon arrival.</p>
                </div>
            <?php endif; ?>
        </div>
        </div>
    </div>

    <!-- 2. Detailed Orders by Guest & Room (Order Cards) -->
    <div class="adm-card kitchen-collapsible-card" id="section_order_tickets" style="margin-bottom: 22px; background: #FFFFFF; border: 1.5px solid rgba(28, 56, 38, 0.15); border-radius: 12px; overflow: hidden; box-shadow: 0 3px 10px rgba(0,0,0,0.03);">
        <div class="kitchen-section-header" id="header_order_tickets" onclick="toggleKitchenMainSection('order_tickets');" style="cursor: pointer; user-select: none; padding: 18px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; background: #FFFFFF; transition: background 0.2s ease;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: #ECFDF5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0;">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <div>
                    <h3 class="font-serif" style="margin: 0; font-size: 1.35rem; color: #1C3826; display: flex; align-items: center; gap: 8px;">
                        Guest &amp; Room Order Tickets
                    </h3>
                    <span class="font-sans" style="font-size: 12px; color: #64748B;">Individual vouchers with dining slots, room numbers &amp; concierge contacts</span>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <span style="font-size: 12px; font-weight: 700; color: #1C3826; background: rgba(28, 56, 38, 0.1); border: 1px solid rgba(28, 56, 38, 0.2); padding: 4px 10px; border-radius: 6px;">
                    <i class="fa-solid fa-bed"></i> <?php echo count($kitchen_bookings); ?> Active Room Tickets
                </span>
                <button type="button" class="section-toggle-btn" id="btn_toggle_order_tickets" onclick="event.stopPropagation(); toggleKitchenMainSection('order_tickets');">
                    <span id="txt_toggle_order_tickets">Expand</span>
                    <i class="fa-solid fa-chevron-down toggle-icon" id="icon_toggle_order_tickets"></i>
                </button>
            </div>
        </div>

        <!-- Collapsible Content for Order Tickets -->
        <div class="kitchen-section-body" id="body_order_tickets" style="display: none; padding: 20px; border-top: 1.5px solid #F1F5F9; background: #F8FAF8;">

        <?php if (empty($kitchen_bookings)): ?>
            <div class="adm-card" style="padding: 40px; text-align: center; background: #FFFFFF; border-radius: 12px; border: 1.5px dashed #CBD5E1;">
                <i class="fa-solid fa-utensils" style="font-size: 36px; color: #94A3B8; margin-bottom: 12px; display: block;"></i>
                <h4 style="color: #334155; margin-bottom: 4px;">No Active Food Orders Found for this Period</h4>
                <p style="color: #64748B; font-size: 13px; max-width: 450px; margin: 0 auto 16px;">
                    There are no advance meal orders booked for the selected date range. You can use the Quick Add form below to take a walk-in order for any resident guest.
                </p>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 18px;">
                <?php foreach ($kitchen_bookings as $kb): 
                    $dishes_to_render = ($meal_filter === 'all') ? $kb['parsed_dishes'] : $kb['filtered_dishes'];
                    $chalet_label = format_chalet_label($kb['villa_type']);
                    $status_badge_color = ($kb['food_status'] === 'ready' || $kb['food_status'] === 'served') ? '#10B981' : (($kb['food_status'] === 'preparing') ? '#F59E0B' : '#64748B');
                ?>
                    <?php
                    $st_val = strtolower($kb['food_status'] ?? 'selected');
                    if ($st_val === 'served') {
                        $st_badge_bg = '#ECFDF5'; $st_badge_color = '#065F46'; $st_badge_border = '#10B981'; $st_badge_text = '✅ Delivered / Served';
                    } elseif ($st_val === 'ready') {
                        $st_badge_bg = '#F0FDF4'; $st_badge_color = '#15803D'; $st_badge_border = '#22C55E'; $st_badge_text = '🟢 Ready to Serve';
                    } elseif ($st_val === 'preparing' || $st_val === 'cooking') {
                        $st_badge_bg = '#FFFBEB'; $st_badge_color = '#B45309'; $st_badge_border = '#F59E0B'; $st_badge_text = '🟠 Preparing to Cook';
                    } else {
                        $st_badge_bg = '#F1F5F9'; $st_badge_color = '#475569'; $st_badge_border = '#94A3B8'; $st_badge_text = '🟡 Order Placed';
                    }
                    ?>
                    <div class="adm-card kitchen-ticket-card" id="kitchen-card-<?php echo $kb['id']; ?>" style="background: #FFFFFF; border: 1.5px solid rgba(28, 56, 38, 0.15); border-radius: 12px; padding: 18px; box-shadow: 0 3px 10px rgba(0,0,0,0.03); display: flex; flex-direction: column; justify-content: space-between; position: relative;">
                        
                        <div>
                            <!-- Ticket Top Bar: Chalet & Reference & Current Status Badge -->
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid #F1F5F9; gap: 8px; flex-wrap: wrap;">
                                <div>
                                    <span style="font-size: 11px; font-weight: 700; color: #0E7490; background: #E0F2FE; padding: 2px 7px; border-radius: 4px; text-transform: uppercase;">
                                        REF #<?php echo htmlspecialchars($kb['reference_code']); ?>
                                    </span>
                                    <h4 class="font-serif" style="margin: 4px 0 0 0; font-size: 1.15rem; color: #1C3826; font-weight: 700;">
                                        <?php echo htmlspecialchars($chalet_label); ?>
                                    </h4>
                                </div>

                                <!-- Current Status Badge -->
                                <div id="ticket-badge-wrap-<?php echo $kb['id']; ?>">
                                    <span id="ticket-current-badge-<?php echo $kb['id']; ?>" style="font-size: 11.5px; font-weight: 700; padding: 4px 10px; border-radius: 6px; background: <?php echo $st_badge_bg; ?>; color: <?php echo $st_badge_color; ?>; border: 1.5px solid <?php echo $st_badge_border; ?>; display: inline-flex; align-items: center; gap: 5px;">
                                        <?php echo $st_badge_text; ?>
                                    </span>
                                </div>
                            </div>

                            <!-- 1-Click Interactive Status Workflow Bar -->
                            <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 10px 12px; margin-bottom: 14px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                    <span style="font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">
                                        <i class="fa-solid fa-arrows-spin" style="color: #0E7490; margin-right: 4px;"></i> Update Kitchen Status:
                                    </span>
                                    <span style="font-size: 10.5px; color: #94A3B8;">(Click to advance stage)</span>
                                </div>
                                
                                <div class="kitchen-stage-btn-group" id="ticket-stage-group-<?php echo $kb['id']; ?>" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px;">
                                    
                                    <!-- 1. Placed -->
                                    <button type="button" 
                                            class="btn-kitchen-stage <?php echo ($st_val === 'selected' || $st_val === 'queued') ? 'active-stage' : ''; ?>" 
                                            onclick="updateTicketKitchenStatus(<?php echo $kb['id']; ?>, 'selected', 'Order Placed');"
                                            style="padding: 7px 4px; font-size: 11px; font-weight: 700; border-radius: 6px; border: 1px solid <?php echo ($st_val === 'selected' || $st_val === 'queued') ? '#94A3B8' : '#CBD5E1'; ?>; background: <?php echo ($st_val === 'selected' || $st_val === 'queued') ? '#334155' : '#FFFFFF'; ?>; color: <?php echo ($st_val === 'selected' || $st_val === 'queued') ? '#FFFFFF' : '#475569'; ?>; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px; transition: all 0.15s ease;"
                                            title="Mark as Placed / Queued (Guest may cancel)">
                                        <span style="font-size: 13px;">🟡</span>
                                        <span>Placed</span>
                                    </button>

                                    <!-- 2. Preparing to Cook -->
                                    <button type="button" 
                                            class="btn-kitchen-stage <?php echo ($st_val === 'preparing' || $st_val === 'cooking') ? 'active-stage' : ''; ?>" 
                                            onclick="updateTicketKitchenStatus(<?php echo $kb['id']; ?>, 'preparing', 'Preparing to Cook');"
                                            style="padding: 7px 4px; font-size: 11px; font-weight: 700; border-radius: 6px; border: 1px solid <?php echo ($st_val === 'preparing' || $st_val === 'cooking') ? '#D97706' : '#FDE68A'; ?>; background: <?php echo ($st_val === 'preparing' || $st_val === 'cooking') ? '#D97706' : '#FFFBEB'; ?>; color: <?php echo ($st_val === 'preparing' || $st_val === 'cooking') ? '#FFFFFF' : '#92400E'; ?>; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px; transition: all 0.15s ease;"
                                            title="Start cooking — Locks order so guest cannot cancel!">
                                        <span style="font-size: 13px;">🍳</span>
                                        <span>Preparing</span>
                                    </button>

                                    <!-- 3. Ready to Serve -->
                                    <button type="button" 
                                            class="btn-kitchen-stage <?php echo ($st_val === 'ready') ? 'active-stage' : ''; ?>" 
                                            onclick="updateTicketKitchenStatus(<?php echo $kb['id']; ?>, 'ready', 'Ready to Serve');"
                                            style="padding: 7px 4px; font-size: 11px; font-weight: 700; border-radius: 6px; border: 1px solid <?php echo ($st_val === 'ready') ? '#059669' : '#A7F3D0'; ?>; background: <?php echo ($st_val === 'ready') ? '#059669' : '#F0FDF4'; ?>; color: <?php echo ($st_val === 'ready') ? '#FFFFFF' : '#065F46'; ?>; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px; transition: all 0.15s ease;"
                                            title="Food is cooked &amp; plated — Ready for delivery runner">
                                        <span style="font-size: 13px;">🍲</span>
                                        <span>Ready</span>
                                    </button>

                                    <!-- 4. Delivered / Served -->
                                    <button type="button" 
                                            class="btn-kitchen-stage <?php echo ($st_val === 'served') ? 'active-stage' : ''; ?>" 
                                            onclick="updateTicketKitchenStatus(<?php echo $kb['id']; ?>, 'served', 'Delivered / Served');"
                                            style="padding: 7px 4px; font-size: 11px; font-weight: 700; border-radius: 6px; border: 1px solid <?php echo ($st_val === 'served') ? '#1C3826' : '#CBD5E1'; ?>; background: <?php echo ($st_val === 'served') ? '#1C3826' : '#FFFFFF'; ?>; color: <?php echo ($st_val === 'served') ? '#FFFFFF' : '#1C3826'; ?>; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px; transition: all 0.15s ease;"
                                            title="Food served at cottage / guest dining">
                                        <span style="font-size: 13px;">✅</span>
                                        <span>Served</span>
                                    </button>

                                </div>
                            </div>

                            <!-- Guest & Contact Info -->
                            <div style="margin-bottom: 12px; font-size: 12.5px; color: #475569; display: flex; flex-direction: column; gap: 4px;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <strong style="color: #1E293B; font-size: 13.5px;"><i class="fa-solid fa-user" style="color: var(--accent-gold); margin-right: 5px;"></i> <?php echo htmlspecialchars($kb['guest_name']); ?></strong>
                                    <?php if (!empty($kb['guest_phone'])): 
                                        $clean_ph = preg_replace('/[^0-9]/', '', $kb['guest_phone']);
                                        $wa_msg = urlencode("Hello " . $kb['guest_name'] . ", greetings from Food Forest Kitchen! Regarding your stay in " . $chalet_label . ", our chef is preparing your meal.");
                                    ?>
                                        <a href="https://wa.me/<?php echo $clean_ph; ?>?text=<?php echo $wa_msg; ?>" target="_blank" style="color: #059669; font-size: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fa-brands fa-whatsapp" style="font-size: 14px;"></i> Chat
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 11.5px; color: #64748B;">
                                    <span><i class="fa-solid fa-users" style="margin-right: 4px;"></i> <?php echo $kb['adults_count']; ?> Adults<?php echo ((int)$kb['kids_count'] > 0) ? (', ' . $kb['kids_count'] . ' Kids') : ''; ?></span>
                                    <span style="margin: 0 6px;">•</span>
                                    <span>Stay: <?php echo date('d M', strtotime($kb['checkin_date'])); ?> — <?php echo date('d M', strtotime($kb['checkout_date'])); ?> (<?php echo $kb['nights']; ?>N)</span>
                                </div>
                            </div>

                            <?php if (!empty($kb['special_notes'])): ?>
                                <div style="background: #FEF3C7; border: 1px solid #FDE68A; border-radius: 6px; padding: 8px 10px; margin-bottom: 12px; font-size: 11.5px; color: #92400E;">
                                    <strong style="display: flex; align-items: center; gap: 4px;"><i class="fa-solid fa-circle-exclamation"></i> Dietary Notes:</strong>
                                    <span><?php echo htmlspecialchars($kb['special_notes']); ?></span>
                                </div>
                            <?php endif; ?>

                            <!-- Items Table for this Ticket -->
                            <div style="background: #F8FAF8; border: 1px solid rgba(28, 56, 38, 0.1); border-radius: 8px; overflow: hidden; margin-bottom: 12px;">
                                <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                                    <thead>
                                        <tr style="background: rgba(28, 56, 38, 0.05); color: #1C3826; text-align: left; font-size: 11px; text-transform: uppercase;">
                                            <th style="padding: 7px 10px;">Dish</th>
                                            <th style="padding: 7px 8px;">Meal Slot</th>
                                            <th style="padding: 7px 10px; text-align: right;">Qty</th>
                                            <th style="padding: 7px 10px; text-align: right;">Price</th>
                                            <th style="padding: 7px 10px; text-align: center;">Item Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $ticket_subtotal = 0;
                                        foreach ($dishes_to_render as $dish_idx => $dish_item): 
                                            $dish_qty = (int)($dish_item['quantity'] ?? 1);
                                            $dish_price = (float)($dish_item['price'] ?? 0);
                                            $dish_sub = (float)($dish_item['subtotal'] ?? ($dish_qty * $dish_price));
                                            $ticket_subtotal += $dish_sub;
                                            $dish_slot = strtolower(trim($dish_item['meal_time'] ?? $dish_item['category'] ?? 'lunch'));
                                            $is_veg = (($dish_item['dietary_type'] ?? 'veg') === 'veg');
                                            $d_served = !empty($dish_item['served']);
                                            $d_status = $d_served ? 'served' : strtolower(trim($dish_item['status'] ?? $st_val));
                                        ?>
                                            <tr style="border-top: 1px solid rgba(0,0,0,0.05);" id="ticket-dish-row-<?php echo $kb['id']; ?>-<?php echo $dish_idx; ?>">
                                                <td style="padding: 7px 10px; color: #1E293B; font-weight: 600;">
                                                    <span style="color: <?php echo $is_veg ? '#059669' : '#DC2626'; ?>; font-size: 9px; margin-right: 4px;">●</span>
                                                    <?php echo htmlspecialchars($dish_item['heading'] ?? 'Dish'); ?>
                                                </td>
                                                <td style="padding: 7px 8px;">
                                                    <span style="font-size: 10px; font-weight: 700; color: #065F46; background: #DCFCE7; padding: 1px 5px; border-radius: 3px; text-transform: uppercase;">
                                                        <?php echo htmlspecialchars($dish_slot); ?>
                                                    </span>
                                                </td>
                                                <td style="padding: 7px 10px; text-align: right; font-weight: 700; color: #1C3826;">
                                                    × <?php echo $dish_qty; ?>
                                                </td>
                                                <td style="padding: 7px 10px; text-align: right; color: #64748B;">
                                                    <?php echo ($dish_sub <= 0) ? '<span style="color: #059669; font-weight: 600; font-size: 11px;">Complimentary</span>' : ('₹' . number_format($dish_sub, 0)); ?>
                                                </td>
                                                <td style="padding: 7px 10px; text-align: center;">
                                                    <select onchange="updateSingleDishStatus(<?php echo $kb['id']; ?>, <?php echo $dish_idx; ?>, this.value);" style="font-size: 10.5px; font-weight: 700; padding: 2px 6px; border-radius: 4px; border: 1px solid #CBD5E1; background: #FFFFFF; cursor: pointer;">
                                                        <option value="selected" <?php echo ($d_status === 'selected' || $d_status === 'queued') ? 'selected' : ''; ?>>🟡 Placed</option>
                                                        <option value="preparing" <?php echo ($d_status === 'preparing' || $d_status === 'cooking') ? 'selected' : ''; ?>>🟠 Preparing</option>
                                                        <option value="ready" <?php echo ($d_status === 'ready') ? 'selected' : ''; ?>>🟢 Ready</option>
                                                        <option value="served" <?php echo ($d_status === 'served') ? 'selected' : ''; ?>>✅ Served</option>
                                                    </select>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr style="border-top: 1.5px solid rgba(28, 56, 38, 0.15); background: rgba(28, 56, 38, 0.03);">
                                            <td colspan="3" style="padding: 8px 10px; font-weight: 700; color: #1C3826;">Ticket Total:</td>
                                            <td colspan="2" style="padding: 8px 10px; text-align: right; font-weight: 800; color: #1C3826; font-size: 13px;">
                                                ₹<?php echo number_format($ticket_subtotal, 0); ?>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Ticket Footer: Print Single KOT & Folio -->
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 10px; border-top: 1px dashed rgba(28, 56, 38, 0.15);">
                            <a href="print_bill.php?booking_id=<?php echo $kb['id']; ?>" target="_blank" style="font-size: 11.5px; color: #0E7490; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                <i class="fa-solid fa-file-invoice"></i> View Folio Bill
                            </a>
                            <button type="button" onclick="printSingleTicket(<?php echo $kb['id']; ?>);" style="background: none; border: 1px solid #CBD5E1; color: #334155; padding: 4px 10px; border-radius: 4px; font-size: 11.5px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                <i class="fa-solid fa-print"></i> KOT Slip
                            </button>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        </div>
    </div>

    <!-- 3. Walk-in / Multi-Item Order Creator for In-House Guests -->
    <div id="kitchen_walkin_section" class="adm-card kitchen-collapsible-card" style="margin-bottom: 22px; background: #FFFFFF; border: 1.5px solid rgba(14, 116, 144, 0.35); border-radius: 12px; overflow: hidden; box-shadow: 0 4px 14px rgba(14, 116, 144, 0.08);">
        
        <div class="kitchen-section-header" id="header_walkin_order" onclick="toggleKitchenMainSection('walkin_order');" style="cursor: pointer; user-select: none; padding: 18px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; background: #F0FDFA; transition: background 0.2s ease;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: #CCFBF1; color: #0E7490; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0;">
                    <i class="fa-solid fa-cart-flatbed-suitcase"></i>
                </div>
                <div>
                    <h4 class="font-serif" style="margin: 0; font-size: 1.3rem; color: #0E7490; display: flex; align-items: center; gap: 8px;">
                        Book &amp; Dispatch In-House Guest Food Orders (Multi-Item Cart)
                    </h4>
                    <p class="font-sans" style="font-size: 12px; color: #64748B; margin: 3px 0 0 0;">
                        Select guest room, add dishes with quantities to cart, and book directly into their kitchen ticket and folio bill.
                    </p>
                </div>
            </div>
            
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <span style="font-size: 12px; font-weight: 700; color: #0E7490; background: #E0F2FE; border: 1px solid #BAE6FD; padding: 4px 10px; border-radius: 6px;">
                    <i class="fa-solid fa-bell-concierge"></i> Take Room Order
                </span>
                <button type="button" class="section-toggle-btn" id="btn_toggle_walkin_order" onclick="event.stopPropagation(); toggleKitchenMainSection('walkin_order');">
                    <span id="txt_toggle_walkin_order">Expand</span>
                    <i class="fa-solid fa-chevron-down toggle-icon" id="icon_toggle_walkin_order"></i>
                </button>
            </div>
        </div>

        <!-- Collapsible Content for Walk-in Order Creator -->
        <div class="kitchen-section-body" id="body_walkin_order" style="display: none; padding: 22px; border-top: 1.5px solid #CCFBF1; background: #FFFFFF;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 14px; border-bottom: 1px solid #E2E8F0; padding-bottom: 10px;">
                <span style="font-size: 12px; font-weight: 700; color: #0E7490; text-transform: uppercase;">
                    <i class="fa-solid fa-utensils"></i> Menu Items &amp; Categories (66 Dishes)
                </span>
                <div style="display: flex; gap: 8px;">
                    <button type="button" onclick="expandAllMenuCategories();" class="adm-btn" style="padding: 5px 10px; font-size: 11.5px; background: #E0F2FE; color: #0369A1; border: 1px solid #BAE6FD; border-radius: 6px; cursor: pointer; font-weight: 600;">
                        <i class="fa-solid fa-square-plus"></i> Expand All Categories
                    </button>
                    <button type="button" onclick="collapseAllMenuCategories();" class="adm-btn" style="padding: 5px 10px; font-size: 11.5px; background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1; border-radius: 6px; cursor: pointer; font-weight: 600;">
                        <i class="fa-solid fa-square-minus"></i> Collapse All Categories
                    </button>
                </div>
            </div>

        <form id="walkin_dish_form" method="POST" action="kitchen.php?filter=<?php echo urlencode($filter); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&meal=<?php echo urlencode($meal_filter); ?>" style="margin-bottom: 18px;" onsubmit="return handleFormDirectSubmit(event);">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="quick_add_batch_dishes">
            <input type="hidden" name="items_payload" id="walkin_items_payload" value="[]">

            <!-- Top Selection Bar: In-House Guest & Order Controls -->
            <div style="background: #F8FAFC; border: 1.5px solid #CBD5E1; border-radius: 10px; padding: 14px 18px; margin-bottom: 16px; display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 14px; align-items: flex-end;">
                
                <!-- 1. Select In-House Guest -->
                <div style="grid-column: span 1;">
                    <label style="font-size: 12px; font-weight: 700; color: #1E293B; display: block; margin-bottom: 4px;">
                        1. Select In-House Room / Guest *
                    </label>
                    <select name="booking_id" id="walkin_booking_select" required class="adm-form-input font-sans" style="width: 100%; padding: 9px 10px; border-radius: 6px; border: 1.5px solid #94A3B8; font-size: 13px; font-weight: 600; color: #0F172A; background: #FFFFFF; transition: all 0.2s ease;">
                        <option value="">-- Choose In-House Room / Cottage --</option>
                        <?php if (empty($all_inhouse_bookings)): ?>
                            <option value="" disabled>-- No In-House Guests Currently Checked In --</option>
                        <?php else: ?>
                            <?php foreach ($all_inhouse_bookings as $mb): ?>
                                <option value="<?php echo $mb['id']; ?>">
                                    [#<?php echo htmlspecialchars($mb['reference_code']); ?>] <?php echo htmlspecialchars($mb['guest_name']); ?> (<?php echo format_chalet_label($mb['villa_type']); ?> — <?php echo date('d M', strtotime($mb['checkin_date'])); ?> to <?php echo date('d M', strtotime($mb['checkout_date'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- 2. Serving Meal Slot (Default for added items) -->
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: #1E293B; display: block; margin-bottom: 4px;">
                        2. Default Serving Meal Time *
                    </label>
                    <select name="dish_meal_time" id="quick_meal_select" onchange="onGlobalMealSlotChange(this.value);" required class="adm-form-input font-sans" style="width: 100%; padding: 9px 10px; border-radius: 6px; border: 1.5px solid #94A3B8; font-size: 13px; font-weight: 600; color: #0F172A; background: #FFFFFF;">
                        <option value="breakfast">☀️ Breakfast (09:00 AM – 10:00 AM)</option>
                        <option value="lunch" selected>🍛 Lunch (12:30 PM – 02:30 PM)</option>
                        <option value="snacks">☕ Evening Snacks (04:30 PM – 06:30 PM)</option>
                        <option value="dinner">🌙 Dinner (07:00 PM – 09:00 PM)</option>
                    </select>
                </div>

                <!-- 3. Special Instructions -->
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: #1E293B; display: block; margin-bottom: 4px;">
                        3. Chef / Kitchen Notes (Optional)
                    </label>
                    <input type="text" name="kitchen_notes" id="walkin_kitchen_notes" placeholder="e.g. Less spicy, deliver hot to cottage patio..." class="adm-form-input font-sans" style="width: 100%; padding: 8.5px 10px; border-radius: 6px; border: 1.5px solid #CBD5E1; font-size: 13px; color: #0F172A; background: #FFFFFF;">
                </div>
            </div>

            <!-- Interactive Kitchen Order Cart / Tray Box -->
            <div id="kitchen_order_cart_panel" style="background: #F0FDFA; border: 1.5px solid #0D9488; border-radius: 10px; padding: 16px; margin-bottom: 16px; box-shadow: 0 2px 10px rgba(13, 148, 136, 0.08);">
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-basket-shopping" style="color: #0E7490; font-size: 17px;"></i>
                        <span style="font-size: 13.5px; font-weight: 700; color: #0F172A;">Kitchen Order Cart</span>
                        <span id="kitchen_cart_count_badge" style="background: #0E7490; color: #FFFFFF; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 12px;">0 Dishes</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button type="button" onclick="clearKitchenCart();" id="btn_clear_kitchen_cart" style="display: none; background: #FEE2E2; color: #DC2626; border: 1px solid #FECACA; padding: 4px 10px; border-radius: 5px; font-size: 11.5px; font-weight: 600; cursor: pointer;">
                            <i class="fa-solid fa-trash-can"></i> Clear Cart
                        </button>
                    </div>
                </div>

                <!-- Empty State -->
                <div id="kitchen_cart_empty_state" style="text-align: center; padding: 20px 14px; background: #FFFFFF; border: 1px dashed #99F6E4; border-radius: 8px; color: #64748B;">
                    <i class="fa-solid fa-utensils" style="font-size: 24px; color: #94A3B8; margin-bottom: 6px; display: block;"></i>
                    <strong style="color: #0F172A; font-size: 13.5px;">Your order cart is currently empty</strong>
                    <p style="margin: 4px 0 0; font-size: 12px; color: #64748B;">
                        Scroll down to the menu categories below and tap <strong>[+ Add]</strong> on Masala Dosa, Puttu, Tea, Curry, etc. to pick multiple items with quantities.
                    </p>
                </div>

                <!-- Populated Cart Table -->
                <div id="kitchen_cart_table_wrap" style="display: none; background: #FFFFFF; border: 1px solid #CCFBF1; border-radius: 8px; overflow-x: auto; margin-bottom: 12px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 12.5px; text-align: left;">
                        <thead>
                            <tr style="background: #F8FAFC; border-bottom: 1px solid #E2E8F0; color: #475569; font-weight: 700;">
                                <th style="padding: 10px 12px;">Dish Name</th>
                                <th style="padding: 10px 10px; width: 190px;">Serving Meal Time</th>
                                <th style="padding: 10px 10px; width: 130px; text-align: center;">Quantity</th>
                                <th style="padding: 10px 10px; width: 90px; text-align: right;">Rate</th>
                                <th style="padding: 10px 12px; width: 100px; text-align: right;">Subtotal</th>
                                <th style="padding: 10px 10px; width: 45px; text-align: center;"></th>
                            </tr>
                        </thead>
                        <tbody id="kitchen_cart_table_body">
                            <!-- Populated via JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- Cart Summary & Dispatch Button -->
                <div id="kitchen_cart_footer_bar" style="display: none; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; padding-top: 6px;">
                    <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                        <div style="font-size: 12px; color: #475569;">
                            Items: <strong id="cart_total_items_qty" style="color: #0F172A;">0</strong>
                        </div>
                        <div style="font-size: 12px; color: #475569;">
                            Food Subtotal: <strong id="cart_food_subtotal_text" style="color: #0E7490;">₹0.00</strong>
                        </div>
                        <div style="font-size: 12px; color: #475569;">
                            Est. <?php echo ($gst_rate_food > 0) ? ($gst_rate_food . '%') : '0%'; ?> GST: <strong id="cart_gst_text" style="color: #64748B;">₹0.00</strong>
                        </div>
                        <div style="font-size: 13.5px; color: #047857; font-weight: 700;">
                            Net Added to Bill: <strong id="cart_grand_total_text" style="color: #065F46; font-size: 15px;">₹0.00</strong>
                        </div>
                    </div>

                    <div>
                        <button type="button" id="btn_dispatch_kitchen_cart" onclick="dispatchKitchenCartOrder();" style="height: 42px; padding: 0 22px; background: #0E7490; color: #FFFFFF; border: none; border-radius: 6px; font-weight: 700; font-size: 13.5px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 3px 10px rgba(14, 116, 144, 0.35); transition: background 0.2s ease;">
                            <i class="fa-solid fa-bell-concierge"></i>
                            <span id="dispatch_cart_btn_label">Book &amp; Dispatch Order to Ticket</span>
                        </button>
                    </div>
                </div>

            </div>

            <!-- Quick Dish Search Bar -->
            <div style="position: relative; margin-bottom: 14px;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 12px; color: #94A3B8; font-size: 13px;"></i>
                <input type="text" id="walkin_dish_search" oninput="filterWalkinDishes(this.value);" placeholder="🔍 Search any of the 66 dishes by name (e.g. Masala Dosa, Puttu, Biriyani, Tea, Banana Fry, Juices)..." class="adm-form-input font-sans" style="width: 100%; padding: 9px 12px 9px 34px; border-radius: 6px; border: 1px solid #CBD5E1; font-size: 13px; background: #FFFFFF;">
                <button type="button" id="clear_dish_search_btn" onclick="clearDishSearch();" style="display: none; position: absolute; right: 10px; top: 8px; background: none; border: none; color: #94A3B8; cursor: pointer; font-size: 16px;">&times;</button>
            </div>
        </form>

        <?php
        // Organize all 66 dishes into categorized groups
        $categories_meta = [
            'breakfast' => ['title' => 'Breakfast (പ്രഭാതഭക്ഷണം)', 'icon' => 'fa-mug-saucer', 'color' => '#D97706', 'bg' => '#FEF3C7', 'default_open' => false],
            'lunch' => ['title' => 'Lunch (ഉച്ചഭക്ഷണം)', 'icon' => 'fa-bowl-rice', 'color' => '#059669', 'bg' => '#D1FAE5', 'default_open' => false],
            'snacks' => ['title' => 'Evening Specials & Snacks (ചായ & ലഘുഭക്ഷണം)', 'icon' => 'fa-cookie-bite', 'color' => '#EA580C', 'bg' => '#FFEDD5', 'default_open' => false],
            'juices' => ['title' => 'Healthy Juices (ഹെൽത്തി ജ്യൂസുകൾ)', 'icon' => 'fa-glass-water', 'color' => '#0284C7', 'bg' => '#E0F2FE', 'default_open' => false],
            'millet' => ['title' => 'Millet Specials (മില്ലറ്റ് വിഭവങ്ങൾ)', 'icon' => 'fa-wheat-awn', 'color' => '#16A34A', 'bg' => '#DCFCE7', 'default_open' => false],
            'curries' => ['title' => 'Curries & Sides (കറികൾ)', 'icon' => 'fa-drumstick-bite', 'color' => '#DC2626', 'bg' => '#FEE2E2', 'default_open' => false],
            'dinner' => ['title' => 'Dinner (അത്താഴം)', 'icon' => 'fa-fire-burner', 'color' => '#7C3AED', 'bg' => '#EDE9FE', 'default_open' => false],
        ];

        $dishes_by_category = [];
        foreach ($all_dishes as $d) {
            $cat = $d['category'] ?? 'lunch';
            if (!isset($dishes_by_category[$cat])) {
                $dishes_by_category[$cat] = [];
            }
            $dishes_by_category[$cat][] = $d;
        }
        ?>

        <!-- Collapsible Category Accordion List with Headings & Plus/Minus Buttons -->
        <div class="menu-categories-accordion" id="menu_categories_accordion" style="display: flex; flex-direction: column; gap: 10px;">
            <?php foreach ($categories_meta as $cat_key => $meta): 
                $cat_dishes = $dishes_by_category[$cat_key] ?? [];
                if (empty($cat_dishes)) continue;
                $is_open = !empty($meta['default_open']);
            ?>
                <div class="category-accordion-item" id="cat-card-<?php echo $cat_key; ?>" style="border: 1.5px solid #E2E8F0; border-radius: 8px; overflow: hidden; background: #FFFFFF; transition: border-color 0.2s ease;">
                    
                    <!-- Accordion Category Header with [+] / [-] Toggle Button -->
                    <div class="cat-accordion-header" onclick="toggleMenuCategory('<?php echo $cat_key; ?>');" style="background: #F8FAFC; padding: 12px 16px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; user-select: none; border-bottom: <?php echo $is_open ? '1px solid #E2E8F0' : 'none'; ?>;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="width: 32px; height: 32px; border-radius: 6px; background: <?php echo $meta['bg']; ?>; color: <?php echo $meta['color']; ?>; display: inline-flex; align-items: center; justify-content: center; font-size: 14px;">
                                <i class="fa-solid <?php echo $meta['icon']; ?>"></i>
                            </span>
                            <div>
                                <span style="font-weight: 700; font-size: 13.5px; color: #1E293B;">
                                    <?php echo htmlspecialchars($meta['title']); ?>
                                </span>
                                <span style="margin-left: 8px; font-size: 11px; font-weight: 600; color: #64748B; background: #E2E8F0; padding: 2px 7px; border-radius: 10px;">
                                    <?php echo count($cat_dishes); ?> items
                                </span>
                            </div>
                        </div>

                        <!-- Plus / Minus Button with distinct circular styling -->
                        <button type="button" class="cat-toggle-btn" id="cat-btn-<?php echo $cat_key; ?>" aria-label="Toggle category" style="width: 28px; height: 28px; border-radius: 50%; border: 1.5px solid <?php echo $is_open ? $meta['color'] : '#94A3B8'; ?>; background: <?php echo $is_open ? $meta['bg'] : '#FFFFFF'; ?>; color: <?php echo $is_open ? $meta['color'] : '#475569'; ?>; display: flex; align-items: center; justify-content: center; font-size: 12px; cursor: pointer; transition: all 0.2s ease;">
                            <i class="fa-solid <?php echo $is_open ? 'fa-minus' : 'fa-plus'; ?>"></i>
                        </button>
                    </div>

                    <!-- Accordion Collapsible Body (Dishes Grid) -->
                    <div class="cat-accordion-body" id="cat-body-<?php echo $cat_key; ?>" style="display: <?php echo $is_open ? 'grid' : 'none'; ?>; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 10px; padding: 14px; background: #FAFAFA;">
                        <?php foreach ($cat_dishes as $dish): 
                            $is_veg = (($dish['dietary_type'] ?? 'veg') === 'veg');
                        ?>
                            <div class="dish-item-card" 
                                 id="dish-card-<?php echo $dish['id']; ?>"
                                 data-dish-id="<?php echo $dish['id']; ?>"
                                 data-dish-name="<?php echo htmlspecialchars($dish['heading'], ENT_QUOTES); ?>"
                                 data-dish-price="<?php echo (float)$dish['price']; ?>"
                                 data-dish-meal="<?php echo htmlspecialchars($dish['default_meal_time']); ?>"
                                 data-dish-category="<?php echo htmlspecialchars($dish['category']); ?>"
                                 data-dish-diet="<?php echo htmlspecialchars($dish['dietary_type'] ?? 'veg'); ?>"
                                 onclick="onDishCardClicked(<?php echo $dish['id']; ?>);"
                                 style="background: #FFFFFF; border: 1.5px solid #E2E8F0; border-radius: 8px; padding: 10px 12px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; gap: 8px; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                                
                                <div style="display: flex; align-items: flex-start; gap: 8px; flex: 1;">
                                    <span style="color: <?php echo $is_veg ? '#16A34A' : '#DC2626'; ?>; font-size: 11px; margin-top: 2px;">●</span>
                                    <div>
                                        <div class="dish-name-label" style="font-size: 13px; font-weight: 600; color: #1E293B; line-height: 1.3;">
                                            <?php echo htmlspecialchars($dish['heading']); ?>
                                        </div>
                                        <div style="font-size: 11px; color: #64748B; margin-top: 2px; text-transform: capitalize;">
                                            <i class="fa-solid fa-clock" style="font-size: 9px; margin-right: 3px;"></i> <?php echo htmlspecialchars($dish['default_meal_time']); ?>
                                        </div>
                                    </div>
                                </div>

                                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;" onclick="event.stopPropagation();">
                                    <span style="font-size: 12.5px; font-weight: 700; color: #0E7490;">
                                        ₹<?php echo number_format($dish['price'], 0); ?>
                                    </span>
                                    <div id="dish_ctrl_box_<?php echo $dish['id']; ?>">
                                        <button type="button" class="dish-select-btn" onclick="addDishToCart(<?php echo $dish['id']; ?>);" style="padding: 3px 10px; font-size: 11px; font-weight: 700; background: #F0FDFA; color: #0E7490; border: 1px solid #99F6E4; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: all 0.15s ease;">
                                            <i class="fa-solid fa-plus"></i> Add
                                        </button>
                                    </div>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
        </div>

    </div>

    <!-- Floating Bottom Quick-Cart Dock (Appears when scrolled past cart) -->
    <div id="floating_kitchen_cart_dock" style="display: none; position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%); z-index: 9998; background: #0E7490; color: #FFFFFF; border: 1.5px solid #0891B2; padding: 10px 22px; border-radius: 30px; box-shadow: 0 10px 30px rgba(14, 116, 144, 0.45); align-items: center; gap: 16px; font-family: var(--font-sans); transition: all 0.25s ease;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-basket-shopping" style="font-size: 16px; color: #A5F3FC;"></i>
            <span style="font-size: 13px; font-weight: 700;"><span id="dock_item_count">0</span> items in Cart</span>
            <span style="color: rgba(255,255,255,0.4);">•</span>
            <span id="dock_total_amount" style="font-size: 14px; font-weight: 800; color: #ECFDF5;">₹0.00</span>
        </div>
        <button type="button" onclick="scrollToKitchenCart();" style="background: #FFFFFF; color: #0E7490; border: none; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
            <i class="fa-solid fa-arrow-up"></i> Review Cart &amp; Book
        </button>
    </div>

</div>

<!-- Print Stylesheet for Physical Kitchen Printouts (Thermal / A4) -->
<style>
@media print {
    /* Hide admin shell navigation, header, buttons and sidebars */
    .adm-sidebar, .adm-topbar, .adm-section-header button, .adm-section-header a, .adm-card:has(form), .adm-meal-tabs-bar, .adm-mobile-bottom-dock, .btn-date-filter-pill, .menu-categories-accordion {
        display: none !important;
    }
    .adm-main-layout, .adm-content-body, body {
        background: #FFFFFF !important;
        color: #000000 !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .adm-kitchen-wrap {
        padding: 10px !important;
    }
    .adm-card {
        border: 1px solid #000000 !important;
        box-shadow: none !important;
        page-break-inside: avoid;
        margin-bottom: 15px !important;
    }
    .kitchen-ticket-card {
        border: 2px dashed #000000 !important;
        padding: 10px !important;
    }
}

.dish-item-card:hover {
    border-color: #0E7490 !important;
    box-shadow: 0 3px 8px rgba(14, 116, 144, 0.12) !important;
    transform: translateY(-1px);
}
.dish-item-card.is-in-cart {
    border-color: #0E7490 !important;
    background: #F0FDFA !important;
    box-shadow: 0 2px 10px rgba(14, 116, 144, 0.2) !important;
}
.dish-cart-stepper {
    display: inline-flex;
    align-items: center;
    background: #0E7490;
    color: #FFFFFF;
    border-radius: 5px;
    overflow: hidden;
    height: 26px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12);
}
.dish-cart-stepper button {
    width: 24px;
    height: 26px;
    background: transparent;
    border: none;
    color: #FFFFFF;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s;
}
.dish-cart-stepper button:hover {
    background: rgba(255, 255, 255, 0.25);
}
.dish-cart-stepper span {
    min-width: 22px;
    text-align: center;
    font-weight: 700;
    font-size: 12.5px;
}
.cat-toggle-btn {
    pointer-events: none;
}

/* Collapsible Kitchen Sections */
.kitchen-section-header {
    cursor: pointer;
    user-select: none;
    transition: background 0.2s ease, filter 0.2s ease;
}
.kitchen-section-header:hover {
    filter: brightness(0.97);
}
.section-toggle-btn {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    border: 1.5px solid #CBD5E1;
    background: #FFFFFF;
    color: #334155;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    cursor: pointer;
    transition: all 0.2s ease;
}
.section-toggle-btn:hover {
    border-color: #0E7490;
    color: #0E7490;
    background: #F0FDFA;
}
.section-toggle-btn.is-open {
    background: #E0F2FE;
    color: #0369A1;
    border-color: #7DD3FC;
}
.section-toggle-btn .toggle-icon {
    transition: transform 0.25s ease;
}
.section-toggle-btn.is-open .toggle-icon {
    transform: rotate(180deg);
}
.adm-btn-section-shortcut {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    background: #FFFFFF;
    color: #334155;
    border: 1px solid #CBD5E1;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s ease;
}
.adm-btn-section-shortcut:hover {
    border-color: #0E7490;
    color: #0E7490;
    background: #F0FDFA;
}
.adm-btn-section-shortcut.active-shortcut {
    background: #1C3826;
    color: #FFFFFF;
    border-color: #1C3826;
}
.adm-btn-section-shortcut.active-shortcut i {
    color: #FBBF24 !important;
}
.adm-btn-section-shortcut.order-btn-highlight {
    background: #F0FDFA;
    border-color: #99F6E4;
    color: #0E7490;
    font-weight: 700;
}
.adm-btn-section-shortcut.order-btn-highlight:hover {
    background: #0E7490;
    color: #FFFFFF;
}
.adm-btn-section-shortcut.order-btn-highlight:hover i {
    color: #FFFFFF !important;
}

@media print {
    .kitchen-section-body {
        display: block !important;
    }
    .section-toggle-btn, .kitchen-sections-quicknav, #floating_kitchen_cart_dock {
        display: none !important;
    }
}
</style>

<script>
// Food Dishes Dictionary
var allFoodDishesMap = <?php 
    $dishes_dict = [];
    foreach ($all_dishes as $d) {
        $dishes_dict[$d['id']] = $d;
    }
    echo json_encode($dishes_dict, JSON_UNESCAPED_UNICODE); 
?>;

// Kitchen Order Cart State
var kitchenCart = [];
var foodGstRate = <?php echo json_encode($gst_rate_food); ?>;

// Global default meal slot
function getSelectedMealSlot() {
    var sel = document.getElementById('quick_meal_select');
    return sel ? sel.value : 'lunch';
}

function onGlobalMealSlotChange(newSlot) {
    // Optional: could notify or update new additions
}

// Add Dish to Cart (or increment qty if already present)
function addDishToCart(dishId) {
    dishId = parseInt(dishId, 10);
    var dish = allFoodDishesMap[dishId];
    if (!dish) return;

    var existing = kitchenCart.find(function(it) {
        return it.dishId === dishId;
    });

    var defSlot = getSelectedMealSlot() || dish.default_meal_time || 'lunch';

    if (existing) {
        existing.quantity = Math.min(50, existing.quantity + 1);
    } else {
        kitchenCart.push({
            dishId: dishId,
            dishName: dish.heading,
            price: parseFloat(dish.price) || 0,
            quantity: 1,
            mealSlot: defSlot,
            dietaryType: dish.dietary_type || 'veg',
            category: dish.category || 'lunch'
        });
    }

    renderKitchenCart();
    showKitchenToast('✓ Added ' + dish.heading + ' to cart', true);
}

// Clicking card body
function onDishCardClicked(dishId) {
    addDishToCart(dishId);
}

// Adjust quantity of item in cart
function adjustDishCartQty(dishId, delta) {
    dishId = parseInt(dishId, 10);
    var idx = kitchenCart.findIndex(function(it) {
        return it.dishId === dishId;
    });
    if (idx === -1) return;

    var newQty = kitchenCart[idx].quantity + delta;
    if (newQty <= 0) {
        var removedName = kitchenCart[idx].dishName;
        kitchenCart.splice(idx, 1);
        showKitchenToast('Removed ' + removedName + ' from cart', false);
    } else {
        kitchenCart[idx].quantity = Math.min(50, newQty);
    }

    renderKitchenCart();
}

// Change item meal slot in table
function changeCartItemMealSlot(dishId, newSlot) {
    dishId = parseInt(dishId, 10);
    var item = kitchenCart.find(function(it) {
        return it.dishId === dishId;
    });
    if (item) {
        item.mealSlot = newSlot;
        renderKitchenCart();
    }
}

// Remove item from cart
function removeCartItem(dishId) {
    adjustDishCartQty(dishId, -999);
}

// Clear entire kitchen cart
function clearKitchenCart(showToast) {
    if (showToast === undefined) showToast = true;
    if (kitchenCart.length === 0) return;
    kitchenCart = [];
    renderKitchenCart();
    if (showToast) {
        showKitchenToast('Cart cleared', false);
    }
}

// Render Cart UI and sync all dish card states
function renderKitchenCart() {
    var totalCount = 0;
    var totalQty = 0;
    var foodSubtotal = 0;

    var tbody = document.getElementById('kitchen_cart_table_body');
    var emptyBox = document.getElementById('kitchen_cart_empty_state');
    var tableWrap = document.getElementById('kitchen_cart_table_wrap');
    var footerBar = document.getElementById('kitchen_cart_footer_bar');
    var countBadge = document.getElementById('kitchen_cart_count_badge');
    var clearBtn = document.getElementById('btn_clear_kitchen_cart');
    var payloadInput = document.getElementById('walkin_items_payload');

    var dock = document.getElementById('floating_kitchen_cart_dock');
    var dockCount = document.getElementById('dock_item_count');
    var dockTotal = document.getElementById('dock_total_amount');

    if (tbody) {
        var html = '';
        kitchenCart.forEach(function(item) {
            totalCount++;
            totalQty += item.quantity;
            var lineSubtotal = item.quantity * item.price;
            foodSubtotal += lineSubtotal;

            var isVeg = (item.dietaryType === 'veg');
            var dotColor = isVeg ? '#16A34A' : '#DC2626';

            html += '<tr style="border-bottom: 1px solid #F1F5F9;">';
            
            // Name
            html += '<td style="padding: 9px 12px; font-weight: 600; color: #1E293B;">';
            html += '<span style="color:' + dotColor + '; font-size:11px; margin-right:6px;">●</span>';
            html += item.dishName;
            html += '</td>';

            // Meal Slot Dropdown
            html += '<td style="padding: 9px 10px;">';
            html += '<select onchange="changeCartItemMealSlot(' + item.dishId + ', this.value);" style="font-size:12px; font-weight:600; padding:4px 8px; border-radius:5px; border:1.5px solid #CBD5E1; background:#FFFFFF; color:#0F172A; width:100%;">';
            var slots = [
                { id: 'breakfast', label: '☀️ Breakfast' },
                { id: 'lunch', label: '🍛 Lunch' },
                { id: 'snacks', label: '☕ Evening Snacks' },
                { id: 'dinner', label: '🌙 Dinner' }
            ];
            slots.forEach(function(s) {
                var sel = (item.mealSlot === s.id) ? ' selected' : '';
                html += '<option value="' + s.id + '"' + sel + '>' + s.label + '</option>';
            });
            html += '</select>';
            html += '</td>';

            // Quantity Stepper
            html += '<td style="padding: 9px 10px; text-align: center;">';
            html += '<div style="display:inline-flex; align-items:center; border:1px solid #CBD5E1; border-radius:5px; overflow:hidden; background:#F8FAFC;">';
            html += '<button type="button" onclick="adjustDishCartQty(' + item.dishId + ', -1);" style="width:28px; height:28px; border:none; background:transparent; font-weight:700; color:#475569; cursor:pointer;">−</button>';
            html += '<span style="width:28px; text-align:center; font-weight:700; font-size:13px; color:#0F172A;">' + item.quantity + '</span>';
            html += '<button type="button" onclick="adjustDishCartQty(' + item.dishId + ', 1);" style="width:28px; height:28px; border:none; background:transparent; font-weight:700; color:#475569; cursor:pointer;">+</button>';
            html += '</div>';
            html += '</td>';

            // Unit Price
            html += '<td style="padding: 9px 10px; text-align: right; color:#64748B; font-weight:600;">₹' + item.price.toLocaleString('en-IN') + '</td>';

            // Subtotal
            html += '<td style="padding: 9px 12px; text-align: right; font-weight:700; color:#0E7490;">₹' + lineSubtotal.toLocaleString('en-IN') + '</td>';

            // Delete
            html += '<td style="padding: 9px 10px; text-align: center;">';
            html += '<button type="button" onclick="removeCartItem(' + item.dishId + ');" style="background:none; border:none; color:#EF4444; font-size:16px; cursor:pointer; padding:4px;" title="Remove dish">&times;</button>';
            html += '</td>';

            html += '</tr>';
        });

        tbody.innerHTML = html;
    }

    // Toggle states
    if (totalCount > 0) {
        if (emptyBox) emptyBox.style.display = 'none';
        if (tableWrap) tableWrap.style.display = 'block';
        if (footerBar) footerBar.style.display = 'flex';
        if (clearBtn) clearBtn.style.display = 'inline-flex';
    } else {
        if (emptyBox) emptyBox.style.display = 'block';
        if (tableWrap) tableWrap.style.display = 'none';
        if (footerBar) footerBar.style.display = 'none';
        if (clearBtn) clearBtn.style.display = 'none';
    }

    // Stats
    var gst = Math.round((foodSubtotal * (foodGstRate / 100)) * 100) / 100;
    var grandTotal = Math.round((foodSubtotal + gst) * 100) / 100;

    if (countBadge) countBadge.textContent = totalCount + (totalCount === 1 ? ' Dish' : ' Dishes');
    var itemsQtyEl = document.getElementById('cart_total_items_qty');
    if (itemsQtyEl) itemsQtyEl.textContent = totalCount + ' dishes (' + totalQty + ' portions)';

    var subEl = document.getElementById('cart_food_subtotal_text');
    if (subEl) subEl.textContent = '₹' + foodSubtotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    var gstEl = document.getElementById('cart_gst_text');
    if (gstEl) gstEl.textContent = '₹' + gst.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    var totEl = document.getElementById('cart_grand_total_text');
    if (totEl) totEl.textContent = '₹' + grandTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    var btnLbl = document.getElementById('dispatch_cart_btn_label');
    if (btnLbl) {
        btnLbl.textContent = 'Book & Dispatch ' + totalCount + ' Dishes (₹' + grandTotal.toLocaleString('en-IN') + ') to Ticket';
    }

    if (payloadInput) {
        payloadInput.value = JSON.stringify(kitchenCart);
    }

    // Update Floating Dock
    if (dock && dockCount && dockTotal) {
        dockCount.textContent = totalCount;
        dockTotal.textContent = '₹' + grandTotal.toLocaleString('en-IN');
        checkFloatingDockVisibility();
    }

    // Synchronize All Menu Cards in Accordion
    syncDishCardsUI();
}

// Synchronize Dish Cards in Accordion to reflect cart items and quantities
function syncDishCardsUI() {
    var cartMap = {};
    kitchenCart.forEach(function(it) {
        cartMap[it.dishId] = it.quantity;
    });

    document.querySelectorAll('.dish-item-card').forEach(function(card) {
        var dishId = parseInt(card.getAttribute('data-dish-id'), 10);
        var ctrlBox = document.getElementById('dish_ctrl_box_' + dishId);
        var inCartQty = cartMap[dishId] || 0;

        if (inCartQty > 0) {
            card.classList.add('is-in-cart');
            if (ctrlBox) {
                ctrlBox.innerHTML = '<div class="dish-cart-stepper" onclick="event.stopPropagation();">' +
                    '<button type="button" onclick="adjustDishCartQty(' + dishId + ', -1);" title="Decrease">−</button>' +
                    '<span>' + inCartQty + '</span>' +
                    '<button type="button" onclick="adjustDishCartQty(' + dishId + ', 1);" title="Increase">+</button>' +
                    '</div>';
            }
        } else {
            card.classList.remove('is-in-cart');
            if (ctrlBox) {
                ctrlBox.innerHTML = '<button type="button" class="dish-select-btn" onclick="event.stopPropagation(); addDishToCart(' + dishId + ');" style="padding: 3px 10px; font-size: 11px; font-weight: 700; background: #F0FDFA; color: #0E7490; border: 1px solid #99F6E4; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: all 0.15s ease;">' +
                    '<i class="fa-solid fa-plus"></i> Add' +
                    '</button>';
            }
        }
    });
}

// Scroll to cart panel
function scrollToKitchenCart() {
    var p = document.getElementById('kitchen_order_cart_panel');
    if (p) {
        p.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// Check Floating Dock visibility on scroll
function checkFloatingDockVisibility() {
    var dock = document.getElementById('floating_kitchen_cart_dock');
    var cartPanel = document.getElementById('kitchen_order_cart_panel');
    if (!dock || !cartPanel) return;

    if (kitchenCart.length === 0) {
        dock.style.display = 'none';
        return;
    }

    var rect = cartPanel.getBoundingClientRect();
    if (rect.bottom < 100) {
        dock.style.display = 'flex';
    } else {
        dock.style.display = 'none';
    }
}
window.addEventListener('scroll', checkFloatingDockVisibility, { passive: true });

// Dispatch Cart Order via AJAX
async function dispatchKitchenCartOrder() {
    var roomSelect = document.getElementById('walkin_booking_select');
    if (!roomSelect || !roomSelect.value) {
        if (roomSelect) {
            roomSelect.style.borderColor = '#EF4444';
            roomSelect.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.25)';
            roomSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
            roomSelect.focus();
            setTimeout(function() {
                roomSelect.style.borderColor = '';
                roomSelect.style.boxShadow = '';
            }, 3000);
        }
        showKitchenToast('Please choose an in-house room / resident guest first!', false);
        return;
    }

    if (kitchenCart.length === 0) {
        showKitchenToast('Your cart is empty! Pick dishes with [+] from the menu below.', false);
        return;
    }

    var btn = document.getElementById('btn_dispatch_kitchen_cart');
    var origHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Booking &amp; Dispatching...';
    }

    var notesInput = document.getElementById('walkin_kitchen_notes');
    var notes = notesInput ? notesInput.value.trim() : '';

    try {
        var formData = new FormData();
        formData.append('csrf_token', '<?php echo csrf_token(); ?>');
        formData.append('action', 'quick_add_batch_dishes');
        formData.append('booking_id', roomSelect.value);
        formData.append('kitchen_notes', notes);
        formData.append('items_payload', JSON.stringify(kitchenCart));

        var res = await fetch('kitchen.php?filter=<?php echo urlencode($filter); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&meal=<?php echo urlencode($meal_filter); ?>', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });

        var data = await res.json();
        if (data.success) {
            clearKitchenCart(false);
            if (notesInput) notesInput.value = '';
            showKitchenToast('✓ ' + (data.message || 'Order successfully added to guest ticket!'), true);
            
            // Reload page smoothly to display the updated live ticket and preparation summary
            setTimeout(function() {
                window.location.reload();
            }, 1000);
        } else {
            showKitchenToast(data.message || 'Error dispatching order', false);
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = origHtml;
            }
        }
    } catch (err) {
        // Fallback to normal form submit if fetch fails
        var form = document.getElementById('walkin_dish_form');
        if (form) {
            form.submit();
        }
    }
}

// Fallback form submit
function handleFormDirectSubmit(e) {
    var roomSelect = document.getElementById('walkin_booking_select');
    if (!roomSelect || !roomSelect.value) {
        e.preventDefault();
        roomSelect.focus();
        showKitchenToast('Please choose an in-house room / resident guest first!', false);
        return false;
    }
    if (kitchenCart.length === 0) {
        e.preventDefault();
        showKitchenToast('Please add at least one dish to the cart.', false);
        return false;
    }
    var payloadInput = document.getElementById('walkin_items_payload');
    if (payloadInput) {
        payloadInput.value = JSON.stringify(kitchenCart);
    }
    return true;
}

// Toggle Single Category Accordion with Plus/Minus
function toggleMenuCategory(catKey) {
    var body = document.getElementById('cat-body-' + catKey);
    var btn = document.getElementById('cat-btn-' + catKey);
    var card = document.getElementById('cat-card-' + catKey);
    var header = card ? card.querySelector('.cat-accordion-header') : null;

    if (!body || !btn) return;

    var currentDisplay = window.getComputedStyle(body).display;
    var isOpen = (currentDisplay !== 'none');

    if (isOpen) {
        body.style.display = 'none';
        btn.innerHTML = '<i class="fa-solid fa-plus"></i>';
        btn.style.background = '#FFFFFF';
        btn.style.color = '#475569';
        btn.style.borderColor = '#94A3B8';
        if (header) header.style.borderBottom = 'none';
    } else {
        body.style.display = 'grid';
        btn.innerHTML = '<i class="fa-solid fa-minus"></i>';
        btn.style.background = '#E0F2FE';
        btn.style.color = '#0284C7';
        btn.style.borderColor = '#0284C7';
        if (header) header.style.borderBottom = '1px solid #E2E8F0';
    }
}

// Expand All Categories
function expandAllMenuCategories() {
    document.querySelectorAll('.cat-accordion-body').forEach(function(b) {
        b.style.display = 'grid';
    });
    document.querySelectorAll('.cat-toggle-btn').forEach(function(btn) {
        btn.innerHTML = '<i class="fa-solid fa-minus"></i>';
        btn.style.background = '#E0F2FE';
        btn.style.color = '#0284C7';
        btn.style.borderColor = '#0284C7';
    });
    document.querySelectorAll('.cat-accordion-header').forEach(function(h) {
        h.style.borderBottom = '1px solid #E2E8F0';
    });
}

// =========================================================================
// Main Kitchen Section Collapsible Controls (Chef Preparation, Tickets, Walk-in)
// =========================================================================
function toggleKitchenMainSection(secKey, forceOpen, autoScroll) {
    var body = document.getElementById('body_' + secKey);
    var btn = document.getElementById('btn_toggle_' + secKey);
    var txt = document.getElementById('txt_toggle_' + secKey);
    var header = document.getElementById('header_' + secKey);
    var shortcut = document.getElementById('shortcut_btn_' + secKey);
    if (!body) return;

    var isCurrentlyOpen = (body.style.display !== 'none');
    var shouldOpen = (typeof forceOpen === 'boolean') ? forceOpen : !isCurrentlyOpen;

    if (shouldOpen) {
        body.style.display = 'block';
        if (btn) {
            btn.classList.add('is-open');
            if (txt) txt.textContent = 'Collapse';
        }
        if (shortcut) {
            shortcut.classList.add('active-shortcut');
        }
        try {
            sessionStorage.setItem('ff_kitchen_sec_' + secKey, 'open');
        } catch(e) {}

        if (autoScroll) {
            setTimeout(function() {
                var el = document.getElementById('section_' + secKey) || document.getElementById('kitchen_' + secKey) || header;
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 80);
        }
    } else {
        body.style.display = 'none';
        if (btn) {
            btn.classList.remove('is-open');
            if (txt) txt.textContent = 'Expand';
        }
        if (shortcut) {
            shortcut.classList.remove('active-shortcut');
        }
        try {
            sessionStorage.setItem('ff_kitchen_sec_' + secKey, 'closed');
        } catch(e) {}
    }
}

function expandAllKitchenSections() {
    ['prep_summary', 'order_tickets', 'walkin_order'].forEach(function(k) {
        toggleKitchenMainSection(k, true, false);
    });
}

function collapseAllKitchenSections() {
    ['prep_summary', 'order_tickets', 'walkin_order'].forEach(function(k) {
        toggleKitchenMainSection(k, false, false);
    });
}

// Auto-expand section if requested in URL anchor or query
document.addEventListener('DOMContentLoaded', function() {
    var urlParams = new URLSearchParams(window.location.search);
    var hash = window.location.hash;
    if (urlParams.get('open') === 'order' || urlParams.get('action') === 'order' || hash === '#kitchen_walkin_section') {
        toggleKitchenMainSection('walkin_order', true, true);
    }
});

// Collapse All Categories
function collapseAllMenuCategories() {
    document.querySelectorAll('.cat-accordion-body').forEach(function(b) {
        b.style.display = 'none';
    });
    document.querySelectorAll('.cat-toggle-btn').forEach(function(btn) {
        btn.innerHTML = '<i class="fa-solid fa-plus"></i>';
        btn.style.background = '#FFFFFF';
        btn.style.color = '#475569';
        btn.style.borderColor = '#94A3B8';
    });
    document.querySelectorAll('.cat-accordion-header').forEach(function(h) {
        h.style.borderBottom = 'none';
    });
}

// Real-time Search Across 66 Dishes
function filterWalkinDishes(query) {
    var q = (query || '').toLowerCase().trim();
    var clearBtn = document.getElementById('clear_dish_search_btn');
    if (clearBtn) clearBtn.style.display = q.length > 0 ? 'block' : 'none';

    var allCards = document.querySelectorAll('.dish-item-card');
    var categories = document.querySelectorAll('.category-accordion-item');

    if (!q) {
        allCards.forEach(function(c) { c.style.display = 'flex'; });
        categories.forEach(function(cat) { 
            cat.style.display = 'block'; 
        });
        return;
    }

    allCards.forEach(function(card) {
        var name = (card.getAttribute('data-dish-name') || '').toLowerCase();
        var cat = (card.getAttribute('data-dish-category') || '').toLowerCase();
        var match = (name.indexOf(q) !== -1 || cat.indexOf(q) !== -1);
        card.style.display = match ? 'flex' : 'none';
    });

    categories.forEach(function(cat) {
        var visibleCount = 0;
        cat.querySelectorAll('.dish-item-card').forEach(function(c) {
            if (c.style.display !== 'none') visibleCount++;
        });

        if (visibleCount > 0) {
            cat.style.display = 'block';
            var body = cat.querySelector('.cat-accordion-body');
            var btn = cat.querySelector('.cat-toggle-btn');
            var header = cat.querySelector('.cat-accordion-header');
            if (body) body.style.display = 'grid';
            if (btn) {
                btn.innerHTML = '<i class="fa-solid fa-minus"></i>';
                btn.style.background = '#E0F2FE';
                btn.style.color = '#0284C7';
                btn.style.borderColor = '#0284C7';
            }
            if (header) header.style.borderBottom = '1px solid #E2E8F0';
        } else {
            cat.style.display = 'none';
        }
    });
}

function clearDishSearch() {
    var input = document.getElementById('walkin_dish_search');
    if (input) {
        input.value = '';
        filterWalkinDishes('');
        input.focus();
    }
}

// Auto update meal select when dish changes
document.getElementById('quick_dish_select')?.addEventListener('change', function() {
    var opt = this.options[this.selectedIndex];
    var defMeal = opt.getAttribute('data-default-meal');
    var mealSel = document.getElementById('quick_meal_select');
    if (defMeal && mealSel) {
        mealSel.value = defMeal;
    }
});

// Single ticket print function
function printSingleTicket(bookingId) {
    var card = document.getElementById('kitchen-card-' + bookingId);
    if (!card) return;
    var w = window.open('', '_blank', 'width=500,height=600');
    w.document.write('<!DOCTYPE html><html><head><title>Kitchen Order Ticket</title>');
    w.document.write('<style>body{font-family: monospace; padding: 20px; color:#000;} table{width:100%; border-collapse:collapse;} th,td{padding:6px; border-bottom:1px dashed #000; text-align:left;} th:last-child,td:last-child{text-align:right;} .header{text-align:center; border-bottom:2px solid #000; padding-bottom:10px; margin-bottom:10px;}</style>');
    w.document.write('</head><body>');
    w.document.write('<div class="header"><h2>FOOD FOREST SANCTUARY</h2><p>KITCHEN ORDER TICKET (KOT)</p><p>' + new Date().toLocaleString() + '</p></div>');
    w.document.write(card.innerHTML);
    w.document.write('<script>window.onload=function(){window.print(); window.close();};<\/script>');
    w.document.write('</body></html>');
    w.document.close();
}

// Instant Toast Notification
function showKitchenToast(message, isSuccess) {
    var toast = document.getElementById('adm-kitchen-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'adm-kitchen-toast';
        toast.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:99999;padding:12px 20px;border-radius:8px;font-size:13.5px;font-weight:600;display:flex;align-items:center;gap:10px;box-shadow:0 8px 24px rgba(0,0,0,0.18);transition:all 0.25s ease;transform:translateY(100px);opacity:0;';
        document.body.appendChild(toast);
    }
    toast.style.background = isSuccess ? '#064E3B' : '#7F1D1D';
    toast.style.color = '#FFFFFF';
    toast.style.border = isSuccess ? '1.5px solid #10B981' : '1.5px solid #EF4444';
    toast.innerHTML = (isSuccess ? '<i class="fa-solid fa-circle-check" style="color:#34D399;font-size:16px;"></i> ' : '<i class="fa-solid fa-circle-exclamation" style="color:#F87171;font-size:16px;"></i> ') + '<span>' + message + '</span>';

    toast.style.transform = 'translateY(0)';
    toast.style.opacity = '1';

    clearTimeout(window._kitchenToastTimer);
    window._kitchenToastTimer = setTimeout(function() {
        toast.style.transform = 'translateY(100px)';
        toast.style.opacity = '0';
    }, 4000);
}

// 1-Click Status Update for Kitchen Order Ticket
async function updateTicketKitchenStatus(bookingId, newStatus, label) {
    var group = document.getElementById('ticket-stage-group-' + bookingId);
    var badge = document.getElementById('ticket-current-badge-' + bookingId);

    if (badge) {
        badge.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Updating...';
    }

    try {
        var formData = new FormData();
        formData.append('csrf_token', '<?php echo csrf_token(); ?>');
        formData.append('action', 'update_food_status');
        formData.append('booking_id', bookingId);
        formData.append('new_food_status', newStatus);

        var res = await fetch('kitchen.php?filter=<?php echo urlencode($filter); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&meal=<?php echo urlencode($meal_filter); ?>', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });

        var data = await res.json();
        if (data.success) {
            var badgeStyles = {
                'selected': { bg: '#F1F5F9', color: '#475569', border: '#94A3B8', text: '🟡 Order Placed' },
                'preparing': { bg: '#FFFBEB', color: '#B45309', border: '#F59E0B', text: '🟠 Preparing to Cook' },
                'ready': { bg: '#F0FDF4', color: '#15803D', border: '#22C55E', text: '🟢 Ready to Serve' },
                'served': { bg: '#ECFDF5', color: '#065F46', border: '#10B981', text: '✅ Delivered / Served' }
            };
            var style = badgeStyles[newStatus] || badgeStyles['selected'];
            if (badge) {
                badge.style.background = style.bg;
                badge.style.color = style.color;
                badge.style.borderColor = style.border;
                badge.innerHTML = style.text;
            }

            if (group) {
                var btns = group.querySelectorAll('.btn-kitchen-stage');
                btns.forEach(function(b) {
                    b.classList.remove('active-stage');
                    b.style.background = '#FFFFFF';
                    b.style.color = '#475569';
                    b.style.borderColor = '#CBD5E1';
                });

                var activeIdx = ['selected', 'preparing', 'ready', 'served'].indexOf(newStatus);
                if (activeIdx !== -1 && btns[activeIdx]) {
                    var ab = btns[activeIdx];
                    ab.classList.add('active-stage');
                    if (newStatus === 'selected') {
                        ab.style.background = '#334155'; ab.style.color = '#FFFFFF'; ab.style.borderColor = '#94A3B8';
                    } else if (newStatus === 'preparing') {
                        ab.style.background = '#D97706'; ab.style.color = '#FFFFFF'; ab.style.borderColor = '#D97706';
                    } else if (newStatus === 'ready') {
                        ab.style.background = '#059669'; ab.style.color = '#FFFFFF'; ab.style.borderColor = '#059669';
                    } else if (newStatus === 'served') {
                        ab.style.background = '#1C3826'; ab.style.color = '#FFFFFF'; ab.style.borderColor = '#1C3826';
                    }
                }
            }

            // Sync all item selects in this ticket
            var card = document.getElementById('kitchen-card-' + bookingId);
            if (card) {
                card.querySelectorAll('tbody select').forEach(function(sel) {
                    sel.value = newStatus;
                });
            }

            showKitchenToast(data.message || ('Status updated to ' + label), true);
        } else {
            showKitchenToast(data.message || 'Error updating status', false);
        }
    } catch (e) {
        // Fallback: form submit
        var fbForm = document.createElement('form');
        fbForm.method = 'POST';
        fbForm.action = 'kitchen.php?filter=<?php echo urlencode($filter); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&meal=<?php echo urlencode($meal_filter); ?>';
        fbForm.innerHTML = '<?php echo csrf_field(); ?>' +
            '<input type="hidden" name="action" value="update_food_status">' +
            '<input type="hidden" name="booking_id" value="' + bookingId + '">' +
            '<input type="hidden" name="new_food_status" value="' + newStatus + '">';
        document.body.appendChild(fbForm);
        fbForm.submit();
    }
}

// Update Single Dish Status
async function updateSingleDishStatus(bookingId, itemIndex, newStatus) {
    try {
        var formData = new FormData();
        formData.append('csrf_token', '<?php echo csrf_token(); ?>');
        formData.append('action', 'update_dish_status');
        formData.append('booking_id', bookingId);
        formData.append('item_index', itemIndex);
        formData.append('new_dish_status', newStatus);

        var res = await fetch('kitchen.php?filter=<?php echo urlencode($filter); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&meal=<?php echo urlencode($meal_filter); ?>', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: formData
        });
        var data = await res.json();
        if (data.success) {
            showKitchenToast(data.message || 'Dish status updated', true);
            if (data.overall_status) {
                var badgeStyles = {
                    'selected': { bg: '#F1F5F9', color: '#475569', border: '#94A3B8', text: '🟡 Order Placed' },
                    'preparing': { bg: '#FFFBEB', color: '#B45309', border: '#F59E0B', text: '🟠 Preparing to Cook' },
                    'ready': { bg: '#F0FDF4', color: '#15803D', border: '#22C55E', text: '🟢 Ready to Serve' },
                    'served': { bg: '#ECFDF5', color: '#065F46', border: '#10B981', text: '✅ Delivered / Served' }
                };
                var badge = document.getElementById('ticket-current-badge-' + bookingId);
                var style = badgeStyles[data.overall_status] || badgeStyles['selected'];
                if (badge) {
                    badge.style.background = style.bg;
                    badge.style.color = style.color;
                    badge.style.borderColor = style.border;
                    badge.innerHTML = style.text;
                }
            }
        }
    } catch (e) {
        showKitchenToast('Failed to update dish status', false);
    }
}
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
