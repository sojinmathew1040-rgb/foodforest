<?php
// =========================================================================
// Food Forest Sanctuary — Kitchen & Chef Orders Hub
// =========================================================================
$page_title = 'Kitchen & Chef Orders';
$page_subtitle = 'Live food prep schedules, guest meal selections & kitchen batch management';
require_once __DIR__ . '/includes/header.php';

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
        } elseif ($action === 'quick_add_dish') {
            $booking_id = (int)($_POST['booking_id'] ?? 0);
            $dish_id = (int)($_POST['dish_id'] ?? 0);
            $qty = max(1, (int)($_POST['dish_qty'] ?? 1));
            $chosen_meal = strtolower(trim($_POST['dish_meal_time'] ?? 'lunch'));

            if ($booking_id > 0 && $dish_id > 0) {
                // Fetch dish
                $dstmt = $pdo->prepare("SELECT * FROM food_menu WHERE id = ?");
                $dstmt->execute([$dish_id]);
                $dish = $dstmt->fetch(PDO::FETCH_ASSOC);

                if ($dish) {
                    $bstmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
                    $bstmt->execute([$booking_id]);
                    $b = $bstmt->fetch(PDO::FETCH_ASSOC);

                    if ($b) {
                        $f_items = !empty($b['food_items']) ? json_decode($b['food_items'], true) : [];
                        if (!is_array($f_items)) $f_items = [];

                        $price = (float)$dish['price'];
                        $subtotal = $qty * $price;

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
                            'inclusions' => []
                        ];

                        $new_food_total = 0.00;
                        foreach ($f_items as $fi) {
                            $new_food_total += (float)($fi['subtotal'] ?? (($fi['price'] ?? 0) * ($fi['quantity'] ?? 1)));
                        }

                        // Recalculate billing components with 5% GST
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
                        $gst_pct = (float)($b['gst_percentage'] > 0 ? $b['gst_percentage'] : 5.00);
                        $new_gst = round($taxable * ($gst_pct / 100), 2);
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

                        $alert_message = "Added {$qty}x {$dish['heading']} ({$chosen_meal}) to Reservation #{$booking_id} (#{$b['reference_code']}). Bill updated with ₹" . number_format($subtotal, 2) . ".";
                    }
                }
            }
        }
    }
}

// -------------------------------------------------------------
// 3. Query Bookings with Food for Date Range
// -------------------------------------------------------------
// A booking is active on the given dates if checkin_date <= to_date AND checkout_date >= from_date
$sql = "SELECT id, reference_code, guest_name, guest_phone, guest_email, villa_type, adults_count, kids_count, 
               checkin_date, checkout_date, nights, status, food_status, food_amount, food_items, special_notes 
        FROM bookings 
        WHERE status != 'cancelled' 
          AND checkin_date <= :to_date 
          AND checkout_date >= :from_date 
        ORDER BY checkin_date ASC, id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':from_date' => $from_date,
    ':to_date' => $to_date
]);
$all_matching_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all active non-cancelled reservations for the walk-in modal cottage selector
$inhouse_sql = "SELECT id, reference_code, guest_name, guest_phone, villa_type, checkin_date, checkout_date 
                FROM bookings 
                WHERE status != 'cancelled' 
                  AND checkout_date >= CURDATE() 
                ORDER BY checkin_date ASC, id ASC";
