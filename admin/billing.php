<?php
// =========================================================================
// Food Forest Sanctuary — Concierge Billing & Invoices Management Hub
// =========================================================================

$page_title = 'Billing & Invoices';
$page_subtitle = 'Generate, customize, and print bespoke guest folios & meal settlements';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db();
ensure_billing_columns($pdo);
ensure_food_menu_table_exists($pdo);

$alert_message = '';
$alert_type = 'success';

// Handle Bill Customization & Payment Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $alert_message = 'Security validation failed. Please try again.';
        $alert_type = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_bill') {
            $booking_id = (int)($_POST['booking_id'] ?? 0);
            $booking_ref = strtoupper(trim($_POST['booking_ref'] ?? ''));
            
            if ($booking_id > 0) {
                // Parse existing booking
                $curr = get_booking_billing_details($pdo, $booking_id);
                if ($curr) {
                    $nights = max(1, (int)($curr['nights'] ?? 1));
                    $room_amount = (float)($_POST['room_amount'] ?? $curr['parsed']['room_amount']);
                    $advance_paid = max(0, (float)($_POST['advance_paid'] ?? 0));
                    $discount_amount = max(0, (float)($_POST['discount_amount'] ?? 0));
                    $tax_amount = max(0, (float)($_POST['tax_amount'] ?? 0));
                    $payment_method = trim($_POST['payment_method'] ?? 'unspecified');
                    $billing_notes = trim($_POST['billing_notes'] ?? '');

                    // Process Food Items from Form
                    $food_items = [];
                    $food_total = 0.00;
                    if (!empty($_POST['food_heading']) && is_array($_POST['food_heading'])) {
                        foreach ($_POST['food_heading'] as $idx => $heading) {
                            $heading = trim($heading);
                            if (empty($heading)) continue;
                            $cat = trim($_POST['food_category'][$idx] ?? 'Dining');
                            $qty = max(1, (int)($_POST['food_qty'][$idx] ?? 1));
                            $price = max(0, (float)($_POST['food_price'][$idx] ?? 0));
                            $subtotal = $qty * $price;
                            $food_total += $subtotal;
                            $food_items[] = [
                                'heading' => $heading,
                                'category' => $cat,
                                'quantity' => $qty,
                                'price' => $price,
                                'subtotal' => $subtotal
                            ];
                        }
                    }
                    $food_json = !empty($food_items) ? json_encode($food_items, JSON_UNESCAPED_UNICODE) : null;
                    $food_status = !empty($food_items) ? 'selected' : 'none';

                    // Process Activities from Form
                    $activities = [];
                    $activities_total = 0.00;
                    if (!empty($_POST['act_title']) && is_array($_POST['act_title'])) {
                        foreach ($_POST['act_title'] as $idx => $title) {
                            $title = trim($title);
                            if (empty($title)) continue;
                            $timing = trim($_POST['act_timing'][$idx] ?? 'Curated Schedule');
                            $qty = max(1, (int)($_POST['act_qty'][$idx] ?? 1));
                            $price = max(0, (float)($_POST['act_price'][$idx] ?? 0));
                            $subtotal = $qty * $price;
                            $activities_total += $subtotal;
                            $activities[] = [
                                'title' => $title,
                                'timing' => $timing,
                                'quantity' => $qty,
                                'price' => $price,
                                'subtotal' => $subtotal
                            ];
                        }
                    }
                    $activities_json = !empty($activities) ? json_encode($activities, JSON_UNESCAPED_UNICODE) : null;

                    // Process Custom Services / Incidentals
                    $custom_items = [];
                    $custom_total = 0.00;
                    if (!empty($_POST['cust_title']) && is_array($_POST['cust_title'])) {
                        foreach ($_POST['cust_title'] as $idx => $title) {
                            $title = trim($title);
                            if (empty($title)) continue;
                            $qty = max(1, (int)($_POST['cust_qty'][$idx] ?? 1));
                            $price = max(0, (float)($_POST['cust_price'][$idx] ?? 0));
                            $subtotal = $qty * $price;
                            $custom_total += $subtotal;
                            $custom_items[] = [
                                'title' => $title,
                                'quantity' => $qty,
                                'price' => $price,
                                'subtotal' => $subtotal
                            ];
                        }
                    }
                    $custom_json = !empty($custom_items) ? json_encode($custom_items, JSON_UNESCAPED_UNICODE) : null;

                    // Calculate Grand Total & Status
                    $gross = $room_amount + $food_total + $activities_total + $custom_total;
                    $net_total = max(0, $gross + $tax_amount - $discount_amount);
                    $balance_due = max(0, $net_total - $advance_paid);

                    $payment_status = 'unpaid';
                    if ($balance_due <= 0 && $net_total > 0) {
                        $payment_status = 'paid';
                    } elseif ($advance_paid > 0 && $balance_due > 0) {
                        $payment_status = 'partial';
                    }

                    // Update database record
                    $stmt = $pdo->prepare("UPDATE bookings SET 
                        room_amount = ?, 
                        food_items = ?, 
                        food_amount = ?, 
                        food_status = ?, 
                        activities_json = ?, 
                        billing_items_json = ?, 
                        extra_charges = ?, 
                        discount_amount = ?, 
                        tax_amount = ?, 
                        advance_paid = ?, 
                        total_amount = ?, 
                        payment_method = ?, 
                        payment_status = ?, 
                        billing_notes = ? 
                        WHERE id = ?");
                    $stmt->execute([
                        $room_amount,
                        $food_json,
                        $food_total,
                        $food_status,
                        $activities_json,
                        $custom_json,
                        $custom_total,
                        $discount_amount,
                        $tax_amount,
                        $advance_paid,
                        $net_total,
                        $payment_method,
                        $payment_status,
                        $billing_notes,
                        $booking_id
                    ]);

                    $alert_message = "Billing breakdown for reservation #{$curr['reference_code']} updated successfully.";
                }
            }
        }

        // Quick Settle Balance Action
        if ($action === 'quick_settle') {
            $booking_id = (int)($_POST['booking_id'] ?? 0);
            if ($booking_id > 0) {
                $curr = get_booking_billing_details($pdo, $booking_id);
                if ($curr) {
                    $net = (float)$curr['parsed']['net_total'];
                    $method = trim($_POST['quick_method'] ?? 'cash');
                    $stmt = $pdo->prepare("UPDATE bookings SET advance_paid = ?, payment_method = ?, payment_status = 'paid' WHERE id = ?");
                    $stmt->execute([$net, $method, $booking_id]);
                    $alert_message = "Full balance of ₹" . number_format($net, 2) . " settled for reservation #{$curr['reference_code']}.";
                }
            }
        }
    }
}

