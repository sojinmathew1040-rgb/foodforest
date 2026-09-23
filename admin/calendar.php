<?php
// =========================================================================
// Food Forest Sanctuary — Interactive Multi-Channel Booking Calendar
// File: admin/calendar.php
// Provides a real-time visual calendar grid & multi-chalet timeline view
// with MakeMyTrip, Airbnb, Booking.com, and direct reservation tracking.
// =========================================================================
$page_title = 'Booking Calendar';
$page_subtitle = 'Visual timeline & multi-channel occupancy planner';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db();
ensure_ical_and_channel_schema($pdo);

// Selected month and year
$curr_month = isset($_GET['month']) ? max(1, min(12, (int)$_GET['month'])) : (int)date('n');
$curr_year = isset($_GET['year']) ? max(2020, min(2035, (int)$_GET['year'])) : (int)date('Y');
$selected_room = trim($_GET['room'] ?? 'all');
$selected_source = trim($_GET['source'] ?? 'all');

// Navigation helpers
$prev_month = $curr_month - 1;
$prev_year = $curr_year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $curr_month + 1;
$next_year = $curr_year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

$first_day_ts = strtotime("{$curr_year}-{$curr_month}-01");
$days_in_month = (int)date('t', $first_day_ts);
$first_day_of_week = (int)date('w', $first_day_ts); // 0 (Sun) to 6 (Sat)
$month_name = date('F', $first_day_ts);

// Handle Quick Add / Offline Block Reservation
$alert_message = '';
$alert_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $alert_message = 'Security token validation failed.';
        $alert_type = 'error';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'quick_book' || $action === 'block_dates') {
            $villa = trim($_POST['villa_type'] ?? 'treehouse');
            $source = trim($_POST['booking_source'] ?? 'offline_direct');
            $guest_name = trim($_POST['guest_name'] ?? ($action === 'block_dates' ? 'Maintenance / Owner Block' : 'Walk-in Guest'));
            $guest_phone = trim($_POST['guest_phone'] ?? 'Offline');
            $guest_email = trim($_POST['guest_email'] ?? '');
            $cin = trim($_POST['checkin_date'] ?? '');
            $cout = trim($_POST['checkout_date'] ?? '');
            $guests = max(1, (int)($_POST['guests_count'] ?? 2));
            $notes = trim($_POST['special_notes'] ?? '');
            $total_amt = (float)($_POST['total_amount'] ?? 0);

            if (empty($cin) || empty($cout) || $cin >= $cout) {
                $alert_message = 'Please select valid check-in and check-out dates.';
                $alert_type = 'error';
            } elseif (!check_room_availability($pdo, $villa, $cin, $cout)) {
                $alert_message = 'Selected dates conflict with an existing reservation or OTA block for this chalet.';
                $alert_type = 'error';
            } else {
                $d1 = new DateTime($cin);
                $d2 = new DateTime($cout);
                $nights = max(1, $d1->diff($d2)->days);

                $prefix = ($source === 'makemytrip') ? 'MMT' : (($source === 'airbnb') ? 'ABNB' : 'DIR');
                $ref = 'FF-' . $prefix . '-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));

                $ins = $pdo->prepare("INSERT INTO bookings 
                    (reference_code, villa_type, booking_source, guest_name, guest_phone, guest_email, guests_count, checkin_date, checkout_date, nights, special_notes, total_amount, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')");
                $ins->execute([$ref, $villa, $source, $guest_name, $guest_phone, $guest_email, $guests, $cin, $cout, $nights, $notes, $total_amt]);

                $alert_message = "Reservation {$ref} successfully recorded for {$villa}!";
                $alert_type = 'success';
            }
        } elseif ($action === 'cancel_booking') {
            $b_id = (int)$_POST['booking_id'];
            $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?")->execute([$b_id]);
            $alert_message = "Booking #{$b_id} marked as cancelled.";
            $alert_type = 'success';
        }
    }
}