$all_inhouse_bookings = $pdo->query($inhouse_sql)->fetchAll(PDO::FETCH_ASSOC);
if (empty($all_inhouse_bookings)) {
    $all_inhouse_bookings = $all_matching_bookings;
}

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

    <!-- Chef's Kitchen Preparation Batch Summary (Aggregated Prep List) -->
    <div class="adm-card" style="margin-bottom: 28px; background: #FFFFFF; border: 1.5px solid rgba(28, 56, 38, 0.15); border-radius: 12px; padding: 20px; box-shadow: 0 3px 10px rgba(0,0,0,0.03);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1.5px solid #F1F5F9; padding-bottom: 12px; flex-wrap: wrap; gap: 10px;">
            <div>
                <h3 class="font-serif" style="margin: 0; font-size: 1.35rem; color: #1C3826; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-fire-burner" style="color: #D97706;"></i>
                    Chef's Preparation Batch Summary
                </h3>
                <span class="font-sans" style="font-size: 12px; color: #64748B;">Aggregated dish quantities needed by kitchen staff for current shift</span>
            </div>
            <span style="font-size: 12px; font-weight: 700; color: #047857; background: #ECFDF5; border: 1px solid #A7F3D0; padding: 4px 10px; border-radius: 6px;">
                <i class="fa-solid fa-clipboard-check"></i> <?php echo ($meal_filter === 'all') ? 'All Shifts' : (ucfirst($meal_filter) . ' Shift'); ?>
            </span>
        </div>

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

    <!-- Detailed Orders by Guest & Room (Order Cards) -->
    <div style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;">
            <h3 class="font-serif" style="margin: 0; font-size: 1.4rem; color: #1C3826; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-receipt" style="color: var(--accent-gold);"></i>
                Guest &amp; Room Order Tickets (<?php echo count($kitchen_bookings); ?>)
            </h3>
            <span style="font-size: 12px; color: #64748B;">Individual vouchers with dining slots, room numbers &amp; concierge contacts</span>
        </div>

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
                                                    ₹<?php echo number_format($dish_sub, 0); ?>
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

    <!-- Walk-in / Instant Dish Order Creator Box for Kitchen Staff -->
    <div class="adm-card" style="background: #FFFFFF; border: 1.5px solid rgba(14, 116, 144, 0.35); border-radius: 12px; padding: 22px; box-shadow: 0 4px 14px rgba(14, 116, 144, 0.08); margin-top: 25px;">
        
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 14px; border-bottom: 1px solid #E2E8F0; padding-bottom: 12px;">
            <div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-plus-circle" style="color: #0E7490; font-size: 20px;"></i>
                    <h4 class="font-serif" style="margin: 0; font-size: 1.25rem; color: #0E7490;">Add Extra Walk-in Dish to In-House Guest Ticket</h4>
                </div>
                <p class="font-sans" style="font-size: 12.5px; color: #64748B; margin: 4px 0 0 0;">
                    Select the guest room, expand any meal category below using the <strong>[ + ]</strong> button, pick your dish, and record it directly into their kitchen ticket and checkout bill.
                </p>
            </div>
            
            <div style="display: flex; gap: 8px;">
                <button type="button" onclick="expandAllMenuCategories();" class="adm-btn" style="padding: 5px 10px; font-size: 11.5px; background: #E0F2FE; color: #0369A1; border: 1px solid #BAE6FD; border-radius: 6px; cursor: pointer; font-weight: 600;">
                    <i class="fa-solid fa-square-plus"></i> Expand All
                </button>
                <button type="button" onclick="collapseAllMenuCategories();" class="adm-btn" style="padding: 5px 10px; font-size: 11.5px; background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1; border-radius: 6px; cursor: pointer; font-weight: 600;">
                    <i class="fa-solid fa-square-minus"></i> Collapse All
                </button>
            </div>
        </div>

        <form id="walkin_dish_form" method="POST" action="kitchen.php?filter=<?php echo urlencode($filter); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&meal=<?php echo urlencode($meal_filter); ?>" style="margin-bottom: 18px;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="quick_add_dish">
            <input type="hidden" name="dish_id" id="selected_dish_id" value="" required>

            <!-- Top Selection Bar: In-House Guest & Order Confirmation Controls -->
            <div style="background: #F8FAFC; border: 1.5px solid #CBD5E1; border-radius: 10px; padding: 14px 18px; margin-bottom: 16px; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; align-items: flex-end;">
                
                <!-- 1. Select In-House Guest -->
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: #1E293B; display: block; margin-bottom: 4px;">
                        1. Select In-House Room / Guest *
                    </label>
                    <select name="booking_id" id="walkin_booking_select" required class="adm-form-input font-sans" style="width: 100%; padding: 9px 10px; border-radius: 6px; border: 1.5px solid #94A3B8; font-size: 13px; font-weight: 600; color: #0F172A; background: #FFFFFF;">
                        <option value="">-- Choose In-House Room / Cottage --</option>
                        <?php foreach ($all_inhouse_bookings as $mb): ?>
                            <option value="<?php echo $mb['id']; ?>">
                                [#<?php echo htmlspecialchars($mb['reference_code']); ?>] <?php echo htmlspecialchars($mb['guest_name']); ?> (<?php echo format_chalet_label($mb['villa_type']); ?> — <?php echo date('d M', strtotime($mb['checkin_date'])); ?> to <?php echo date('d M', strtotime($mb['checkout_date'])); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 2. Serving Meal Slot -->
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: #1E293B; display: block; margin-bottom: 4px;">
                        2. Serving Meal Time *
                    </label>
                    <select name="dish_meal_time" id="quick_meal_select" required class="adm-form-input font-sans" style="width: 100%; padding: 9px 10px; border-radius: 6px; border: 1.5px solid #94A3B8; font-size: 13px; font-weight: 600; color: #0F172A; background: #FFFFFF;">
                        <option value="breakfast">☀️ Breakfast (09:00 AM – 10:00 AM)</option>
                        <option value="lunch" selected>🍛 Lunch (12:30 PM – 02:30 PM)</option>
                        <option value="snacks">☕ Evening Snacks (04:30 PM – 06:30 PM)</option>
                        <option value="dinner">🌙 Dinner (07:00 PM – 09:00 PM)</option>
                    </select>
                </div>

                <!-- 3. Quantity -->
                <div style="max-width: 140px;">
                    <label style="font-size: 12px; font-weight: 700; color: #1E293B; display: block; margin-bottom: 4px;">
                        3. Quantity *
                    </label>
                    <div style="display: flex; align-items: center;">
                        <button type="button" onclick="decrementDishQty();" style="width: 34px; height: 38px; border: 1px solid #CBD5E1; background: #F1F5F9; border-radius: 6px 0 0 6px; cursor: pointer; font-weight: 700; font-size: 15px;">−</button>
                        <input type="number" name="dish_qty" id="walkin_dish_qty" value="1" min="1" max="50" required class="adm-form-input font-sans" style="width: 60px; height: 38px; padding: 0; text-align: center; border-radius: 0; border-left: none; border-right: none; border-top: 1px solid #CBD5E1; border-bottom: 1px solid #CBD5E1; font-size: 14px; font-weight: 700;">
                        <button type="button" onclick="incrementDishQty();" style="width: 34px; height: 38px; border: 1px solid #CBD5E1; background: #F1F5F9; border-radius: 0 6px 6px 0; cursor: pointer; font-weight: 700; font-size: 15px;">+</button>
                    </div>
                </div>

                <!-- 4. Submit Button -->
                <div>
                    <button type="submit" id="walkin_submit_btn" class="adm-btn-action" style="width: 100%; height: 38px; padding: 0 18px; background: #0E7490; color: #fff; border: none; border-radius: 6px; font-weight: 700; font-size: 13.5px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 7px; box-shadow: 0 2px 6px rgba(14, 116, 144, 0.3);">
                        <i class="fa-solid fa-plus"></i>
                        <span id="walkin_btn_label">Add to Ticket</span>
                    </button>
                </div>
            </div>

            <!-- Active Selected Dish Banner -->
            <div id="selected_dish_banner" style="background: #ECFDF5; border: 1.5px solid #A7F3D0; border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; display: none; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-circle-check" style="color: #059669; font-size: 18px;"></i>
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: #047857; text-transform: uppercase;">Selected Dish:</span>
                        <div id="selected_dish_name" style="font-size: 14px; font-weight: 700; color: #064E3B;"></div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span id="selected_dish_price_badge" style="font-size: 13px; font-weight: 700; color: #065F46; background: #D1FAE5; padding: 3px 10px; border-radius: 20px;"></span>
                    <button type="button" onclick="clearSelectedDish();" style="background: none; border: none; color: #94A3B8; cursor: pointer; font-size: 16px;" title="Clear Selection">&times;</button>
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
            'breakfast' => ['title' => 'Breakfast (പ്രഭാതഭക്ഷണം)', 'icon' => 'fa-mug-saucer', 'color' => '#D97706', 'bg' => '#FEF3C7', 'default_open' => true],
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
                                 onclick="selectDishForWalkin(this);"
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

                                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                                    <span style="font-size: 12.5px; font-weight: 700; color: #0E7490;">
                                        ₹<?php echo number_format($dish['price'], 0); ?>
                                    </span>
                                    <button type="button" class="dish-select-btn" style="padding: 2px 8px; font-size: 11px; font-weight: 700; background: #F1F5F9; color: #0E7490; border: 1px solid #CBD5E1; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; gap: 3px;">
                                        <i class="fa-solid fa-plus"></i> Select
                                    </button>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

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
.dish-item-card.is-selected {
    border-color: #059669 !important;
    background: #ECFDF5 !important;
    box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.25) !important;
}
.dish-item-card.is-selected .dish-select-btn {
    background: #059669 !important;
    color: #FFFFFF !important;
    border-color: #059669 !important;
}
.cat-toggle-btn {
    pointer-events: none;
}
.dish-select-btn {
    pointer-events: none;
}
</style>