// Search & Filter Logic
$search = trim($_GET['search'] ?? '');
$active_tab = trim($_GET['tab'] ?? 'inhouse');
$filter_villa = trim($_GET['villa'] ?? '');
$filter_date_preset = trim($_GET['date_preset'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');
$sort_by = trim($_GET['sort'] ?? 'checkin_desc');

// Resolve date preset shortcuts
if ($filter_date_preset === 'today') {
    $date_from = date('Y-m-d');
    $date_to = date('Y-m-d');
} elseif ($filter_date_preset === 'yesterday') {
    $date_from = date('Y-m-d', strtotime('-1 day'));
    $date_to = date('Y-m-d', strtotime('-1 day'));
} elseif ($filter_date_preset === 'this_week') {
    $date_from = date('Y-m-d', strtotime('monday this week'));
    $date_to = date('Y-m-d', strtotime('sunday this week'));
} elseif ($filter_date_preset === 'this_month') {
    $date_from = date('Y-m-01');
    $date_to = date('Y-m-t');
}

// Live KPI Stats
$kpi_inhouse_count = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'inhouse'")->fetchColumn();
$kpi_today_cout_count = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE (status = 'inhouse' OR status = 'completed') AND (checkout_date = CURDATE() OR (checked_out_at >= CURDATE() AND checked_out_at < CURDATE() + INTERVAL 1 DAY))")->fetchColumn();
$kpi_pending_dues = (float)$pdo->query("SELECT SUM(GREATEST(0, total_amount - COALESCE(advance_paid, 0))) FROM bookings WHERE status IN ('inhouse', 'confirmed')")->fetchColumn();
$kpi_month_revenue = (float)$pdo->query("SELECT SUM(total_amount) FROM bookings WHERE status NOT IN ('cancelled', 'rejected') AND MONTH(checkin_date) = MONTH(CURDATE()) AND YEAR(checkin_date) = YEAR(CURDATE())")->fetchColumn();

// Tab badge counts
$tab_count_inhouse = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'inhouse'")->fetchColumn();
$tab_count_recent = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'completed' AND (checked_out_at >= NOW() - INTERVAL 48 HOUR OR checkout_date >= CURDATE() - INTERVAL 2 DAY)")->fetchColumn();
$tab_count_upcoming = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed' AND checkin_date >= CURDATE()")->fetchColumn();
$tab_count_all = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();

// Build Base Query
$query = "SELECT b.*, r.title AS room_title, r.image_url AS room_image, r.elevation AS room_elevation, r.stay_type AS room_stay_type, r.rate_per_night AS room_rate, r.base_guests, r.extra_guest_rate, r.extra_child_rate 
          FROM bookings b 
          LEFT JOIN rooms r ON b.villa_type = r.slug 
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (b.guest_name LIKE ? OR b.guest_phone LIKE ? OR b.reference_code LIKE ? OR b.guest_email LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($active_tab === 'inhouse') {
    $query .= " AND b.status = 'inhouse'";
} elseif ($active_tab === 'recent_checkouts') {
    $query .= " AND b.status = 'completed' AND (b.checked_out_at >= NOW() - INTERVAL 48 HOUR OR b.checkout_date >= CURDATE() - INTERVAL 2 DAY)";
} elseif ($active_tab === 'upcoming') {
    $query .= " AND b.status = 'confirmed' AND b.checkin_date >= CURDATE()";
} elseif ($active_tab === 'all') {
    // No specific status filter
}

if (!empty($filter_villa)) {
    $query .= " AND b.villa_type = ?";
    $params[] = $filter_villa;
}

if (!empty($date_from)) {
    $query .= " AND b.checkin_date >= ?";
    $params[] = $date_from;
}
if (!empty($date_to)) {
    $query .= " AND b.checkin_date <= ?";
    $params[] = $date_to;
}

// Sorting
if ($sort_by === 'checkout_desc') {
    $query .= " ORDER BY b.checkout_date DESC";
} elseif ($sort_by === 'checkout_asc') {
    $query .= " ORDER BY b.checkout_date ASC";
} elseif ($sort_by === 'checkin_asc') {
    $query .= " ORDER BY b.checkin_date ASC";
} elseif ($sort_by === 'amount_desc') {
    $query .= " ORDER BY b.total_amount DESC";
} elseif ($sort_by === 'amount_asc') {
    $query .= " ORDER BY b.total_amount ASC";
} elseif ($sort_by === 'guest_asc') {
    $query .= " ORDER BY b.guest_name ASC";
} else {
    $query .= " ORDER BY b.checkin_date DESC, b.id DESC";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bookings_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Parse all bookings through the comprehensive billing helper
$billing_records = [];
foreach ($bookings_raw as $raw) {
    $parsed_record = get_booking_billing_details($pdo, $raw['id']);
    if ($parsed_record) {
        $billing_records[] = $parsed_record;
    }
}

// Fetch Master Menus and Experiences for the Bill Customizer Modal
$all_rooms = $pdo->query("SELECT slug, title FROM rooms ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$menu_items = $pdo->query("SELECT heading, category, price FROM food_menu WHERE is_active = 1 ORDER BY category, heading")->fetchAll(PDO::FETCH_ASSOC);
$experience_items = $pdo->query("SELECT title, timing, badge FROM experiences WHERE is_active = 1 ORDER BY display_order")->fetchAll(PDO::FETCH_ASSOC);
$currency = get_setting('currency_symbol', '₹');
?>

<div class="adm-content-wrapper">

    <!-- Header Alert Notification -->
    <?php if (!empty($alert_message)): ?>
        <div class="adm-alert adm-alert-<?php echo $alert_type; ?>" style="margin-bottom: 24px;">
            <i class="fa-solid <?php echo ($alert_type === 'success') ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i>
            <span><?php echo htmlspecialchars($alert_message); ?></span>
        </div>
    <?php endif; ?>

    <!-- Top KPI Stats Bar -->
    <div class="adm-grid adm-grid-4" style="margin-bottom: 28px;">
        
        <div class="adm-stat-card" style="border-left: 3px solid var(--adm-emerald);">
            <div class="adm-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #34D399;">
                <i class="fa-solid fa-hotel"></i>
            </div>
            <div class="adm-stat-meta">
                <span class="adm-stat-label">Currently In-House</span>
                <div class="adm-stat-value"><?php echo $kpi_inhouse_count; ?> <span style="font-size: 13px; font-weight: 500; color: var(--adm-text-muted);">Stays Active</span></div>
            </div>
        </div>

        <div class="adm-stat-card" style="border-left: 3px solid var(--adm-gold);">
            <div class="adm-stat-icon" style="background: rgba(197, 160, 89, 0.15); color: var(--adm-gold);">
                <i class="fa-solid fa-door-open"></i>
            </div>
            <div class="adm-stat-meta">
                <span class="adm-stat-label">Today's Check-Outs</span>
                <div class="adm-stat-value"><?php echo $kpi_today_cout_count; ?> <span style="font-size: 13px; font-weight: 500; color: var(--adm-text-muted);">Departures</span></div>
            </div>
        </div>

        <div class="adm-stat-card" style="border-left: 3px solid #EF4444;">
            <div class="adm-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: #F87171;">
                <i class="fa-solid fa-hand-holding-dollar"></i>
            </div>
            <div class="adm-stat-meta">
                <span class="adm-stat-label">Pending Balance Dues</span>
                <div class="adm-stat-value"><?php echo $currency . number_format($kpi_pending_dues, 0); ?></div>
            </div>
        </div>

        <div class="adm-stat-card" style="border-left: 3px solid #6366F1;">
            <div class="adm-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: #818CF8;">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <div class="adm-stat-meta">
                <span class="adm-stat-label">This Month's Bookings</span>
                <div class="adm-stat-value"><?php echo $currency . number_format($kpi_month_revenue, 0); ?></div>
            </div>
        </div>

    </div>

    <!-- Main Navigation Filter Tabs -->
    <div class="adm-tab-bar" style="margin-bottom: 20px; display: flex; gap: 8px; flex-wrap: wrap; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 12px;">
        
        <a href="?tab=inhouse<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" 
           class="adm-tab-btn <?php echo ($active_tab === 'inhouse') ? 'active' : ''; ?>"
           style="padding: 9px 18px; border-radius: 8px; font-size: 13.5px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; <?php echo ($active_tab === 'inhouse') ? 'background: var(--adm-gold); color: #101F15;' : 'background: rgba(255,255,255,0.05); color: var(--adm-text-secondary);'; ?>">
            <i class="fa-solid fa-bell-concierge"></i>
            <span>Currently In-House</span>
            <span class="adm-badge-count" style="padding: 2px 7px; border-radius: 12px; font-size: 11px; <?php echo ($active_tab === 'inhouse') ? 'background: #101F15; color: #FFF;' : 'background: rgba(255,255,255,0.1); color: #FFF;'; ?>"><?php echo $tab_count_inhouse; ?></span>
        </a>

        <a href="?tab=recent_checkouts<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" 
           class="adm-tab-btn <?php echo ($active_tab === 'recent_checkouts') ? 'active' : ''; ?>"
           style="padding: 9px 18px; border-radius: 8px; font-size: 13.5px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; <?php echo ($active_tab === 'recent_checkouts') ? 'background: var(--adm-gold); color: #101F15;' : 'background: rgba(255,255,255,0.05); color: var(--adm-text-secondary);'; ?>">
            <i class="fa-solid fa-person-walking-luggage"></i>
            <span>Recent Check-Outs (48h)</span>
            <span class="adm-badge-count" style="padding: 2px 7px; border-radius: 12px; font-size: 11px; <?php echo ($active_tab === 'recent_checkouts') ? 'background: #101F15; color: #FFF;' : 'background: rgba(255,255,255,0.1); color: #FFF;'; ?>"><?php echo $tab_count_recent; ?></span>
        </a>

        <a href="?tab=upcoming<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" 
           class="adm-tab-btn <?php echo ($active_tab === 'upcoming') ? 'active' : ''; ?>"
           style="padding: 9px 18px; border-radius: 8px; font-size: 13.5px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; <?php echo ($active_tab === 'upcoming') ? 'background: var(--adm-gold); color: #101F15;' : 'background: rgba(255,255,255,0.05); color: var(--adm-text-secondary);'; ?>">
            <i class="fa-solid fa-calendar-check"></i>
            <span>Upcoming Confirmed</span>
            <span class="adm-badge-count" style="padding: 2px 7px; border-radius: 12px; font-size: 11px; <?php echo ($active_tab === 'upcoming') ? 'background: #101F15; color: #FFF;' : 'background: rgba(255,255,255,0.1); color: #FFF;'; ?>"><?php echo $tab_count_upcoming; ?></span>
        </a>

        <a href="?tab=all<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" 
           class="adm-tab-btn <?php echo ($active_tab === 'all') ? 'active' : ''; ?>"
           style="padding: 9px 18px; border-radius: 8px; font-size: 13.5px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; <?php echo ($active_tab === 'all') ? 'background: var(--adm-gold); color: #101F15;' : 'background: rgba(255,255,255,0.05); color: var(--adm-text-secondary);'; ?>">
            <i class="fa-solid fa-box-archive"></i>
            <span>All Bookings Archive</span>
            <span class="adm-badge-count" style="padding: 2px 7px; border-radius: 12px; font-size: 11px; <?php echo ($active_tab === 'all') ? 'background: #101F15; color: #FFF;' : 'background: rgba(255,255,255,0.1); color: #FFF;'; ?>"><?php echo $tab_count_all; ?></span>
        </a>

        <a href="edit_section.php?section=bank" 
           class="adm-tab-btn" 
           style="padding: 9px 18px; border-radius: 8px; font-size: 13.5px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; margin-left: auto; background: rgba(16, 185, 129, 0.15); color: #34D399; border: 1px solid rgba(16, 185, 129, 0.35);" 
           title="Configure Bank Account Number, IFSC, UPI ID & Payment QR">
            <i class="fa-solid fa-building-columns"></i>
            <span>Bank & UPI QR Settings</span>
        </a>

    </div>

    <!-- Multi-Filter & Search Bar -->
    <div class="adm-card" style="margin-bottom: 24px; padding: 18px 24px;">
        <form method="GET" action="billing.php" class="adm-form-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) 120px 80px; gap: 14px; align-items: end;">
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($active_tab); ?>">

            <!-- Search Guest / Ref -->
            <div>
                <label class="adm-form-label" style="font-size: 12px;"><i class="fa-solid fa-magnifying-glass"></i> Search Reservation</label>
                <input type="text" name="search" class="adm-input" placeholder="Guest Name, Phone, #FF-xxxx..." value="<?php echo htmlspecialchars($search); ?>">
            </div>

            <!-- Property Filter -->
            <div>
                <label class="adm-form-label" style="font-size: 12px;"><i class="fa-solid fa-tree"></i> Sanctuary Villa</label>
                <select name="villa" class="adm-input">
                    <option value="">All Properties / Villas</option>
                    <?php foreach ($all_rooms as $r): ?>
                        <option value="<?php echo $r['slug']; ?>" <?php echo ($filter_villa === $r['slug']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($r['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Date Preset Shortcut -->
            <div>
                <label class="adm-form-label" style="font-size: 12px;"><i class="fa-regular fa-clock"></i> Quick Date Filter</label>
                <select name="date_preset" class="adm-input" onchange="this.form.submit()">
                    <option value="">Custom / All Dates</option>
                    <option value="today" <?php echo ($filter_date_preset === 'today') ? 'selected' : ''; ?>>Today</option>
                    <option value="yesterday" <?php echo ($filter_date_preset === 'yesterday') ? 'selected' : ''; ?>>Yesterday</option>
                    <option value="this_week" <?php echo ($filter_date_preset === 'this_week') ? 'selected' : ''; ?>>This Week</option>
                    <option value="this_month" <?php echo ($filter_date_preset === 'this_month') ? 'selected' : ''; ?>>This Month</option>
                </select>
            </div>

            <!-- Date From / To -->
            <div>
                <label class="adm-form-label" style="font-size: 12px;"><i class="fa-regular fa-calendar"></i> Check-In From</label>
                <input type="date" name="date_from" class="adm-input" value="<?php echo htmlspecialchars($date_from); ?>">
            </div>

            <div>
                <label class="adm-form-label" style="font-size: 12px;"><i class="fa-regular fa-calendar"></i> Check-In To</label>
                <input type="date" name="date_to" class="adm-input" value="<?php echo htmlspecialchars($date_to); ?>">
            </div>

            <!-- Sort Option -->
            <div>
                <label class="adm-form-label" style="font-size: 12px;"><i class="fa-solid fa-arrow-down-short-wide"></i> Sort By</label>
                <select name="sort" class="adm-input">
                    <option value="checkin_desc" <?php echo ($sort_by === 'checkin_desc') ? 'selected' : ''; ?>>Check-In (Newest)</option>
                    <option value="checkin_asc" <?php echo ($sort_by === 'checkin_asc') ? 'selected' : ''; ?>>Check-In (Oldest)</option>
                    <option value="checkout_desc" <?php echo ($sort_by === 'checkout_desc') ? 'selected' : ''; ?>>Check-Out (Newest)</option>
                    <option value="amount_desc" <?php echo ($sort_by === 'amount_desc') ? 'selected' : ''; ?>>Amount (Highest)</option>
                    <option value="guest_asc" <?php echo ($sort_by === 'guest_asc') ? 'selected' : ''; ?>>Guest Name (A-Z)</option>
                </select>
            </div>

            <!-- Submit Button -->
            <div>
                <button type="submit" class="adm-btn adm-btn-primary" style="width: 100%; height: 42px; display: flex; align-items: center; justify-content: center; gap: 6px;">
                    <i class="fa-solid fa-filter"></i>
                    <span>Filter</span>
                </button>
            </div>

            <!-- Reset Button -->
            <div>
                <a href="billing.php?tab=<?php echo urlencode($active_tab); ?>" class="adm-btn adm-btn-secondary" style="width: 100%; height: 42px; display: flex; align-items: center; justify-content: center; text-decoration: none;" title="Clear Filters">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>

        </form>
    </div>

    <!-- Invoices & Billing Table -->
    <div class="adm-card" style="padding: 0; overflow: hidden;">
        
        <div style="padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.08); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3 style="font-family: var(--adm-font-title); font-size: 18px; color: #FFFFFF; margin: 0;">
                    <?php 
                    if ($active_tab === 'inhouse') echo 'Currently Residing (In-House) Folios';
                    elseif ($active_tab === 'recent_checkouts') echo 'Recent Checked-Out Stays (Past 48 Hours)';
                    elseif ($active_tab === 'upcoming') echo 'Upcoming Confirmed Guest Folios';
                    else echo 'Comprehensive Sanctuary Billing Archive';
                    ?>
                </h3>
                <p style="font-size: 12.5px; color: var(--adm-text-muted); margin-top: 4px;">
                    Showing <?php echo count($billing_records); ?> reservation folio(s) ready for review, edit, or printing.
                </p>
            </div>

            <div style="display: flex; gap: 8px;">
                <span class="adm-badge" style="background: rgba(197, 160, 89, 0.15); color: var(--adm-gold); border: 1px solid rgba(197,160,89,0.3); padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                    <i class="fa-solid fa-print"></i> Ready for A4 Print
                </span>
            </div>
        </div>

        <?php if (!empty($billing_records)): ?>
            <div class="adm-table-responsive">
                <table class="adm-table" style="width: 100%; text-align: left;">
                    <thead>
                        <tr>
                            <th style="width: 100px;">Ref ID</th>
                            <th>Guest Information</th>
                            <th>Property & Occupancy</th>
                            <th>Stay Dates & Status</th>
                            <th>What They Ate & Did</th>
                            <th style="text-align: right;">Bill & Dues</th>
                            <th style="text-align: center; width: 170px;">Print & Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($billing_records as $b): 
                            $bp = $b['parsed'];
                            $guest_phone_clean = preg_replace('/[^0-9]/', '', $b['guest_phone']);
                        ?>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.06); transition: background 0.15s ease;">
                                
                                <!-- Ref ID -->
                                <td>
                                    <div style="font-family: monospace; font-size: 13.5px; font-weight: 700; color: var(--adm-gold);">
                                        #<?php echo htmlspecialchars($b['reference_code']); ?>
                                    </div>
                                    <div style="font-size: 11px; color: var(--adm-text-muted); margin-top: 4px;">
                                        ID: #<?php echo $b['id']; ?>
                                    </div>
                                </td>

                                <!-- Guest Info -->
                                <td>
                                    <div style="font-weight: 600; color: #FFFFFF; font-size: 14.5px;">
                                        <?php echo htmlspecialchars($b['guest_name']); ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--adm-text-secondary); margin-top: 2px;">
                                        <i class="fa-solid fa-phone" style="font-size: 11px; color: var(--adm-gold); margin-right: 4px;"></i>
                                        <?php echo htmlspecialchars($b['guest_phone']); ?>
                                    </div>
                                    <?php if (!empty($b['guest_email'])): ?>
                                        <div style="font-size: 11.5px; color: var(--adm-text-muted);">
                                            <i class="fa-solid fa-envelope" style="font-size: 10px; margin-right: 4px;"></i>
                                            <?php echo htmlspecialchars($b['guest_email']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Property & Occupancy -->
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <img src="<?php echo htmlspecialchars($b['room_image']); ?>" 
                                             alt="Villa" 
                                             style="width: 44px; height: 44px; border-radius: 6px; object-fit: cover; border: 1px solid rgba(255,255,255,0.1);"
                                             onerror="this.src='../assets/images/treehouse_exterior.png'">
                                        <div>
                                            <div style="font-weight: 600; color: #FFFFFF; font-size: 13.5px;">
                                                <?php echo htmlspecialchars($b['room_title']); ?>
                                            </div>
                                            <div style="font-size: 12px; color: var(--adm-text-muted);">
                                                <i class="fa-solid fa-users" style="color: var(--adm-gold); font-size: 11px; margin-right: 3px;"></i>
                                                <?php echo $bp['adults_count']; ?> Adults<?php echo $bp['kids_count'] > 0 ? ', ' . $bp['kids_count'] . ' Kids' : ''; ?> (<?php echo (int)$b['guests_count']; ?> Pax)
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Stay Dates & Status -->
                                <td>
                                    <div style="font-size: 13px; color: #FFFFFF; font-weight: 500;">
                                        <?php echo date('d M', strtotime($b['checkin_date'])); ?> → <?php echo date('d M Y', strtotime($b['checkout_date'])); ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--adm-text-muted); margin-top: 2px;">
                                        <i class="fa-regular fa-moon"></i> <?php echo $bp['nights']; ?> Night<?php echo $bp['nights'] > 1 ? 's' : ''; ?>
                                    </div>
                                    <div style="margin-top: 5px;">
                                        <?php if ($b['status'] === 'inhouse'): ?>
                                            <span class="adm-badge" style="background: rgba(16, 185, 129, 0.2); color: #34D399; border: 1px solid rgba(16,185,129,0.3); font-size: 11px; font-weight: 700;">
                                                <i class="fa-solid fa-circle" style="font-size: 8px; vertical-align: middle; margin-right: 4px;"></i> IN-HOUSE
                                            </span>
                                        <?php elseif ($b['status'] === 'completed'): ?>
                                            <span class="adm-badge" style="background: rgba(148, 163, 184, 0.18); color: #CBD5E1; font-size: 11px;">
                                                CHECKED OUT
                                            </span>
                                        <?php elseif ($b['status'] === 'confirmed'): ?>
                                            <span class="adm-badge" style="background: rgba(245, 158, 11, 0.18); color: #FBBF24; font-size: 11px;">
                                                CONFIRMED
                                            </span>
                                        <?php else: ?>
                                            <span class="adm-badge" style="background: rgba(255, 255, 255, 0.1); color: var(--adm-text-muted); font-size: 11px;">
                                                <?php echo strtoupper(htmlspecialchars($b['status'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- What They Ate & Did -->
                                <td>
                                    <!-- Food items snippet -->
                                    <div style="margin-bottom: 6px;">
                                        <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--adm-gold); letter-spacing: 0.5px;">
                                            <i class="fa-solid fa-utensils"></i> Gastronomy (<?php echo count($bp['food_items']); ?>):
                                        </span>
                                        <?php if (!empty($bp['food_items'])): ?>
                                            <div style="font-size: 12px; color: var(--adm-text-secondary); line-height: 1.4; margin-top: 2px;">
                                                <?php 
                                                $food_names = array_map(function($fi) { 
                                                    return htmlspecialchars($fi['heading']) . ' (x' . ($fi['quantity'] ?? 1) . ')'; 
                                                }, array_slice($bp['food_items'], 0, 2));
                                                echo implode(', ', $food_names);
                                                if (count($bp['food_items']) > 2) echo ' <span style="color:var(--adm-gold);">+' . (count($bp['food_items']) - 2) . ' more</span>';
                                                ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="font-size: 12px; color: var(--adm-text-muted);">None pre-ordered</span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Activities snippet -->
                                    <div>
                                        <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #34D399; letter-spacing: 0.5px;">
                                            <i class="fa-solid fa-compass"></i> Experiences (<?php echo count($bp['activities']); ?>):
                                        </span>
                                        <?php if (!empty($bp['activities'])): ?>
                                            <div style="font-size: 12px; color: var(--adm-text-secondary); line-height: 1.4; margin-top: 2px;">
                                                <?php 
                                                $act_names = array_map(function($ac) { 
                                                    return htmlspecialchars($ac['title']); 
                                                }, array_slice($bp['activities'], 0, 2));
                                                echo implode(', ', $act_names);
                                                if (count($bp['activities']) > 2) echo ' <span style="color:#34D399;">+' . (count($bp['activities']) - 2) . ' more</span>';
                                                ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="font-size: 12px; color: var(--adm-text-muted);">Standard Complimentary</span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Bill & Dues -->
                                <td style="text-align: right;">
                                    <div style="font-size: 15px; font-weight: 700; color: #FFFFFF; font-family: monospace;">
                                        <?php echo $currency . number_format($bp['net_total'], 2); ?>
                                    </div>
                                    <div style="font-size: 11.5px; color: var(--adm-text-muted); margin-top: 2px;">
                                        Adv: <?php echo $currency . number_format($bp['advance_paid'], 2); ?>
                                    </div>
                                    <div style="margin-top: 4px;">
                                        <?php if ($bp['balance_due'] <= 0): ?>
                                            <span style="font-size: 11.5px; font-weight: 700; color: #34D399; background: rgba(16, 185, 129, 0.15); padding: 2px 8px; border-radius: 4px; display: inline-block;">
                                                <i class="fa-solid fa-check"></i> SETTLED
                                            </span>
                                        <?php else: ?>
                                            <span style="font-size: 11.5px; font-weight: 700; color: #F87171; background: rgba(239, 68, 68, 0.15); padding: 2px 8px; border-radius: 4px; display: inline-block;" title="Balance Due">
                                                Due: <?php echo $currency . number_format($bp['balance_due'], 2); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Print & Action Buttons -->
                                <td style="text-align: center;">
                                    <div style="display: flex; flex-direction: column; gap: 6px; align-items: stretch;">
                                        
                                        <!-- Primary Print Button -->
                                        <a href="print_bill.php?ref=<?php echo urlencode($b['reference_code']); ?>" 
                                           target="_blank" 
                                           class="adm-btn adm-btn-primary" 
                                           style="padding: 6px 12px; font-size: 12.5px; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none;"
                                           title="Open printable luxury guest folio & invoice">
                                            <i class="fa-solid fa-print"></i>
                                            <span>Print Bill</span>
                                        </a>

                                        <!-- Edit / Customize Modal Trigger -->
                                        <button type="button" 
                                                onclick="openBillEditModal(<?php echo htmlspecialchars(json_encode($b), ENT_QUOTES, 'UTF-8'); ?>)"
                                                class="adm-btn adm-btn-secondary" 
                                                style="padding: 5px 10px; font-size: 12px; display: flex; align-items: center; justify-content: center; gap: 5px;"
                                                title="Add food items, extra activities, discounts, or record payment">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                            <span>Customize / Edit</span>
                                        </button>

                                        <!-- Quick WhatsApp Share & Digital Receipt -->
                                        <div style="display: flex; gap: 4px;">
                                            <?php if (!empty($b['guest_phone'])): ?>
                                                <a href="print_bill.php?ref=<?php echo urlencode($b['reference_code']); ?>" 
                                                   target="_blank" 
                                                   class="adm-btn" 
                                                   style="flex: 1; padding: 4px 6px; font-size: 11px; background: rgba(37, 211, 102, 0.15); color: #25D366; border: 1px solid rgba(37, 211, 102, 0.3); text-decoration: none; text-align: center;"
                                                   title="View & Share via WhatsApp">
                                                    <i class="fa-brands fa-whatsapp"></i> WA
                                                </a>
                                            <?php endif; ?>
                                            <a href="../receipt.php?ref=<?php echo urlencode($b['reference_code']); ?>" 
                                               target="_blank" 
                                               class="adm-btn" 
                                               style="flex: 1; padding: 4px 6px; font-size: 11px; background: rgba(255,255,255,0.08); color: var(--adm-text-secondary); text-decoration: none; text-align: center;"
                                               title="View guest web receipt">
                                                <i class="fa-solid fa-eye"></i> View
                                            </a>
                                        </div>

                                    </div>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="padding: 60px 30px; text-align: center; color: var(--adm-text-muted);">
                <i class="fa-solid fa-receipt" style="font-size: 38px; color: var(--adm-gold); margin-bottom: 12px; opacity: 0.6;"></i>
                <h4 style="color: #FFFFFF; font-size: 17px; margin-bottom: 6px;">No Reservation Folios Found</h4>
                <p style="font-size: 13.5px; max-width: 480px; margin: 0 auto 16px;">
                    No bookings matched your active filter or search criteria. Try selecting another tab or clearing filter parameters.
                </p>
                <a href="billing.php?tab=all" class="adm-btn adm-btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>Show All Reservations</span>
                </a>
            </div>
        <?php endif; ?>

    </div>

</div>

<!-- ========================================================================= -->
<!-- MODAL: LIVE BILL CUSTOMIZER & EXTRA ITEM GENERATOR -->
<!-- ========================================================================= -->
<div id="billEditModal" class="adm-modal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); overflow-y: auto; padding: 30px 15px;">
    <div class="adm-modal-dialog" style="max-width: 850px; margin: 30px auto; background: var(--adm-bg-surface); border: 1px solid var(--adm-gold-border); border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.7); overflow: hidden; position: relative;">
        
        <!-- Modal Header -->
        <div style="padding: 20px 28px; background: #0A160F; border-bottom: 1px solid rgba(255,255,255,0.08); display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 34px; height: 34px; border-radius: 50%; background: var(--adm-gold); color: #101F15; display: flex; align-items: center; justify-content: center; font-size: 15px;">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 17px; color: #FFFFFF; font-family: var(--adm-font-title);">Customize & Settle Folio — #<span id="modalRefCode"></span></h3>
                    <div style="font-size: 12px; color: var(--adm-text-muted);" id="modalGuestSubtitle"></div>
                </div>
            </div>
            <button type="button" onclick="closeBillEditModal()" style="background: transparent; border: none; color: var(--adm-text-muted); font-size: 20px; cursor: pointer; padding: 4px;" title="Close Modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Modal Form -->
        <form method="POST" action="billing.php" id="billEditForm" style="padding: 28px;">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="update_bill">
            <input type="hidden" name="booking_id" id="modalBookingId">
            <input type="hidden" name="booking_ref" id="modalBookingRef">

            <!-- 1. Stay & Room Tariff Setting -->
            <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 16px 20px; margin-bottom: 20px;">
                <div style="font-weight: 700; color: var(--adm-gold); font-size: 13.5px; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-tree"></i> 1. Accommodation & Stay Breakdown
                </div>
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 14px; align-items: end;">
                    <div>
                        <label class="adm-form-label" style="font-size: 12px;">Villa / Property</label>
                        <input type="text" id="modalVillaTitle" class="adm-input" readonly style="opacity: 0.8; background: #0A160F;">
                    </div>
                    <div>
                        <label class="adm-form-label" style="font-size: 12px;">Total Nights</label>
                        <input type="text" id="modalNights" class="adm-input" readonly style="opacity: 0.8; background: #0A160F;">
                    </div>
                    <div>
                        <label class="adm-form-label" style="font-size: 12px;">Room Total (<?php echo $currency; ?>)</label>
                        <input type="number" step="0.01" name="room_amount" id="modalRoomAmount" class="adm-input" style="font-weight: 700; color: var(--adm-gold);" oninput="recalculateModalTotals()">
                    </div>
                </div>
            </div>

            <!-- 2. What They Ate (Gastronomy / Food Menu List) -->
            <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 16px 20px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <div style="font-weight: 700; color: var(--adm-gold); font-size: 13.5px; text-transform: uppercase; letter-spacing: 0.8px; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-utensils"></i> 2. What They Ate (Gastronomy & Dining Orders)
                    </div>
                    <button type="button" onclick="addFoodItemRow()" class="adm-btn adm-btn-secondary" style="padding: 4px 10px; font-size: 11.5px; display: inline-flex; align-items: center; gap: 5px;">
                        <i class="fa-solid fa-plus"></i> Add Meal / Dish
                    </button>
                </div>

                <!-- Quick Food Preset Select -->
                <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 12px; background: #0A160F; padding: 8px 12px; border-radius: 6px;">
                    <span style="font-size: 12px; color: var(--adm-text-muted); white-space: nowrap;">Quick Add from Menu:</span>
                    <select id="foodMenuPresetSelect" class="adm-input" style="font-size: 12px; height: 34px;">
                        <option value="">-- Choose Item from Food Menu --</option>
                        <?php foreach ($menu_items as $mi): ?>
                            <option value="<?php echo htmlspecialchars(json_encode($mi), ENT_QUOTES, 'UTF-8'); ?>">
                                [<?php echo strtoupper($mi['category']); ?>] <?php echo htmlspecialchars($mi['heading']); ?> — <?php echo $currency . number_format($mi['price'], 0); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" onclick="addSelectedMenuPreset()" class="adm-btn adm-btn-primary" style="padding: 4px 12px; font-size: 12px; white-space: nowrap;">
                        <i class="fa-solid fa-cart-plus"></i> Add
                    </button>
                </div>

                <div id="foodItemsContainer">
                    <!-- Dynamic Rows Injected Here -->
                </div>
                <div style="text-align: right; margin-top: 8px; font-size: 12.5px; color: var(--adm-text-muted);">
                    Gastronomy Subtotal: <strong id="foodSubtotalLabel" style="color: #FFFFFF; font-family: monospace;">₹0.00</strong>
                </div>
            </div>

            <!-- 3. What They Did (Sanctuary Activities & Experiences) -->
            <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 16px 20px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <div style="font-weight: 700; color: #34D399; font-size: 13.5px; text-transform: uppercase; letter-spacing: 0.8px; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-compass"></i> 3. What They Did (Experiences & Activities)
                    </div>
                    <button type="button" onclick="addActivityItemRow()" class="adm-btn adm-btn-secondary" style="padding: 4px 10px; font-size: 11.5px; display: inline-flex; align-items: center; gap: 5px;">
                        <i class="fa-solid fa-plus"></i> Add Activity
                    </button>
                </div>

                <!-- Quick Activity Preset Select -->
                <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 12px; background: #0A160F; padding: 8px 12px; border-radius: 6px;">
                    <span style="font-size: 12px; color: var(--adm-text-muted); white-space: nowrap;">Quick Add from Experiences:</span>
                    <select id="experiencePresetSelect" class="adm-input" style="font-size: 12px; height: 34px;">
                        <option value="">-- Choose Experience / Ritual --</option>
                        <?php foreach ($experience_items as $ei): ?>
                            <option value="<?php echo htmlspecialchars(json_encode($ei), ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($ei['title']); ?> (<?php echo htmlspecialchars($ei['timing']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" onclick="addSelectedExperiencePreset()" class="adm-btn adm-btn-primary" style="padding: 4px 12px; font-size: 12px; white-space: nowrap;">
                        <i class="fa-solid fa-plus"></i> Add
                    </button>
                </div>

                <div id="activityItemsContainer">
                    <!-- Dynamic Rows Injected Here -->
                </div>
                <div style="text-align: right; margin-top: 8px; font-size: 12.5px; color: var(--adm-text-muted);">
                    Experiences Subtotal: <strong id="activitiesSubtotalLabel" style="color: #FFFFFF; font-family: monospace;">₹0.00</strong>
                </div>
            </div>

            <!-- 4. Extra Services / Custom Incidentals -->
            <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 16px 20px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <div style="font-weight: 700; color: #818CF8; font-size: 13.5px; text-transform: uppercase; letter-spacing: 0.8px; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-bell-concierge"></i> 4. Extra Services / Bespoke Incidentals
                    </div>
                    <button type="button" onclick="addCustomItemRow()" class="adm-btn adm-btn-secondary" style="padding: 4px 10px; font-size: 11.5px; display: inline-flex; align-items: center; gap: 5px;">
                        <i class="fa-solid fa-plus"></i> Add Custom Charge
                    </button>
                </div>
                <div id="customItemsContainer">
                    <!-- Dynamic Rows Injected Here -->
                </div>
            </div>

            <!-- 5. Discounts, Taxes, Advance & Payment Settlement -->
            <div style="background: #0A160F; border: 1px solid rgba(197, 160, 89, 0.3); border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                <div style="font-weight: 700; color: #FFFFFF; font-size: 14px; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-calculator" style="color: var(--adm-gold);"></i> 5. Adjustments & Payment Settlement
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 14px;">
                    <div>
                        <label class="adm-form-label" style="font-size: 12px;">Discount / Concession (<?php echo $currency; ?>)</label>
                        <input type="number" step="0.01" name="discount_amount" id="modalDiscount" class="adm-input" value="0" oninput="recalculateModalTotals()">
                    </div>
                    <div>
                        <label class="adm-form-label" style="font-size: 12px;">Taxes / GST (<?php echo $currency; ?>)</label>
                        <input type="number" step="0.01" name="tax_amount" id="modalTax" class="adm-input" value="0" oninput="recalculateModalTotals()">
                    </div>
                    <div>
                        <label class="adm-form-label" style="font-size: 12px;">Advance Paid (<?php echo $currency; ?>)</label>
                        <input type="number" step="0.01" name="advance_paid" id="modalAdvance" class="adm-input" value="0" style="color: #34D399; font-weight: 600;" oninput="recalculateModalTotals()">
                    </div>
                    <div>
                        <label class="adm-form-label" style="font-size: 12px;">Payment Method</label>
                        <select name="payment_method" id="modalPaymentMethod" class="adm-input">
                            <option value="upi">UPI / GPay / PhonePe</option>
                            <option value="cash">Cash on Arrival</option>
                            <option value="card">Credit / Debit Card</option>
                            <option value="bank_transfer">Bank NEFT / RTGS</option>
                            <option value="online">Online Gateway</option>
                            <option value="unspecified">Unspecified / Pending</option>
                        </select>
                    </div>
                </div>

                <!-- Live Summary Calculated Bar -->
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; margin-top: 18px; padding-top: 14px; border-top: 1px solid rgba(255,255,255,0.08); text-align: center;">
                    <div style="background: rgba(255,255,255,0.04); padding: 10px; border-radius: 6px;">
                        <span style="font-size: 11.5px; color: var(--adm-text-muted); display: block;">Calculated Gross Total</span>
                        <strong id="modalGrossLabel" style="font-size: 16px; color: #FFFFFF; font-family: monospace;">₹0.00</strong>
                    </div>
                    <div style="background: rgba(197, 160, 89, 0.1); padding: 10px; border-radius: 6px; border: 1px solid rgba(197,160,89,0.3);">
                        <span style="font-size: 11.5px; color: var(--adm-gold); display: block; font-weight: 700;">NET GRAND TOTAL</span>
                        <strong id="modalNetLabel" style="font-size: 18px; color: var(--adm-gold); font-family: monospace;">₹0.00</strong>
                    </div>
                    <div id="modalDueCard" style="background: rgba(239, 68, 68, 0.12); padding: 10px; border-radius: 6px; border: 1px solid rgba(239,68,68,0.3);">
                        <span id="modalDueTitle" style="font-size: 11.5px; color: #F87171; display: block; font-weight: 700;">BALANCE PAYABLE</span>
                        <strong id="modalDueLabel" style="font-size: 18px; color: #F87171; font-family: monospace;">₹0.00</strong>
                    </div>
                </div>

                <div style="margin-top: 14px;">
                    <label class="adm-form-label" style="font-size: 12px;">Billing / Concierge Notes (Optional)</label>
                    <input type="text" name="billing_notes" id="modalBillingNotes" class="adm-input" placeholder="e.g. Complimentary firewood provided, guest requested company GST invoice">
                </div>
            </div>

            <!-- Modal Action Buttons -->
            <div style="display: flex; justify-content: flex-end; gap: 12px; align-items: center;">
                <button type="button" onclick="closeBillEditModal()" class="adm-btn adm-btn-secondary" style="padding: 10px 20px;">
                    Cancel
                </button>
                <button type="submit" class="adm-btn adm-btn-primary" style="padding: 10px 24px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Save & Update Folio</span>
                </button>
            </div>

        </form>

    </div>
</div>

<script>
// Dynamic Modal Logic
var activeModalBooking = null;

function openBillEditModal(booking) {
    activeModalBooking = booking;
    var p = booking.parsed;

    document.getElementById('modalBookingId').value = booking.id;
    document.getElementById('modalBookingRef').value = booking.reference_code;
    document.getElementById('modalRefCode').innerText = booking.reference_code;
    document.getElementById('modalGuestSubtitle').innerText = booking.guest_name + ' • ' + (booking.guest_phone || '') + ' • ' + (p.nights || 1) + ' Night(s)';

    document.getElementById('modalVillaTitle').value = booking.room_title || 'Sanctuary Villa';
    document.getElementById('modalNights').value = p.nights + ' Night(s)';
    document.getElementById('modalRoomAmount').value = p.room_amount || 0;

    document.getElementById('modalDiscount').value = p.discount_amount || 0;
    document.getElementById('modalTax').value = p.tax_amount || 0;
    document.getElementById('modalAdvance').value = p.advance_paid || 0;
    document.getElementById('modalPaymentMethod').value = p.payment_method || 'upi';
    document.getElementById('modalBillingNotes').value = booking.billing_notes || '';

    // Populate Food Items
    var foodContainer = document.getElementById('foodItemsContainer');
    foodContainer.innerHTML = '';
    if (p.food_items && p.food_items.length > 0) {
        p.food_items.forEach(function(fi) {
            appendFoodRow(fi.heading, fi.category || 'Dining', fi.quantity || 1, fi.price || 0);
        });
    }

    // Populate Activities
    var actContainer = document.getElementById('activityItemsContainer');
    actContainer.innerHTML = '';
    if (p.activities && p.activities.length > 0) {
        p.activities.forEach(function(act) {
            appendActivityRow(act.title, act.timing || 'Curated Schedule', act.quantity || 1, act.price || 0);
        });
    }

    // Populate Custom Items
    var custContainer = document.getElementById('customItemsContainer');
    custContainer.innerHTML = '';
    if (p.custom_items && p.custom_items.length > 0) {
        p.custom_items.forEach(function(ci) {
            appendCustomRow(ci.title, ci.quantity || 1, ci.price || 0);
        });
    }

    recalculateModalTotals();
    document.getElementById('billEditModal').style.display = 'block';
}

function closeBillEditModal() {
    document.getElementById('billEditModal').style.display = 'none';
}

// Food Row Helpers
function appendFoodRow(heading, category, qty, price) {
    var container = document.getElementById('foodItemsContainer');
    var row = document.createElement('div');
    row.className = 'food-edit-row';
    row.style.cssText = 'display: grid; grid-template-columns: 2fr 1fr 80px 100px 36px; gap: 8px; margin-bottom: 8px; align-items: center;';
    row.innerHTML = `
        <input type="text" name="food_heading[]" value="${escapeHtml(heading)}" class="adm-input" placeholder="Dish / Meal Title" style="height: 36px; font-size: 12.5px;">
        <input type="text" name="food_category[]" value="${escapeHtml(category)}" class="adm-input" placeholder="Category" style="height: 36px; font-size: 12px;">
        <input type="number" name="food_qty[]" value="${qty}" min="1" class="adm-input" style="height: 36px; text-align: center;" oninput="recalculateModalTotals()">
        <input type="number" step="0.01" name="food_price[]" value="${price}" class="adm-input" placeholder="Rate" style="height: 36px; text-align: right;" oninput="recalculateModalTotals()">
        <button type="button" onclick="this.parentElement.remove(); recalculateModalTotals();" class="adm-btn" style="height: 36px; background: rgba(239, 68, 68, 0.2); color: #F87171; padding: 0; display: flex; align-items: center; justify-content: center;" title="Remove">
            <i class="fa-solid fa-trash-can" style="font-size: 12px;"></i>
        </button>
    `;
    container.appendChild(row);
}

function addFoodItemRow() {
    appendFoodRow('', 'Dining', 1, 0);
    recalculateModalTotals();
}

function addSelectedMenuPreset() {
    var select = document.getElementById('foodMenuPresetSelect');
    if (!select.value) return;
    try {
        var item = JSON.parse(select.value);
        appendFoodRow(item.heading, item.category, 1, parseFloat(item.price || 0));
        select.value = '';
        recalculateModalTotals();
    } catch(e){}
}

// Activity Row Helpers
function appendActivityRow(title, timing, qty, price) {
    var container = document.getElementById('activityItemsContainer');
    var row = document.createElement('div');
    row.className = 'activity-edit-row';
    row.style.cssText = 'display: grid; grid-template-columns: 2fr 1fr 80px 100px 36px; gap: 8px; margin-bottom: 8px; align-items: center;';
    row.innerHTML = `
        <input type="text" name="act_title[]" value="${escapeHtml(title)}" class="adm-input" placeholder="Experience Title" style="height: 36px; font-size: 12.5px;">
        <input type="text" name="act_timing[]" value="${escapeHtml(timing)}" class="adm-input" placeholder="Schedule / Notes" style="height: 36px; font-size: 12px;">
        <input type="number" name="act_qty[]" value="${qty}" min="1" class="adm-input" style="height: 36px; text-align: center;" oninput="recalculateModalTotals()">
        <input type="number" step="0.01" name="act_price[]" value="${price}" class="adm-input" placeholder="Rate" style="height: 36px; text-align: right;" oninput="recalculateModalTotals()">
        <button type="button" onclick="this.parentElement.remove(); recalculateModalTotals();" class="adm-btn" style="height: 36px; background: rgba(239, 68, 68, 0.2); color: #F87171; padding: 0; display: flex; align-items: center; justify-content: center;" title="Remove">
            <i class="fa-solid fa-trash-can" style="font-size: 12px;"></i>
        </button>
    `;
    container.appendChild(row);
}

function addActivityItemRow() {
    appendActivityRow('', 'Curated Schedule', 1, 0);
    recalculateModalTotals();
}

function addSelectedExperiencePreset() {
    var select = document.getElementById('experiencePresetSelect');
    if (!select.value) return;
    try {
        var item = JSON.parse(select.value);
        appendActivityRow(item.title, item.timing, 1, 0);
        select.value = '';
        recalculateModalTotals();
    } catch(e){}
}

// Custom Incidentals Row Helpers
function appendCustomRow(title, qty, price) {
    var container = document.getElementById('customItemsContainer');
    var row = document.createElement('div');
    row.className = 'custom-edit-row';
    row.style.cssText = 'display: grid; grid-template-columns: 2.5fr 80px 100px 36px; gap: 8px; margin-bottom: 8px; align-items: center;';
    row.innerHTML = `
        <input type="text" name="cust_title[]" value="${escapeHtml(title)}" class="adm-input" placeholder="e.g. Special Campfire Wood, Cab Transfer, Spa Service" style="height: 36px; font-size: 12.5px;">
        <input type="number" name="cust_qty[]" value="${qty}" min="1" class="adm-input" style="height: 36px; text-align: center;" oninput="recalculateModalTotals()">
        <input type="number" step="0.01" name="cust_price[]" value="${price}" class="adm-input" placeholder="Amount" style="height: 36px; text-align: right;" oninput="recalculateModalTotals()">
        <button type="button" onclick="this.parentElement.remove(); recalculateModalTotals();" class="adm-btn" style="height: 36px; background: rgba(239, 68, 68, 0.2); color: #F87171; padding: 0; display: flex; align-items: center; justify-content: center;" title="Remove">
            <i class="fa-solid fa-trash-can" style="font-size: 12px;"></i>
        </button>
    `;
    container.appendChild(row);
}

function addCustomItemRow() {
    appendCustomRow('', 1, 0);
    recalculateModalTotals();
}

// Recalculate Live Totals
function recalculateModalTotals() {
    var roomAmt = parseFloat(document.getElementById('modalRoomAmount').value) || 0;

    // Food total
    var foodTotal = 0;
    document.querySelectorAll('.food-edit-row').forEach(function(r) {
        var qty = parseFloat(r.querySelector('input[name="food_qty[]"]').value) || 1;
        var pr = parseFloat(r.querySelector('input[name="food_price[]"]').value) || 0;
        foodTotal += (qty * pr);
    });
    document.getElementById('foodSubtotalLabel').innerText = '₹' + foodTotal.toLocaleString('en-IN', {minimumFractionDigits: 2});

    // Activities total
    var actTotal = 0;
    document.querySelectorAll('.activity-edit-row').forEach(function(r) {
        var qty = parseFloat(r.querySelector('input[name="act_qty[]"]').value) || 1;
        var pr = parseFloat(r.querySelector('input[name="act_price[]"]').value) || 0;
        actTotal += (qty * pr);
    });
    document.getElementById('activitiesSubtotalLabel').innerText = '₹' + actTotal.toLocaleString('en-IN', {minimumFractionDigits: 2});

    // Custom total
    var custTotal = 0;
    document.querySelectorAll('.custom-edit-row').forEach(function(r) {
        var qty = parseFloat(r.querySelector('input[name="cust_qty[]"]').value) || 1;
        var pr = parseFloat(r.querySelector('input[name="cust_price[]"]').value) || 0;
        custTotal += (qty * pr);
    });

    var discount = parseFloat(document.getElementById('modalDiscount').value) || 0;
    var tax = parseFloat(document.getElementById('modalTax').value) || 0;
    var advance = parseFloat(document.getElementById('modalAdvance').value) || 0;

    var gross = roomAmt + foodTotal + actTotal + custTotal;
    var net = Math.max(0, gross + tax - discount);
    var due = Math.max(0, net - advance);

    document.getElementById('modalGrossLabel').innerText = '₹' + gross.toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('modalNetLabel').innerText = '₹' + net.toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('modalDueLabel').innerText = '₹' + due.toLocaleString('en-IN', {minimumFractionDigits: 2});

    var dueCard = document.getElementById('modalDueCard');
    var dueTitle = document.getElementById('modalDueTitle');
    var dueLabel = document.getElementById('modalDueLabel');
    if (due <= 0) {
        dueCard.style.background = 'rgba(16, 185, 129, 0.12)';
        dueCard.style.borderColor = 'rgba(16, 185, 129, 0.3)';
        dueTitle.style.color = '#34D399';
        dueTitle.innerText = 'FULLY SETTLED';
        dueLabel.style.color = '#34D399';
    } else {
        dueCard.style.background = 'rgba(239, 68, 68, 0.12)';
        dueCard.style.borderColor = 'rgba(239, 68, 68, 0.3)';
        dueTitle.style.color = '#F87171';
        dueTitle.innerText = 'BALANCE PAYABLE';
        dueLabel.style.color = '#F87171';
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#039;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

// Close on outside click
window.onclick = function(event) {
    var modal = document.getElementById('billEditModal');
    if (event.target === modal) {
        closeBillEditModal();
    }
};
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