// Fetch all chalets
$rooms = $pdo->query("SELECT id, slug, title, stay_type, rate_per_night FROM rooms ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Calculate calendar date window
$cal_start_date = date('Y-m-d', strtotime("-{$first_day_of_week} days", $first_day_ts));
$total_cells = ceil(($first_day_of_week + $days_in_month) / 7) * 7;
$cal_end_date = date('Y-m-d', strtotime("+ " . ($total_cells - 1) . " days", strtotime($cal_start_date)));

// Fetch bookings in calendar range
$b_sql = "SELECT b.*, r.title AS room_title 
          FROM bookings b 
          LEFT JOIN rooms r ON b.villa_type = r.slug 
          WHERE b.status NOT IN ('cancelled', 'rejected') 
            AND b.checkin_date <= ? 
            AND b.checkout_date >= ?";

$b_params = [$cal_end_date, $cal_start_date];

if ($selected_room !== 'all') {
    $b_sql .= " AND b.villa_type = ?";
    $b_params[] = $selected_room;
}

if ($selected_source !== 'all') {
    $b_sql .= " AND b.booking_source = ?";
    $b_params[] = $selected_source;
}

$b_sql .= " ORDER BY b.checkin_date ASC";
$b_stmt = $pdo->prepare($b_sql);
$b_stmt->execute($b_params);
$bookings = $b_stmt->fetchAll(PDO::FETCH_ASSOC);

// Index bookings by date
$bookings_by_date = [];
$source_counts = [
    'direct_website' => 0,
    'makemytrip' => 0,
    'airbnb' => 0,
    'booking_com' => 0,
    'offline_direct' => 0
];

foreach ($bookings as $b) {
    $src = strtolower(trim($b['booking_source'] ?: 'direct_website'));
    if (stripos($src, 'make') !== false || stripos($src, 'mmt') !== false) {
        $src_key = 'makemytrip';
    } elseif (stripos($src, 'air') !== false) {
        $src_key = 'airbnb';
    } elseif (stripos($src, 'booking') !== false) {
        $src_key = 'booking_com';
    } elseif ($src === 'direct_website') {
        $src_key = 'direct_website';
    } else {
        $src_key = 'offline_direct';
    }

    if (isset($source_counts[$src_key])) {
        $source_counts[$src_key]++;
    } else {
        $source_counts['offline_direct']++;
    }

    $c_start = new DateTime($b['checkin_date']);
    $c_end = new DateTime($b['checkout_date']);
    
    while ($c_start < $c_end) {
        $d_str = $c_start->format('Y-m-d');
        if (!isset($bookings_by_date[$d_str])) {
            $bookings_by_date[$d_str] = [];
        }
        $bookings_by_date[$d_str][] = $b;
        $c_start->modify('+1 day');
    }
}

$total_bookings_count = count($bookings);
?>

<div class="adm-content-container">

    <?php if (!empty($alert_message)): ?>
        <div class="adm-alert adm-alert-<?php echo $alert_type; ?>" style="margin-bottom: 20px;">
            <i class="fa-solid fa-<?php echo $alert_type === 'success' ? 'circle-check' : 'circle-exclamation'; ?>"></i>
            <span><?php echo htmlspecialchars($alert_message); ?></span>
        </div>
    <?php endif; ?>

    <!-- Header KPI Row -->
    <div class="adm-kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 24px;">
        <div class="adm-kpi-card">
            <div class="adm-kpi-label">Active Bookings (<?php echo $month_name; ?>)</div>
            <div class="adm-kpi-number"><?php echo $total_bookings_count; ?></div>
            <span class="adm-kpi-trend">All Channels & Chalets</span>
        </div>
        <div class="adm-kpi-card" style="border-left: 3px solid #C5A059;">
            <div class="adm-kpi-label">🟡 Direct Website Stays</div>
            <div class="adm-kpi-number"><?php echo $source_counts['direct_website']; ?></div>
            <span class="adm-kpi-trend">Commission-Free Reservations</span>
        </div>
        <div class="adm-kpi-card" style="border-left: 3px solid #e74c3c;">
            <div class="adm-kpi-label">🔴 MakeMyTrip (InGoMMT)</div>
            <div class="adm-kpi-number"><?php echo $source_counts['makemytrip']; ?></div>
            <span class="adm-kpi-trend">Synced via iCal</span>
        </div>
        <div class="adm-kpi-card" style="border-left: 3px solid #FF5A5F;">
            <div class="adm-kpi-label">🌺 Airbnb / Booking.com</div>
            <div class="adm-kpi-number"><?php echo $source_counts['airbnb'] + $source_counts['booking_com']; ?></div>
            <span class="adm-kpi-trend">External OTA Feeds</span>
        </div>
    </div>

    <!-- Calendar Controls Bar -->
    <div class="adm-card" style="margin-bottom: 24px; padding: 16px 20px;">
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px;">
            
            <!-- Month Nav -->
            <div style="display: flex; align-items: center; gap: 12px;">
                <a href="calendar.php?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>&room=<?php echo urlencode($selected_room); ?>&source=<?php echo urlencode($selected_source); ?>" 
                   class="adm-btn-action outline" style="padding: 6px 14px;">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
                
                <h2 style="font-family: var(--adm-font-title); font-size: 20px; color: #FFFFFF; margin: 0; min-width: 170px; text-align: center;">
                    <?php echo $month_name . ' ' . $curr_year; ?>
                </h2>

                <a href="calendar.php?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>&room=<?php echo urlencode($selected_room); ?>&source=<?php echo urlencode($selected_source); ?>" 
                   class="adm-btn-action outline" style="padding: 6px 14px;">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>

                <a href="calendar.php?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>&room=<?php echo urlencode($selected_room); ?>&source=<?php echo urlencode($selected_source); ?>" 
                   class="adm-btn-action gold" style="padding: 6px 12px; font-size: 11px;">
                    Today
                </a>
            </div>

            <!-- Filters & Actions -->
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px;">
                
                <!-- Room Filter -->
                <form method="GET" action="calendar.php" id="cal-filter-form" style="display: flex; gap: 8px;">
                    <input type="hidden" name="month" value="<?php echo $curr_month; ?>">
                    <input type="hidden" name="year" value="<?php echo $curr_year; ?>">
                    
                    <select name="room" class="adm-input" style="padding: 6px 12px; font-size: 12px; max-width: 180px;" onchange="this.form.submit()">
                        <option value="all" <?php echo $selected_room === 'all' ? 'selected' : ''; ?>>All Chalets</option>
                        <?php foreach ($rooms as $r): ?>
                            <option value="<?php echo htmlspecialchars($r['slug']); ?>" <?php echo $selected_room === $r['slug'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($r['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="source" class="adm-input" style="padding: 6px 12px; font-size: 12px; max-width: 160px;" onchange="this.form.submit()">
                        <option value="all" <?php echo $selected_source === 'all' ? 'selected' : ''; ?>>All Channels</option>
                        <option value="direct_website" <?php echo $selected_source === 'direct_website' ? 'selected' : ''; ?>>Direct Website</option>
                        <option value="makemytrip" <?php echo $selected_source === 'makemytrip' ? 'selected' : ''; ?>>MakeMyTrip</option>
                        <option value="airbnb" <?php echo $selected_source === 'airbnb' ? 'selected' : ''; ?>>Airbnb</option>
                        <option value="booking_com" <?php echo $selected_source === 'booking_com' ? 'selected' : ''; ?>>Booking.com</option>
                        <option value="offline_direct" <?php echo $selected_source === 'offline_direct' ? 'selected' : ''; ?>>Offline / Phone</option>
                    </select>
                </form>

                <!-- Sync Now Trigger -->
                <a href="channel_sync.php" class="adm-btn-action outline" title="Manage & Sync Feeds" style="padding: 7px 14px; font-size: 12px;">
                    <i class="fa-solid fa-arrows-rotate"></i> OTA Sync
                </a>

                <!-- Quick Block Button -->
                <button type="button" class="adm-btn-action gold" onclick="openQuickBookModal()" style="padding: 7px 16px; font-size: 12px;">
                    <i class="fa-solid fa-plus"></i> Block / Reserve
                </button>
            </div>
        </div>
    </div>

    <!-- Calendar Legend Bar -->
    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 14px; margin-bottom: 16px; padding: 10px 16px; background: rgba(20, 40, 28, 0.4); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 8px; font-size: 11.5px;">
        <span style="color: var(--adm-gold); font-weight: 700; margin-right: 4px;"><i class="fa-solid fa-circle-info"></i> Channel Key:</span>
        <span style="display: inline-flex; align-items: center; gap: 6px;"><span style="width: 10px; height: 10px; border-radius: 2px; background: #C5A059;"></span> Direct Website</span>
        <span style="display: inline-flex; align-items: center; gap: 6px;"><span style="width: 10px; height: 10px; border-radius: 2px; background: #e74c3c;"></span> MakeMyTrip (InGoMMT)</span>
        <span style="display: inline-flex; align-items: center; gap: 6px;"><span style="width: 10px; height: 10px; border-radius: 2px; background: #FF5A5F;"></span> Airbnb</span>
        <span style="display: inline-flex; align-items: center; gap: 6px;"><span style="width: 10px; height: 10px; border-radius: 2px; background: #003580;"></span> Booking.com</span>
        <span style="display: inline-flex; align-items: center; gap: 6px;"><span style="width: 10px; height: 10px; border-radius: 2px; background: #27ae60;"></span> Offline / Walk-in</span>
    </div>

    <!-- Monthly Calendar Grid Layout -->
    <div class="adm-card" style="padding: 0; overflow: hidden; border-radius: 12px;">
        
        <!-- Days Header (Sun - Sat) -->
        <div style="display: grid; grid-template-columns: repeat(7, 1fr); background: #14281c; border-bottom: 1px solid rgba(197, 160, 89, 0.2); text-align: center; font-weight: 700; font-size: 12px; color: var(--adm-gold); padding: 12px 0;">
            <div>SUN</div>
            <div>MON</div>
            <div>TUE</div>
            <div>WED</div>
            <div>THU</div>
            <div>FRI</div>
            <div>SAT</div>
        </div>

        <!-- Days Grid -->
        <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; background: rgba(197, 160, 89, 0.15);">
            <?php 
            $curr_cal_date = new DateTime($cal_start_date);
            $today_str = date('Y-m-d');

            for ($i = 0; $i < $total_cells; $i++):
                $d_str = $curr_cal_date->format('Y-m-d');
                $d_num = (int)$curr_cal_date->format('j');
                $is_current_month = ((int)$curr_cal_date->format('n') === $curr_month);
                $is_today = ($d_str === $today_str);
                $day_bookings = $bookings_by_date[$d_str] ?? [];
            ?>
                <div class="cal-day-cell <?php echo !$is_current_month ? 'other-month' : ''; ?> <?php echo $is_today ? 'is-today' : ''; ?>"
                     style="min-height: 110px; background: <?php echo $is_current_month ? '#0e1f16' : '#08130d'; ?>; padding: 6px 8px; position: relative; cursor: pointer; transition: background 0.2s ease;"
                     onclick="handleDayClick('<?php echo $d_str; ?>')">
                    
                    <!-- Date Number & Header -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <span style="font-size: 12px; font-weight: <?php echo $is_today ? '800' : '600'; ?>; color: <?php echo $is_today ? 'var(--adm-gold)' : ($is_current_month ? '#FFFFFF' : 'rgba(255,255,255,0.25)'); ?>; <?php echo $is_today ? 'background: rgba(197, 160, 89, 0.2); padding: 1px 6px; border-radius: 10px; border: 1px solid var(--adm-gold);' : ''; ?>">
                            <?php echo $d_num; ?>
                        </span>
                        
                        <?php if (count($day_bookings) > 0): ?>
                            <span style="font-size: 9px; color: var(--adm-gold); font-weight: 700;">
                                <?php echo count($day_bookings); ?> Stay<?php echo count($day_bookings) > 1 ? 's' : ''; ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Bookings Pills -->
                    <div style="display: flex; flex-direction: column; gap: 3px;">
                        <?php 
                        $max_show = 3;
                        $rendered = 0;
                        foreach ($day_bookings as $b):
                            if ($rendered >= $max_show) break;
                            $rendered++;
                            
                            $src = strtolower(trim($b['booking_source'] ?: 'direct_website'));
                            $pill_bg = '#C5A059';
                            $pill_color = '#101F15';
                            $badge_icon = 'fa-solid fa-house';

                            if (stripos($src, 'make') !== false || stripos($src, 'mmt') !== false) {
                                $pill_bg = '#e74c3c';
                                $pill_color = '#FFFFFF';
                                $badge_icon = 'fa-solid fa-plane-arrival';
                            } elseif (stripos($src, 'air') !== false) {
                                $pill_bg = '#FF5A5F';
                                $pill_color = '#FFFFFF';
                                $badge_icon = 'fa-brands fa-airbnb';
                            } elseif (stripos($src, 'booking') !== false) {
                                $pill_bg = '#003580';
                                $pill_color = '#FFFFFF';
                                $badge_icon = 'fa-solid fa-b';
                            } elseif ($src === 'offline_direct') {
                                $pill_bg = '#27ae60';
                                $pill_color = '#FFFFFF';
                                $badge_icon = 'fa-solid fa-phone';
                            }
                        ?>
                            <div class="cal-booking-pill" 
                                 onclick="event.stopPropagation(); openBookingDetailModal(<?php echo htmlspecialchars(json_encode($b)); ?>)"
                                 style="background: <?php echo $pill_bg; ?>; color: <?php echo $pill_color; ?>; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 4px;"
                                 title="<?php echo htmlspecialchars($b['guest_name'] . ' (' . ($b['room_title'] ?: $b['villa_type']) . ') - ' . $b['checkin_date'] . ' to ' . $b['checkout_date']); ?>">
                                <i class="<?php echo $badge_icon; ?>" style="font-size: 9px;"></i>
                                <span><?php echo htmlspecialchars($b['guest_name']); ?></span>
                            </div>
                        <?php endforeach; ?>

                        <?php if (count($day_bookings) > $max_show): ?>
                            <div style="font-size: 9px; color: var(--adm-gold); font-weight: 700; text-align: center;">
                                +<?php echo count($day_bookings) - $max_show; ?> more
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php 
                $curr_cal_date->modify('+1 day');
            endfor; 
            ?>
        </div>
    </div>

</div>

<!-- Quick Block / Reserve Modal -->
<div class="adm-modal" id="modal-quick-book" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(6px);">
    <div class="adm-modal-content" style="background: #112419; border: 1.5px solid var(--adm-gold); border-radius: 12px; width: 100%; max-width: 520px; padding: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.6);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid rgba(197,160,89,0.25); padding-bottom: 12px;">
            <h3 style="font-family: var(--adm-font-title); font-size: 18px; color: #FFFFFF; margin: 0;">
                <i class="fa-solid fa-calendar-plus" style="color: var(--adm-gold);"></i> Reserve Chalet / Block Dates
            </h3>
            <button type="button" onclick="closeModal('modal-quick-book')" style="background: none; border: none; color: #FFFFFF; font-size: 18px; cursor: pointer;">&times;</button>
        </div>

        <form method="POST" action="calendar.php?month=<?php echo $curr_month; ?>&year=<?php echo $curr_year; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="quick_book">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label class="adm-form-label">Sanctuary Chalet *</label>
                    <select name="villa_type" id="qb-villa" class="adm-input" required>
                        <?php foreach ($rooms as $r): ?>
                            <option value="<?php echo htmlspecialchars($r['slug']); ?>">
                                <?php echo htmlspecialchars($r['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="adm-form-label">Reservation Source *</label>
                    <select name="booking_source" class="adm-input" required>
                        <option value="offline_direct">Offline / Phone Walk-in</option>
                        <option value="makemytrip">MakeMyTrip (InGoMMT)</option>
                        <option value="airbnb">Airbnb</option>
                        <option value="booking_com">Booking.com</option>
                        <option value="direct_website">Direct Website VIP</option>
                        <option value="maintenance_block">Maintenance / Estate Block</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label class="adm-form-label">Check-In Date *</label>
                    <input type="date" name="checkin_date" id="qb-checkin" class="adm-input" required>
                </div>
                <div>
                    <label class="adm-form-label">Check-Out Date *</label>
                    <input type="date" name="checkout_date" id="qb-checkout" class="adm-input" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label class="adm-form-label">Guest / Agency Name *</label>
                    <input type="text" name="guest_name" id="qb-guest" class="adm-input" placeholder="e.g. Mr. Anand / MakeMyTrip Block" required>
                </div>
                <div>
                    <label class="adm-form-label">Phone Number</label>
                    <input type="text" name="guest_phone" class="adm-input" placeholder="+91 98765 43210">
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label class="adm-form-label">Special Notes / Channel Notes</label>
                <textarea name="special_notes" class="adm-input" rows="2" placeholder="Dietary preferences, OTA voucher number, etc."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="adm-btn-action outline" onclick="closeModal('modal-quick-book')">Cancel</button>
                <button type="submit" class="adm-btn-action gold"><i class="fa-solid fa-check"></i> Confirm Reservation</button>
            </div>
        </form>
    </div>
</div>

<!-- Booking Inspector Detail Modal -->
<div class="adm-modal" id="modal-view-booking" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(6px);">
    <div class="adm-modal-content" style="background: #112419; border: 1.5px solid var(--adm-gold); border-radius: 12px; width: 100%; max-width: 480px; padding: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.6);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid rgba(197,160,89,0.25); padding-bottom: 10px;">
            <h3 style="font-family: var(--adm-font-title); font-size: 18px; color: #FFFFFF; margin: 0;" id="vb-title">
                Reservation Record
            </h3>
            <button type="button" onclick="closeModal('modal-view-booking')" style="background: none; border: none; color: #FFFFFF; font-size: 18px; cursor: pointer;">&times;</button>
        </div>

        <div style="display: flex; flex-direction: column; gap: 10px; font-size: 13px; color: #FFFFFF; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed rgba(255,255,255,0.1); padding-bottom: 6px;">
                <span style="color: var(--adm-gold); font-weight: 700;">Reference Code:</span>
                <span id="vb-ref" style="font-family: monospace; font-weight: 700;"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed rgba(255,255,255,0.1); padding-bottom: 6px;">
                <span style="color: var(--adm-gold); font-weight: 700;">Channel / Source:</span>
                <span id="vb-source" style="font-weight: 700;"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed rgba(255,255,255,0.1); padding-bottom: 6px;">
                <span style="color: var(--adm-gold); font-weight: 700;">Sanctuary Stay:</span>
                <span id="vb-chalet"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed rgba(255,255,255,0.1); padding-bottom: 6px;">
                <span style="color: var(--adm-gold); font-weight: 700;">Stay Dates:</span>
                <span id="vb-dates"></span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed rgba(255,255,255,0.1); padding-bottom: 6px;">
                <span style="color: var(--adm-gold); font-weight: 700;">Guest Phone:</span>
                <span id="vb-phone"></span>
            </div>
            <div style="background: rgba(0,0,0,0.25); padding: 8px 12px; border-radius: 6px; font-size: 12px; color: rgba(255,255,255,0.8);">
                <div style="color: var(--adm-gold); font-weight: 700; margin-bottom: 2px;">Notes:</div>
                <div id="vb-notes" style="white-space: pre-line;"></div>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; gap: 10px;">
            <form method="POST" action="calendar.php?month=<?php echo $curr_month; ?>&year=<?php echo $curr_year; ?>" onsubmit="return confirm('Are you sure you want to cancel this reservation?');">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="action" value="cancel_booking">
                <input type="hidden" name="booking_id" id="vb-booking-id" value="">
                <button type="submit" class="adm-btn-action" style="background: rgba(231, 76, 60, 0.2); border: 1px solid #e74c3c; color: #e74c3c; font-size: 11.5px; padding: 7px 12px;">
                    <i class="fa-solid fa-trash-can"></i> Cancel
                </button>
            </form>

            <div style="display: flex; gap: 8px;">
                <a href="#" id="vb-wa-btn" target="_blank" class="adm-btn-action" style="background: #25D366; color: #072814; font-weight: 700; font-size: 11.5px; padding: 7px 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-brands fa-whatsapp"></i> WhatsApp
                </a>
                <button type="button" class="adm-btn-action outline" onclick="closeModal('modal-view-booking')" style="padding: 7px 14px; font-size: 11.5px;">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function openQuickBookModal(dateStr = '') {
    const modal = document.getElementById('modal-quick-book');
    if (!modal) return;
    
    if (dateStr) {
        document.getElementById('qb-checkin').value = dateStr;
        const d = new DateTime ? new Date(dateStr) : new Date();
        const nextDay = new Date(dateStr);
        nextDay.setDate(nextDay.getDate() + 1);
        document.getElementById('qb-checkout').value = nextDay.toISOString().split('T')[0];
    } else {
        const today = new Date();
        document.getElementById('qb-checkin').value = today.toISOString().split('T')[0];
        const nextDay = new Date();
        nextDay.setDate(nextDay.getDate() + 1);
        document.getElementById('qb-checkout').value = nextDay.toISOString().split('T')[0];
    }

    modal.style.display = 'flex';
}

function handleDayClick(dateStr) {
    openQuickBookModal(dateStr);
}

function openBookingDetailModal(b) {
    const modal = document.getElementById('modal-view-booking');
    if (!modal || !b) return;

    document.getElementById('vb-title').innerText = b.guest_name || 'Reservation';
    document.getElementById('vb-ref').innerText = b.reference_code || '-';
    document.getElementById('vb-source').innerText = b.booking_source || 'Direct';
    document.getElementById('vb-chalet').innerText = b.room_title || b.villa_type;
    document.getElementById('vb-dates').innerText = `${b.checkin_date} to ${b.checkout_date} (${b.nights} Nights)`;
    document.getElementById('vb-phone').innerText = b.guest_phone || 'N/A';
    document.getElementById('vb-notes').innerText = b.special_notes || 'No notes provided.';
    document.getElementById('vb-booking-id').value = b.id;

    const waPhone = (b.guest_phone || '').replace(/[^0-9]/g, '');
    const waBtn = document.getElementById('vb-wa-btn');
    if (waPhone && waPhone.length >= 10) {
        const text = encodeURIComponent(`Hello ${b.guest_name}, this is Concierge from Food Forest Sanctuary Kanthalloor regarding your upcoming stay (${b.checkin_date}).`);
        waBtn.href = `https://wa.me/${waPhone}?text=${text}`;
        waBtn.style.display = 'inline-flex';
    } else {
        waBtn.style.display = 'none';
    }

    modal.style.display = 'flex';
}

function closeModal(modalId) {
    const m = document.getElementById(modalId);
    if (m) m.style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