<script>
// Quantity Steppers
function incrementDishQty() {
    var q = document.getElementById('walkin_dish_qty');
    if (q) q.value = Math.min(50, (parseInt(q.value, 10) || 1) + 1);
}
function decrementDishQty() {
    var q = document.getElementById('walkin_dish_qty');
    if (q) q.value = Math.max(1, (parseInt(q.value, 10) || 1) - 1);
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

// Select Dish from Accordion
function selectDishForWalkin(cardEl) {
    if (!cardEl) return;
    
    // Clear other selections
    document.querySelectorAll('.dish-item-card').forEach(function(c) {
        c.classList.remove('is-selected');
        var b = c.querySelector('.dish-select-btn');
        if (b) b.innerHTML = '<i class="fa-solid fa-plus"></i> Select';
    });

    // Mark current as selected
    cardEl.classList.add('is-selected');
    var btn = cardEl.querySelector('.dish-select-btn');
    if (btn) btn.innerHTML = '<i class="fa-solid fa-check"></i> Selected';

    var dishId = cardEl.getAttribute('data-dish-id');
    var dishName = cardEl.getAttribute('data-dish-name');
    var dishPrice = cardEl.getAttribute('data-dish-price');
    var defMeal = cardEl.getAttribute('data-dish-meal');

    // Update form hidden dish id
    var hiddenInput = document.getElementById('selected_dish_id');
    if (hiddenInput) hiddenInput.value = dishId;

    // Update meal time select
    var mealSel = document.getElementById('quick_meal_select');
    if (defMeal && mealSel) {
        mealSel.value = defMeal;
    }

    // Show banner
    var banner = document.getElementById('selected_dish_banner');
    var bannerName = document.getElementById('selected_dish_name');
    var bannerPrice = document.getElementById('selected_dish_price_badge');
    var btnLabel = document.getElementById('walkin_btn_label');

    if (banner && bannerName && bannerPrice) {
        banner.style.display = 'flex';
        bannerName.innerText = dishName;
        bannerPrice.innerText = '₹' + parseFloat(dishPrice).toLocaleString('en-IN');
    }

    if (btnLabel) {
        btnLabel.innerText = 'Add "' + dishName + '" to Ticket';
    }

    // Check if guest room is selected, if not highlight it
    var roomSel = document.getElementById('walkin_booking_select');
    if (roomSel && !roomSel.value) {
        roomSel.focus();
    }
}

// Clear selected dish
function clearSelectedDish() {
    var hiddenInput = document.getElementById('selected_dish_id');
    if (hiddenInput) hiddenInput.value = '';

    document.querySelectorAll('.dish-item-card').forEach(function(c) {
        c.classList.remove('is-selected');
        var b = c.querySelector('.dish-select-btn');
        if (b) b.innerHTML = '<i class="fa-solid fa-plus"></i> Select';
    });

    var banner = document.getElementById('selected_dish_banner');
    if (banner) banner.style.display = 'none';

    var btnLabel = document.getElementById('walkin_btn_label');
    if (btnLabel) btnLabel.innerText = 'Add to Ticket';
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
