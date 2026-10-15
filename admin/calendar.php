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
        } elseif ($action === 'revert_status' || $action === 'update_status') {
            $b_id = (int)$_POST['booking_id'];
            $target_status = $_POST['target_status'] ?? ($_POST['status'] ?? 'confirmed');

            if ($target_status === 'inhouse') {
                $stmt = $pdo->prepare("UPDATE bookings SET status = 'inhouse', checked_in_at = COALESCE(checked_in_at, NOW()), checked_out_at = NULL WHERE id = ?");
                $stmt->execute([$b_id]);
                $alert_message = "Reservation #{$b_id} set to In-House (Checked-In).";
            } elseif ($target_status === 'confirmed') {
                $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed', checked_in_at = NULL, checked_out_at = NULL WHERE id = ?");
                $stmt->execute([$b_id]);
                $alert_message = "Reservation #{$b_id} reverted to Confirmed (Upcoming).";
            } elseif ($target_status === 'pending') {
                $stmt = $pdo->prepare("UPDATE bookings SET status = 'pending', checked_in_at = NULL, checked_out_at = NULL WHERE id = ?");
                $stmt->execute([$b_id]);
                $alert_message = "Reservation #{$b_id} reset to Pending Review.";
            } elseif ($target_status === 'completed') {
                $stmt = $pdo->prepare("UPDATE bookings SET status = 'completed', checked_in_at = COALESCE(checked_in_at, NOW()), checked_out_at = COALESCE(checked_out_at, NOW()) WHERE id = ?");
                $stmt->execute([$b_id]);
                $alert_message = "Reservation #{$b_id} marked as Checked-Out & Completed.";
            } elseif ($target_status === 'waitlist') {
                $stmt = $pdo->prepare("UPDATE bookings SET status = 'waitlist', checked_in_at = NULL, checked_out_at = NULL WHERE id = ?");
                $stmt->execute([$b_id]);
                $alert_message = "Reservation #{$b_id} moved to Waiting List.";
            } elseif ($target_status === 'cancelled' || $target_status === 'rejected') {
                $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
                $stmt->execute([$b_id]);
                $alert_message = "Reservation #{$b_id} marked as Cancelled.";
            }
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

    <!-- Collapsible Compact Calendar KPI Metrics Strip (Single Horizontal Row) -->
    <section class="adm-kpi-strip-container" id="adm-cal-kpi-container" style="margin-bottom: 18px;">
        <div class="adm-kpi-strip-header">
            <div class="adm-kpi-strip-title-group">
                <span class="adm-kpi-strip-tag"><i class="fa-solid fa-calendar-days"></i></span>
                <span class="adm-kpi-strip-title"><?php echo $month_name; ?> Occupancy Summary</span>

                <!-- Compact inline preview pills visible when cards are collapsed -->
                <div class="adm-kpi-collapsed-summary" id="adm-cal-kpi-collapsed-summary">
                    <span class="adm-kpi-summary-pill emerald" title="Total Active Bookings">
                        <i class="fa-solid fa-calendar-check"></i> <?php echo $total_bookings_count; ?> Stays
                    </span>
                    <span class="adm-kpi-summary-pill gold" title="Direct Website Bookings">
                        <i class="fa-solid fa-globe"></i> <?php echo $source_counts['direct_website']; ?> Direct
                    </span>
                    <span class="adm-kpi-summary-pill red" title="MakeMyTrip Bookings">
                        <i class="fa-solid fa-plane"></i> <?php echo $source_counts['makemytrip']; ?> MMT
                    </span>
                    <span class="adm-kpi-summary-pill purple" title="Airbnb / Booking.com">
                        <i class="fa-brands fa-airbnb"></i> <?php echo $source_counts['airbnb'] + $source_counts['booking_com']; ?> OTA
                    </span>
                </div>
            </div>

            <button type="button" class="adm-kpi-toggle-btn" id="btn-toggle-cal-kpi" onclick="toggleCalendarKpiStrip();" aria-expanded="true" title="Collapse or expand occupancy metrics">
                <i class="fa-solid fa-chevron-up" id="adm-cal-kpi-toggle-icon"></i>
                <span id="adm-cal-kpi-toggle-label">Collapse</span>
            </button>
        </div>

        <div class="adm-kpi-row-wrapper" id="adm-cal-kpi-row-wrapper">
            <div class="adm-kpi-compact-row">
                <!-- 1. Active Bookings -->
                <div class="adm-kpi-mini-card emerald" title="Total Month Stays">
                    <div class="adm-kpi-mini-icon"><i class="fa-solid fa-calendar-check"></i></div>
                    <div class="adm-kpi-mini-body">
                        <div class="adm-kpi-mini-label">Active Bookings</div>
                        <div class="adm-kpi-mini-value"><?php echo $total_bookings_count; ?></div>
                    </div>
                    <div class="adm-kpi-mini-trend emerald-trend">All Channels</div>
                </div>

                <!-- 2. Direct Website Stays -->
                <div class="adm-kpi-mini-card gold" title="Commission-Free Direct Reservations">
                    <div class="adm-kpi-mini-icon"><i class="fa-solid fa-globe"></i></div>
                    <div class="adm-kpi-mini-body">
                        <div class="adm-kpi-mini-label">Direct Stays</div>
                        <div class="adm-kpi-mini-value"><?php echo $source_counts['direct_website']; ?></div>
                    </div>
                    <div class="adm-kpi-mini-trend gold-trend">Direct VIP</div>
                </div>

                <!-- 3. MakeMyTrip (InGoMMT) -->
                <div class="adm-kpi-mini-card red" title="MakeMyTrip Stays">
                    <div class="adm-kpi-mini-icon"><i class="fa-solid fa-plane-arrival"></i></div>
                    <div class="adm-kpi-mini-body">
                        <div class="adm-kpi-mini-label">MakeMyTrip</div>
                        <div class="adm-kpi-mini-value"><?php echo $source_counts['makemytrip']; ?></div>
                    </div>
                    <div class="adm-kpi-mini-trend red-trend">InGoMMT</div>
                </div>

                <!-- 4. Airbnb / Booking.com -->
                <div class="adm-kpi-mini-card purple" title="Airbnb / Booking.com Stays">
                    <div class="adm-kpi-mini-icon"><i class="fa-brands fa-airbnb"></i></div>
                    <div class="adm-kpi-mini-body">
                        <div class="adm-kpi-mini-label">OTA Channels</div>
                        <div class="adm-kpi-mini-value"><?php echo $source_counts['airbnb'] + $source_counts['booking_com']; ?></div>
                    </div>
                    <div class="adm-kpi-mini-trend purple-trend">Airbnb &amp; Booking</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Calendar Controls Bar -->
    <div class="adm-card adm-cal-controls-card" style="margin-bottom: 18px; padding: 14px 18px;">
        <div class="adm-cal-controls-inner">
            
            <!-- Month Nav & View Switcher -->
            <div class="adm-cal-month-nav">
                <a href="calendar.php?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>&room=<?php echo urlencode($selected_room); ?>&source=<?php echo urlencode($selected_source); ?>" 
                   class="adm-btn-action outline adm-cal-nav-btn" title="Previous Month">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
                
                <h2 class="adm-cal-month-heading">
                    <?php echo $month_name . ' ' . $curr_year; ?>
                </h2>

                <a href="calendar.php?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>&room=<?php echo urlencode($selected_room); ?>&source=<?php echo urlencode($selected_source); ?>" 
                   class="adm-btn-action outline adm-cal-nav-btn" title="Next Month">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>

                <a href="calendar.php?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>&room=<?php echo urlencode($selected_room); ?>&source=<?php echo urlencode($selected_source); ?>" 
                   class="adm-btn-action gold adm-cal-today-btn" title="Jump to Current Month">
                    Today
                </a>

                <!-- View Switcher (Grid vs List) -->
                <div class="adm-cal-view-toggle">
                    <button type="button" class="adm-cal-view-btn active" id="btn-view-grid" onclick="switchCalendarView('grid')" title="Month Grid View">
                        <i class="fa-solid fa-calendar-days"></i> <span class="adm-hide-mob-small">Grid</span>
                    </button>
                    <button type="button" class="adm-cal-view-btn" id="btn-view-agenda" onclick="switchCalendarView('agenda')" title="List / Agenda View">
                        <i class="fa-solid fa-list-ul"></i> <span class="adm-hide-mob-small">List</span>
                    </button>
                </div>
            </div>

            <!-- Filters & Actions -->
            <div class="adm-cal-actions-bar">
                <!-- Room & Channel Filters -->
                <form method="GET" action="calendar.php" id="cal-filter-form" class="adm-cal-filter-form">
                    <input type="hidden" name="month" value="<?php echo $curr_month; ?>">
                    <input type="hidden" name="year" value="<?php echo $curr_year; ?>">
                    
                    <select name="room" class="adm-input adm-cal-select" onchange="this.form.submit()" title="Filter by Chalet">
                        <option value="all" <?php echo $selected_room === 'all' ? 'selected' : ''; ?>>All Chalets</option>
                        <?php foreach ($rooms as $r): ?>
                            <option value="<?php echo htmlspecialchars($r['slug']); ?>" <?php echo $selected_room === $r['slug'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($r['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="source" class="adm-input adm-cal-select" onchange="this.form.submit()" title="Filter by Channel Source">
                        <option value="all" <?php echo $selected_source === 'all' ? 'selected' : ''; ?>>All Channels</option>
                        <option value="direct_website" <?php echo $selected_source === 'direct_website' ? 'selected' : ''; ?>>Direct Website</option>
                        <option value="makemytrip" <?php echo $selected_source === 'makemytrip' ? 'selected' : ''; ?>>MakeMyTrip</option>
                        <option value="airbnb" <?php echo $selected_source === 'airbnb' ? 'selected' : ''; ?>>Airbnb</option>
                        <option value="booking_com" <?php echo $selected_source === 'booking_com' ? 'selected' : ''; ?>>Booking.com</option>
                        <option value="offline_direct" <?php echo $selected_source === 'offline_direct' ? 'selected' : ''; ?>>Offline / Phone</option>
                    </select>
                </form>

                <!-- Action Buttons -->
                <div class="adm-cal-action-btns">
                    <!-- Sync Now Trigger -->
                    <a href="channel_sync.php" class="adm-btn-action outline" title="Manage & Sync Feeds" style="padding: 7px 12px; font-size: 12px;">
                        <i class="fa-solid fa-arrows-rotate"></i> <span>OTA Sync</span>
                    </a>

                    <!-- Quick Block Button -->
                    <button type="button" class="adm-btn-action gold" onclick="openQuickBookModal()" style="padding: 7px 14px; font-size: 12px;">
                        <i class="fa-solid fa-plus"></i> <span>Block / Reserve</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar Legend Bar (Touch-scrollable on mobile) -->
    <div class="adm-cal-legend-bar">
        <span class="adm-cal-legend-title"><i class="fa-solid fa-circle-info"></i> Channels:</span>
        <span class="adm-cal-legend-item"><span class="adm-cal-dot" style="background: #C5A059;"></span> Direct Website</span>
        <span class="adm-cal-legend-item"><span class="adm-cal-dot" style="background: #e74c3c;"></span> MakeMyTrip</span>
        <span class="adm-cal-legend-item"><span class="adm-cal-dot" style="background: #FF5A5F;"></span> Airbnb</span>
        <span class="adm-cal-legend-item"><span class="adm-cal-dot" style="background: #003580;"></span> Booking.com</span>
        <span class="adm-cal-legend-item"><span class="adm-cal-dot" style="background: #27ae60;"></span> Offline / Walk-in</span>
    </div>

    <!-- Monthly Calendar Grid Layout (100% Fit Responsive) -->
    <div class="adm-cal-wrapper" id="cal-view-grid-container">
        <div class="adm-card adm-cal-card">
            
            <!-- Days Header (Sun - Sat) -->
            <div class="adm-cal-header-row">
                <div>SUN</div>
                <div>MON</div>
                <div>TUE</div>
                <div>WED</div>
                <div>THU</div>
                <div>FRI</div>
                <div>SAT</div>
            </div>

            <!-- Days Grid -->
            <div class="adm-cal-grid">
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
                         onclick="handleDayClick('<?php echo $d_str; ?>')">
                        
                        <!-- Date Number & Header -->
                        <div class="cal-cell-header">
                            <span class="cal-day-num <?php echo $is_today ? 'is-today' : ''; ?>">
                                <?php echo $d_num; ?>
                            </span>
                            
                            <?php if (count($day_bookings) > 0): ?>
                                <span class="cal-stay-count" title="<?php echo count($day_bookings); ?> Stays">
                                    <?php echo count($day_bookings); ?> Stay<?php echo count($day_bookings) > 1 ? 's' : ''; ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Bookings Pills -->
                        <div class="cal-bookings-list">
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
                                     style="background: <?php echo $pill_bg; ?>; color: <?php echo $pill_color; ?>;"
                                     title="<?php echo htmlspecialchars($b['guest_name'] . ' (' . ($b['room_title'] ?: $b['villa_type']) . ') - ' . $b['checkin_date'] . ' to ' . $b['checkout_date']); ?>">
                                    <i class="<?php echo $badge_icon; ?>"></i>
                                    <span><?php echo htmlspecialchars($b['guest_name']); ?></span>
                                </div>
                            <?php endforeach; ?>

                            <?php if (count($day_bookings) > $max_show): ?>
                                <div class="cal-more-badge">
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

    <!-- Month Agenda / Chronological Stays List View -->
    <div class="adm-cal-agenda-wrapper" id="cal-view-agenda-container" style="display: none; margin-bottom: 24px;">
        <div class="adm-card" style="padding: 16px 20px; border-radius: 12px; border: 1px solid rgba(197, 160, 89, 0.25);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid rgba(197,160,89,0.2); padding-bottom: 10px;">
                <h3 style="font-family: var(--adm-font-title); font-size: 16px; color: #FFFFFF; margin: 0;">
                    <i class="fa-solid fa-list-check" style="color: var(--adm-gold);"></i> 
                    <?php echo $month_name . ' ' . $curr_year; ?> Reservations (<?php echo count($bookings); ?>)
                </h3>
                <span style="font-size: 11.5px; color: var(--adm-gold-light);">Chronological Stays</span>
            </div>

            <?php if (empty($bookings)): ?>
                <div style="text-align: center; padding: 36px 16px; color: #94a3b8;">
                    <i class="fa-regular fa-calendar-xmark" style="font-size: 36px; color: var(--adm-gold); margin-bottom: 12px; display: block; opacity: 0.6;"></i>
                    <p style="font-size: 14px; margin: 0;">No active bookings found for <?php echo $month_name . ' ' . $curr_year; ?>.</p>
                </div>
            <?php else: ?>
                <div class="adm-cal-agenda-list" style="display: flex; flex-direction: column; gap: 10px;">
                    <?php foreach ($bookings as $b): 
                        $src = strtolower(trim($b['booking_source'] ?: 'direct_website'));
                        $pill_bg = '#C5A059';
                        $src_label = 'Direct Website';
                        if (stripos($src, 'make') !== false || stripos($src, 'mmt') !== false) {
                            $pill_bg = '#e74c3c';
                            $src_label = 'MakeMyTrip';
                        } elseif (stripos($src, 'air') !== false) {
                            $pill_bg = '#FF5A5F';
                            $src_label = 'Airbnb';
                        } elseif (stripos($src, 'booking') !== false) {
                            $pill_bg = '#003580';
                            $src_label = 'Booking.com';
                        } elseif ($src === 'offline_direct') {
                            $pill_bg = '#27ae60';
                            $src_label = 'Offline / Phone';
                        }
                    ?>
                        <div class="adm-cal-agenda-card" 
                             onclick="openBookingDetailModal(<?php echo htmlspecialchars(json_encode($b)); ?>)"
                             style="background: #0e1f16; border: 1px solid rgba(197, 160, 89, 0.2); border-left: 4px solid <?php echo $pill_bg; ?>; border-radius: 8px; padding: 12px 14px; cursor: pointer; transition: all 0.2s ease;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px; flex-wrap: wrap; gap: 6px;">
                                <div>
                                    <strong style="color: #FFFFFF; font-size: 14px; display: block;">
                                        <?php echo htmlspecialchars($b['guest_name']); ?>
                                    </strong>
                                    <span style="font-size: 11.5px; color: var(--adm-gold-light); font-weight: 600;">
                                        <i class="fa-solid fa-hotel"></i> <?php echo htmlspecialchars($b['room_title'] ?: $b['villa_type']); ?>
                                    </span>
                                </div>
                                <span style="background: <?php echo $pill_bg; ?>; color: #FFFFFF; font-size: 10.5px; font-weight: 700; padding: 2px 8px; border-radius: 4px; text-transform: uppercase;">
                                    <?php echo $src_label; ?>
                                </span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #cbd5e1; flex-wrap: wrap; gap: 8px;">
                                <span>
                                    <i class="fa-regular fa-calendar"></i> 
                                    <strong><?php echo date('d M', strtotime($b['checkin_date'])); ?></strong> → 
                                    <strong><?php echo date('d M', strtotime($b['checkout_date'])); ?></strong> 
                                    (<?php echo $b['nights']; ?> nt<?php echo $b['nights'] > 1 ? 's' : ''; ?>)
                                </span>
                                <span style="font-size: 11px; color: #94a3b8;">
                                    <i class="fa-solid fa-users"></i> <?php echo $b['guests_count']; ?> Guests • 
                                    <span style="color: var(--adm-gold); font-weight: 700;">₹<?php echo number_format($b['total_amount'], 0); ?></span>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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

            <!-- Quick Stage & Revert Controls inside Calendar Modal -->
            <div style="background: rgba(0,0,0,0.2); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 8px; padding: 10px; margin-top: 4px;">
                <div style="font-size: 11px; text-transform: uppercase; color: var(--adm-gold-light); font-weight: 700; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between;">
                    <span><i class="fa-solid fa-arrows-rotate"></i> Change / Revert Status</span>
                    <span id="vb-status-badge" style="text-transform: capitalize; padding: 2px 8px; border-radius: 4px; font-size: 10.5px; background: rgba(197, 160, 89, 0.2); color: var(--adm-gold);">Status</span>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(75px, 1fr)); gap: 6px;">
                    <button type="button" class="adm-btn-action outline" style="padding: 7px 5px; font-size: 11px; justify-content: center; color: #cbd5e1; border-color: rgba(148, 163, 184, 0.4);" onclick="submitCalendarRevert('pending', 'Pending Review')" title="Revert to Pending Review">
                        <i class="fa-solid fa-clock-rotate-left"></i> To Pending
                    </button>
                    <button type="button" class="adm-btn-action emerald" style="padding: 7px 5px; font-size: 11px; justify-content: center;" onclick="submitCalendarRevert('confirmed', 'Confirmed Upcoming')" title="Confirm or Revert to Confirmed">
                        <i class="fa-solid fa-circle-check"></i> Confirm
                    </button>
                    <button type="button" class="adm-btn-action cyan" style="padding: 7px 5px; font-size: 11px; justify-content: center; background: rgba(6, 182, 212, 0.18); border: 1px solid rgba(6, 182, 212, 0.4); color: #22d3ee;" onclick="submitCalendarRevert('inhouse', 'In-House (Checked-In)')" title="Check-In / Revert to In-House">
                        <i class="fa-solid fa-hotel"></i> Check-In
                    </button>
                    <button type="button" class="adm-btn-action purple" style="padding: 7px 5px; font-size: 11px; justify-content: center; background: rgba(168, 85, 247, 0.18); border: 1px solid rgba(168, 85, 247, 0.4); color: #c084fc;" onclick="submitCalendarRevert('completed', 'Checked-Out & Completed')" title="Check-Out / Mark Completed">
                        <i class="fa-solid fa-door-open"></i> Check-Out
                    </button>
                    <button type="button" class="adm-btn-action amber" style="padding: 7px 5px; font-size: 11px; justify-content: center; background: rgba(245, 158, 11, 0.18); border: 1px solid rgba(245, 158, 11, 0.4); color: #fbbf24;" onclick="submitCalendarRevert('waitlist', 'Waiting List')" title="Move to Waitlist">
                        <i class="fa-solid fa-user-clock"></i> Waitlist
                    </button>
                    <button type="button" class="adm-btn-action danger" style="padding: 7px 5px; font-size: 11px; justify-content: center;" onclick="submitCalendarRevert('cancelled', 'Cancelled')" title="Cancel Reservation">
                        <i class="fa-solid fa-ban"></i> Cancel
                    </button>
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; gap: 10px;">
            <div style="display: flex; gap: 8px;">
                <button type="button" class="adm-btn-action purple" onclick="openCalendarCheckoutAudit();" style="font-size: 11.5px; padding: 7px 12px; background: rgba(168, 85, 247, 0.18); border: 1px solid rgba(168, 85, 247, 0.4); color: #c084fc;" title="Audit Room, Food & Experiences">
                    <i class="fa-solid fa-list-check"></i> Stay Audit
                </button>
                <a href="#" id="vb-wa-btn" target="_blank" class="adm-btn-action" style="background: #25D366; color: #072814; font-weight: 700; font-size: 11.5px; padding: 7px 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-brands fa-whatsapp"></i> WhatsApp
                </a>
            </div>
            <button type="button" class="adm-btn-action outline" onclick="closeModal('modal-view-booking')" style="padding: 7px 14px; font-size: 11.5px;">Close</button>
        </div>

        <!-- Hidden form for calendar revert -->
        <form method="POST" id="form-calendar-revert" action="calendar.php?month=<?php echo $curr_month; ?>&year=<?php echo $curr_year; ?>" style="display:none;">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="revert_status">
            <input type="hidden" name="booking_id" id="cal-revert-id" value="">
            <input type="hidden" name="target_status" id="cal-revert-status" value="">
        </form>
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

    const statusMap = {
        'inhouse': { label: 'In-House (Checked-In)', color: '#22d3ee', bg: 'rgba(6, 182, 212, 0.2)' },
        'confirmed': { label: 'Confirmed (Upcoming)', color: '#34d399', bg: 'rgba(16, 185, 129, 0.2)' },
        'completed': { label: 'Checked-Out & Completed', color: '#c084fc', bg: 'rgba(168, 85, 247, 0.2)' },
        'waitlist': { label: 'Waiting List', color: '#fbbf24', bg: 'rgba(245, 158, 11, 0.2)' },
        'cancelled': { label: 'Cancelled', color: '#f87171', bg: 'rgba(239, 68, 68, 0.2)' },
        'pending': { label: 'Pending Review', color: '#cbd5e1', bg: 'rgba(148, 163, 184, 0.2)' }
    };
    const stKey = (b.status || 'confirmed').toLowerCase();
    const stInfo = statusMap[stKey] || { label: b.status, color: '#e2e8f0', bg: 'rgba(255,255,255,0.1)' };
    const stBadge = document.getElementById('vb-status-badge');
    if (stBadge) {
        stBadge.innerText = stInfo.label;
        stBadge.style.color = stInfo.color;
        stBadge.style.backgroundColor = stInfo.bg;
        stBadge.style.border = '1px solid ' + stInfo.color;
    }

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

function openCalendarCheckoutAudit() {
    const bId = document.getElementById('vb-booking-id').value;
    closeModal('modal-view-booking');
    if (typeof openCheckoutAuditModal === 'function') {
        openCheckoutAuditModal(bId);
    }
}

function submitCalendarRevert(targetStatus, label) {
    if (targetStatus === 'completed') {
        openCalendarCheckoutAudit();
        return;
    }
    const bId = document.getElementById('vb-booking-id').value;
    const name = document.getElementById('vb-title').innerText;
    if (confirm('Change / Revert status for "' + name + '" to ' + label + '?')) {
        document.getElementById('cal-revert-id').value = bId;
        document.getElementById('cal-revert-status').value = targetStatus;
        document.getElementById('form-calendar-revert').submit();
    }
}

function closeModal(modalId) {
    const m = document.getElementById(modalId);
    if (m) m.style.display = 'none';
}

function toggleCalendarKpiStrip(forceState) {
    var wrapper = document.getElementById('adm-cal-kpi-row-wrapper');
    var summary = document.getElementById('adm-cal-kpi-collapsed-summary');
    var label = document.getElementById('adm-cal-kpi-toggle-label');
    var icon = document.getElementById('adm-cal-kpi-toggle-icon');
    var btn = document.getElementById('btn-toggle-cal-kpi');
    if (!wrapper) return;

    var isCollapsing;
    if (typeof forceState !== 'undefined') {
        isCollapsing = !forceState;
    } else {
        isCollapsing = (wrapper.style.display !== 'none');
    }

    if (isCollapsing) {
        wrapper.style.display = 'none';
        if (summary) summary.style.display = 'flex';
        if (label) label.textContent = 'Expand';
        if (icon) icon.className = 'fa-solid fa-chevron-down';
        if (btn) btn.setAttribute('aria-expanded', 'false');
        try { localStorage.setItem('ff_cal_kpi_state', 'collapsed'); } catch(e){}
    } else {
        wrapper.style.display = 'block';
        if (summary) summary.style.display = 'none';
        if (label) label.textContent = 'Collapse';
        if (icon) icon.className = 'fa-solid fa-chevron-up';
        if (btn) btn.setAttribute('aria-expanded', 'true');
        try { localStorage.setItem('ff_cal_kpi_state', 'expanded'); } catch(e){}
    }
}

function switchCalendarView(viewMode) {
    var gridWrap = document.getElementById('cal-view-grid-container');
    var agendaWrap = document.getElementById('cal-view-agenda-container');
    var btnGrid = document.getElementById('btn-view-grid');
    var btnAgenda = document.getElementById('btn-view-agenda');

    if (viewMode === 'agenda' || viewMode === 'list') {
        if (gridWrap) gridWrap.style.display = 'none';
        if (agendaWrap) agendaWrap.style.display = 'block';
        if (btnGrid) btnGrid.classList.remove('active');
        if (btnAgenda) btnAgenda.classList.add('active');
        try { localStorage.setItem('ff_cal_view_mode', 'agenda'); } catch(e){}
    } else {
        if (gridWrap) gridWrap.style.display = 'block';
        if (agendaWrap) agendaWrap.style.display = 'none';
        if (btnGrid) btnGrid.classList.add('active');
        if (btnAgenda) btnAgenda.classList.remove('active');
        try { localStorage.setItem('ff_cal_view_mode', 'grid'); } catch(e){}
    }
}

// Restore saved preferences on load
document.addEventListener('DOMContentLoaded', function() {
    try {
        var kState = localStorage.getItem('ff_cal_kpi_state');
        if (kState === 'collapsed') {
            toggleCalendarKpiStrip(false);
        }

        var vMode = localStorage.getItem('ff_cal_view_mode');
        if (vMode === 'agenda') {
            switchCalendarView('agenda');
        }
    } catch(e){}
});
</script>

<?php require_once __DIR__ . '/includes/checkout_audit_modal.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
