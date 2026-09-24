<?php
// =========================================================================
// Food Forest Sanctuary — Reservations & Bookings Manager
// =========================================================================
$page_title = 'Guest Reservations';
$page_subtitle = 'Manage bespoke stays, concierge confirmations & arrivals';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db();

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $all_bookings = $pdo->query("SELECT * FROM bookings ORDER BY checkin_date DESC")->fetchAll();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=FoodForest_Bookings_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Ref ID', 'Guest Name', 'Phone', 'Email', 'Villa', 'Guests', 'Check-In', 'Check-Out', 'Nights', 'Total Amount', 'Status', 'Booked On']);
    foreach ($all_bookings as $row) {
        fputcsv($output, [
            $row['reference_code'],
            $row['guest_name'],
            $row['guest_phone'],
            $row['guest_email'],
            $row['villa_type'],
            $row['guests_count'],
            $row['checkin_date'],
            $row['checkout_date'],
            $row['nights'],
            $row['total_amount'],
            $row['status'],
            $row['created_at']
        ]);
    }
    fclose($output);
    exit;
}

$alert_message = '';
$alert_type = 'success';

// Handle Actions (Add, Update Status, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $alert_message = 'Security validation failed. Please try again.';
        $alert_type = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        // Update Status
        if ($action === 'update_status') {
            $b_id = (int)$_POST['booking_id'];
            $new_status = $_POST['status'];
            $custom_cin = !empty($_POST['checked_in_at']) ? $_POST['checked_in_at'] : null;
            $custom_cout = !empty($_POST['checked_out_at']) ? $_POST['checked_out_at'] : null;

            if (in_array($new_status, ['pending', 'confirmed', 'inhouse', 'waitlist', 'completed', 'cancelled', 'rejected'])) {
                if ($new_status === 'inhouse') {
                    $stmt = $pdo->prepare("UPDATE bookings SET status = ?, checked_in_at = COALESCE(?, checked_in_at, NOW()), checked_out_at = COALESCE(?, checked_out_at) WHERE id = ?");
                    $stmt->execute([$new_status, $custom_cin, $custom_cout, $b_id]);
                } elseif ($new_status === 'completed') {
                    $stmt = $pdo->prepare("UPDATE bookings SET status = ?, checked_out_at = COALESCE(?, checked_out_at, NOW()), checked_in_at = COALESCE(?, checked_in_at) WHERE id = ?");
                    $stmt->execute([$new_status, $custom_cout, $custom_cin, $b_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE bookings SET status = ?, checked_in_at = COALESCE(?, checked_in_at), checked_out_at = COALESCE(?, checked_out_at) WHERE id = ?");
                    $stmt->execute([$new_status, $custom_cin, $custom_cout, $b_id]);
                }
                $status_names = [
                    'confirmed' => 'Approved & Confirmed',
                    'inhouse' => 'Checked-In (In-House)',
                    'waitlist' => 'Added to Waiting List',
                    'cancelled' => 'Cancelled / Rejected',
                    'rejected' => 'Cancelled / Rejected',
                    'pending' => 'Pending Review',
                    'completed' => 'Checked-Out & Completed'
                ];
                $alert_message = 'Reservation status successfully updated to ' . ($status_names[$new_status] ?? ucfirst($new_status)) . '.';
            }
        }

        // Delete Booking
        if ($action === 'delete_booking') {
            $b_id = (int)$_POST['booking_id'];
            $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
            $stmt->execute([$b_id]);
            $alert_message = 'Reservation record has been permanently removed.';
        }

        // Add Manual Booking
        if ($action === 'add_manual_booking') {
            $guest_name = trim($_POST['guest_name'] ?? '');
            $guest_phone = trim($_POST['guest_phone'] ?? '');
            $guest_email = trim($_POST['guest_email'] ?? '');
            $villa_type = $_POST['villa_type'] ?? 'treehouse';
            
            $adults_count = isset($_POST['adults_count']) ? max(1, (int)$_POST['adults_count']) : (int)($_POST['guests_count'] ?? 2);
            $kids_count = isset($_POST['kids_count']) ? max(0, (int)$_POST['kids_count']) : 0;
            $guests_count = $adults_count + $kids_count;

            $checkin = $_POST['checkin_date'] ?? '';
            $checkout = $_POST['checkout_date'] ?? '';
            $special_notes = trim($_POST['special_notes'] ?? '');
            $addons = trim($_POST['addons'] ?? '');
            $status = $_POST['status'] ?? 'confirmed';

            if (!empty($guest_name) && !empty($guest_phone) && !empty($checkin) && !empty($checkout)) {
                $cin = new DateTime($checkin);
                $cout = new DateTime($checkout);
                $nights = max(1, $cin->diff($cout)->days);

                // Fetch room rate and guest occupancy specs
                $room_stmt = $pdo->prepare("SELECT rate_per_night, base_guests, extra_guest_rate, extra_child_rate FROM rooms WHERE slug = ?");
                $room_stmt->execute([$villa_type]);
                $r_data = $room_stmt->fetch(PDO::FETCH_ASSOC);
                $rate = $r_data ? (float)$r_data['rate_per_night'] : 14500;
                $base_guests = $r_data ? (int)($r_data['base_guests'] ?? 2) : 2;
                $extra_adult_rate = $r_data ? (float)($r_data['extra_guest_rate'] ?? 1500) : 1500;
                $extra_child_rate = $r_data ? (float)($r_data['extra_child_rate'] ?? 800) : 800;

                // Dual Occupancy math
                $adults_in_base = min($adults_count, $base_guests);
                $extra_adults = max(0, $adults_count - $adults_in_base);
                $rem_base = max(0, $base_guests - $adults_in_base);
                $kids_in_base = min($kids_count, $rem_base);
                $extra_kids = max(0, $kids_count - $kids_in_base);

                $extra_amount = ($extra_adults * $extra_adult_rate * $nights) + ($extra_kids * $extra_child_rate * $nights);
                $calculated_total = ($rate * $nights) + $extra_amount;

                $id_proof_type = trim($_POST['id_proof_type'] ?? 'Aadhaar Card');
                $id_proof_number = trim($_POST['id_proof_number'] ?? '');
                $city_state = trim($_POST['city_state'] ?? '');

                // Handle ID proof upload if provided
                $id_proof_file = null;
                if (!empty($_FILES['id_proof_file']) && $_FILES['id_proof_file']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = __DIR__ . '/../uploads/id_proofs/';
                    if (!is_dir($upload_dir)) {
                        @mkdir($upload_dir, 0777, true);
                    }
                    $ext = strtolower(pathinfo($_FILES['id_proof_file']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'pdf'])) {
                        $id_proof_file = 'id_adm_' . time() . '_' . rand(100, 999) . '.' . $ext;
                        move_uploaded_file($_FILES['id_proof_file']['tmp_name'], $upload_dir . $id_proof_file);
                    }
                }

                // Generate Reference Code
                $ref = 'FF-' . rand(1000, 9999);

                $ins = $pdo->prepare("INSERT INTO bookings (reference_code, villa_type, guest_name, guest_phone, guest_email, id_proof_type, id_proof_number, id_proof_file, city_state, guests_count, adults_count, kids_count, extra_adults, extra_kids, checkin_date, checkout_date, nights, addons, special_notes, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $ins->execute([$ref, $villa_type, $guest_name, $guest_phone, $guest_email, $id_proof_type, $id_proof_number, $id_proof_file, $city_state, $guests_count, $adults_count, $kids_count, $extra_adults, $extra_kids, $checkin, $checkout, $nights, $addons, $special_notes, $custom_amount, $status]);

                $alert_message = "New reservation #$ref recorded successfully.";
            } else {
                $alert_message = 'Please fill in all mandatory guest and stay date details.';
                $alert_type = 'error';
            }
        }
    }
}

// Search and Filter logic
$search = trim($_GET['search'] ?? '');
$active_tab = trim($_GET['tab'] ?? ($_GET['status'] ?? ($_GET['filter'] ?? 'all')));
if (empty($active_tab)) $active_tab = 'all';
$filter_villa = trim($_GET['villa'] ?? '');
$filter_source = trim($_GET['source'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');
$sort_by = trim($_GET['sort'] ?? 'checkin_desc');

// Live badge counts from DB
$count_all = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$count_pending = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$count_confirmed = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed' AND checkin_date > CURDATE()")->fetchColumn();
$count_waitlist = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE (status = 'waitlist') OR (status = 'confirmed' AND checkin_date <= CURDATE() AND checkout_date >= CURDATE())")->fetchColumn();
$count_inhouse = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'inhouse'")->fetchColumn();
$count_completed = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'completed' AND (checked_out_at >= NOW() - INTERVAL 24 HOUR OR (checked_out_at IS NULL AND checkout_date = CURDATE()))")->fetchColumn();
$count_former = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'completed' AND (checked_out_at < NOW() - INTERVAL 24 HOUR OR (checked_out_at IS NULL AND checkout_date < CURDATE()))")->fetchColumn();
$count_cancelled = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'cancelled' OR status = 'rejected'")->fetchColumn();

$query = "SELECT * FROM bookings WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (guest_name LIKE ? OR guest_phone LIKE ? OR reference_code LIKE ? OR guest_email LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$all_rooms_list = $pdo->query("SELECT * FROM rooms ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$rooms_lookup = [];
foreach ($all_rooms_list as $r) {
    $rooms_lookup[$r['slug']] = $r;
}

if ($active_tab === 'pending') {
    $query .= " AND status = 'pending'";
} elseif ($active_tab === 'confirmed') {
    $query .= " AND status = 'confirmed' AND checkin_date > CURDATE()";
} elseif ($active_tab === 'waitlist') {
    $query .= " AND ((status = 'waitlist') OR (status = 'confirmed' AND checkin_date <= CURDATE() AND checkout_date >= CURDATE()))";
} elseif ($active_tab === 'inhouse') {
    $query .= " AND status = 'inhouse'";
} elseif ($active_tab === 'completed') {
    $query .= " AND (status = 'completed' AND (checked_out_at >= NOW() - INTERVAL 24 HOUR OR (checked_out_at IS NULL AND checkout_date = CURDATE())))";
} elseif ($active_tab === 'former') {
    $query .= " AND (status = 'completed' AND (checked_out_at < NOW() - INTERVAL 24 HOUR OR (checked_out_at IS NULL AND checkout_date < CURDATE())))";
} elseif ($active_tab === 'cancelled') {
    $query .= " AND (status = 'cancelled' OR status = 'rejected')";
}

if (!empty($filter_villa)) {
    $query .= " AND villa_type = ?";
    $params[] = $filter_villa;
}

if (!empty($filter_source)) {
    $query .= " AND booking_source = ?";
    $params[] = $filter_source;
}

if (!empty($date_from)) {
    $query .= " AND checkin_date >= ?";
    $params[] = $date_from;
}
if (!empty($date_to)) {
    $query .= " AND checkin_date <= ?";
    $params[] = $date_to;
}

// Sorting logic
if ($sort_by === 'checkout_desc') {
    $query .= " ORDER BY checkout_date DESC, id DESC";
} elseif ($sort_by === 'checkout_asc') {
    $query .= " ORDER BY checkout_date ASC, id ASC";
} elseif ($sort_by === 'checkin_asc') {
    $query .= " ORDER BY checkin_date ASC, id ASC";
} elseif ($sort_by === 'amount_desc') {
    $query .= " ORDER BY total_amount DESC, id DESC";
} elseif ($sort_by === 'name_asc') {
    $query .= " ORDER BY guest_name ASC";
} else {
    $query .= " ORDER BY checkin_date DESC, id DESC";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$tab_title_map = [
    'all' => 'All Reservations',
    'pending' => 'Pending Concierge Approvals',
    'confirmed' => 'Confirmed Upcoming Stays',
    'waitlist' => 'Waiting List / Expected Arrivals Today',
    'inhouse' => 'Currently In-House (Active Stays)',
    'completed' => 'Recent Check-Outs (Last 24 Hours)',
    'former' => 'Former Guests & Stay History',
    'cancelled' => 'Cancelled / Rejected Bookings'
];
$current_heading = $tab_title_map[$active_tab] ?? 'All Reservations';

// Build query string helper for links
function build_tab_url($tab_name, $current_params = []) {
    $params = array_merge($_GET, ['tab' => $tab_name]);
    return 'bookings.php?' . http_build_query($params);
}
?>

<?php if (!empty($alert_message)): ?>
    <div class="adm-alert adm-alert-<?php echo $alert_type; ?>" style="margin-bottom: 20px;">
        <i class="fa-solid <?php echo $alert_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
        <span><?php echo e($alert_message); ?></span>
    </div>
<?php endif; ?>

<!-- Multi-Status Reservations Tabs -->
<div class="adm-reservation-tabs">
    <a href="<?php echo build_tab_url('all'); ?>" class="adm-res-tab-item <?php echo ($active_tab === 'all') ? 'is-active' : ''; ?>">
        <i class="fa-solid fa-list-check"></i>
        <span>All</span>
        <span class="adm-res-tab-pill gold"><?php echo $count_all; ?></span>
    </a>
    <a href="<?php echo build_tab_url('pending'); ?>" class="adm-res-tab-item <?php echo ($active_tab === 'pending') ? 'is-active' : ''; ?>" title="New online bookings needing approval">
        <i class="fa-solid fa-hourglass-half" style="color: #f59e0b;"></i>
        <span>Pending</span>
        <span class="adm-res-tab-pill amber"><?php echo $count_pending; ?></span>
    </a>
    <a href="<?php echo build_tab_url('confirmed'); ?>" class="adm-res-tab-item <?php echo ($active_tab === 'confirmed') ? 'is-active' : ''; ?>" title="Approved upcoming stays">
        <i class="fa-solid fa-circle-check" style="color: #2ecc71;"></i>
        <span>Confirmed</span>
        <span class="adm-res-tab-pill emerald"><?php echo $count_confirmed; ?></span>
    </a>
    <a href="<?php echo build_tab_url('waitlist'); ?>" class="adm-res-tab-item <?php echo ($active_tab === 'waitlist') ? 'is-active' : ''; ?>" title="Guests expected to arrive / check-in today">
        <i class="fa-solid fa-user-clock" style="color: #38bdf8;"></i>
        <span>Waiting List / Arrivals</span>
        <span class="adm-res-tab-pill cyan"><?php echo $count_waitlist; ?></span>
    </a>
    <a href="<?php echo build_tab_url('inhouse'); ?>" class="adm-res-tab-item <?php echo ($active_tab === 'inhouse') ? 'is-active' : ''; ?>" title="Guests currently staying at the estate">
        <i class="fa-solid fa-hotel" style="color: #06b6d4;"></i>
        <span>In-House</span>
        <span class="adm-res-tab-pill cyan"><?php echo $count_inhouse; ?></span>
    </a>
    <a href="<?php echo build_tab_url('completed'); ?>" class="adm-res-tab-item <?php echo ($active_tab === 'completed') ? 'is-active' : ''; ?>" title="Departed guests checked out in the last 24 hours">
        <i class="fa-solid fa-door-open" style="color: #a855f7;"></i>
        <span>Check-Out (24h)</span>
        <span class="adm-res-tab-pill purple"><?php echo $count_completed; ?></span>
    </a>
    <a href="<?php echo build_tab_url('former'); ?>" class="adm-res-tab-item <?php echo ($active_tab === 'former') ? 'is-active' : ''; ?>" title="Historical record of past completed stays">
        <i class="fa-solid fa-clock-rotate-left" style="color: #94a3b8;"></i>
        <span>Former Guests</span>
        <span class="adm-res-tab-pill slate"><?php echo $count_former; ?></span>
    </a>
    <a href="<?php echo build_tab_url('cancelled'); ?>" class="adm-res-tab-item <?php echo ($active_tab === 'cancelled') ? 'is-active' : ''; ?>">
        <i class="fa-solid fa-ban" style="color: #f43f5e;"></i>
        <span>Cancelled</span>
        <span class="adm-res-tab-pill rose"><?php echo $count_cancelled; ?></span>
    </a>
</div>

<!-- Filtering & Action Toolbar -->
<div class="adm-toolbar-card" style="flex-wrap: wrap; gap: 12px;">
    <div class="adm-toolbar-left" style="flex-wrap: wrap; gap: 10px;">
        <!-- Live Search Box -->
        <form method="GET" class="adm-search-box" style="display:flex;">
            <input type="text" name="search" id="adm-table-search" class="adm-search-input" placeholder="Search by name, phone, ref #..." value="<?php echo e($search); ?>">
            <i class="fa-solid fa-magnifying-glass adm-search-icon"></i>
            <?php if (!empty($active_tab) && $active_tab !== 'all'): ?>
                <input type="hidden" name="tab" value="<?php echo e($active_tab); ?>">
            <?php endif; ?>
            <?php if (!empty($filter_villa)): ?>
                <input type="hidden" name="villa" value="<?php echo e($filter_villa); ?>">
            <?php endif; ?>
            <?php if (!empty($sort_by)): ?>
                <input type="hidden" name="sort" value="<?php echo e($sort_by); ?>">
            <?php endif; ?>
            <?php if (!empty($date_from)): ?>
                <input type="hidden" name="date_from" value="<?php echo e($date_from); ?>">
            <?php endif; ?>
            <?php if (!empty($date_to)): ?>
                <input type="hidden" name="date_to" value="<?php echo e($date_to); ?>">
            <?php endif; ?>
        </form>

        <!-- Villa Filter Select -->
        <select class="adm-filter-select" onchange="let url = new URL(window.location.href); url.searchParams.set('villa', this.value); window.location.href = url.toString();">
            <option value="" <?php echo empty($filter_villa) ? 'selected' : ''; ?>>All Sanctuary Stays</option>
            <?php foreach ($all_rooms_list as $r): ?>
                <option value="<?php echo htmlspecialchars($r['slug']); ?>" <?php echo ($filter_villa === $r['slug']) ? 'selected' : ''; ?>>
                    <?php echo ($r['stay_type'] === 'mudhouse' ? '🌿 ' : '🌲 ') . htmlspecialchars($r['title']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Channel Source Filter Select -->
        <select class="adm-filter-select" onchange="let url = new URL(window.location.href); url.searchParams.set('source', this.value); window.location.href = url.toString();">
            <option value="" <?php echo empty($filter_source) ? 'selected' : ''; ?>>All Booking Sources</option>
            <option value="direct_website" <?php echo ($filter_source === 'direct_website') ? 'selected' : ''; ?>>🟡 Direct Website</option>
            <option value="MakeMyTrip" <?php echo (stripos($filter_source, 'make') !== false) ? 'selected' : ''; ?>>🔴 MakeMyTrip (InGoMMT)</option>
            <option value="Airbnb" <?php echo (stripos($filter_source, 'air') !== false) ? 'selected' : ''; ?>>🌺 Airbnb</option>
            <option value="Booking.com" <?php echo (stripos($filter_source, 'booking') !== false) ? 'selected' : ''; ?>>🔵 Booking.com</option>
            <option value="offline_direct" <?php echo ($filter_source === 'offline_direct') ? 'selected' : ''; ?>>🟢 Offline / Phone</option>
        </select>

        <!-- Sort By Dropdown -->
        <select class="adm-filter-select" onchange="let url = new URL(window.location.href); url.searchParams.set('sort', this.value); window.location.href = url.toString();" title="Sort Reservations">
            <option value="checkin_desc" <?php echo ($sort_by === 'checkin_desc') ? 'selected' : ''; ?>>Check-In Date (Newest first)</option>
            <option value="checkin_asc" <?php echo ($sort_by === 'checkin_asc') ? 'selected' : ''; ?>>Check-In Date (Oldest first)</option>
            <option value="checkout_desc" <?php echo ($sort_by === 'checkout_desc') ? 'selected' : ''; ?>>Check-Out Date (Newest first)</option>
            <option value="checkout_asc" <?php echo ($sort_by === 'checkout_asc') ? 'selected' : ''; ?>>Check-Out Date (Oldest first)</option>
            <option value="amount_desc" <?php echo ($sort_by === 'amount_desc') ? 'selected' : ''; ?>>Tariff Amount (High to Low)</option>
            <option value="name_asc" <?php echo ($sort_by === 'name_asc') ? 'selected' : ''; ?>>Guest Name (A → Z)</option>
        </select>

        <!-- Date Range Filter Form -->
        <form method="GET" style="display: inline-flex; align-items: center; gap: 6px;">
            <input type="hidden" name="tab" value="<?php echo e($active_tab); ?>">
            <?php if (!empty($search)): ?><input type="hidden" name="search" value="<?php echo e($search); ?>"><?php endif; ?>
            <?php if (!empty($filter_villa)): ?><input type="hidden" name="villa" value="<?php echo e($filter_villa); ?>"><?php endif; ?>
            <?php if (!empty($sort_by)): ?><input type="hidden" name="sort" value="<?php echo e($sort_by); ?>"><?php endif; ?>
            <input type="date" name="date_from" class="adm-input" style="padding: 6px 10px; font-size: 12px; width: 125px;" value="<?php echo e($date_from); ?>" title="Stay Date From">
            <span style="font-size: 11px; color: var(--adm-text-muted);">to</span>
            <input type="date" name="date_to" class="adm-input" style="padding: 6px 10px; font-size: 12px; width: 125px;" value="<?php echo e($date_to); ?>" title="Stay Date To">
            <button type="submit" class="adm-btn-action outline" style="padding: 6px 10px; font-size: 11.5px;" title="Filter by date range">
                <i class="fa-solid fa-filter"></i>
            </button>
        </form>
    </div>

    <div class="adm-toolbar-right">
        <!-- Export CSV Button -->
        <a href="bookings.php?export=csv<?php echo !empty($active_tab) ? '&tab='.urlencode($active_tab) : ''; ?>" class="adm-btn-action outline" title="Export Filtered Bookings to CSV">
            <i class="fa-solid fa-file-csv"></i>
            <span>Export CSV</span>
        </a>

        <!-- Add Manual Reservation -->
        <button type="button" class="adm-btn-action gold" onclick="openAdmModal('modal-add-booking');">
            <i class="fa-solid fa-plus"></i>
            <span>Add Reservation</span>
        </button>
    </div>
</div>

<!-- Bookings Table -->
<div class="adm-table-card">
    <div class="adm-table-header">
        <div>
            <h2 class="adm-table-title"><?php echo $current_heading; ?> (<?php echo count($bookings); ?>)</h2>
            <p class="adm-table-subtitle">Showing guest itineraries matching active status & stay filters</p>
        </div>
        <?php if (!empty($search) || ($active_tab !== 'all') || !empty($filter_villa)): ?>
            <a href="bookings.php" class="adm-btn-action outline" style="font-size: 12px; padding: 4px 10px;">
                <i class="fa-solid fa-xmark"></i> Clear Filters
            </a>
        <?php endif; ?>
    </div>

    <div class="adm-table-responsive">
        <table class="adm-data-table">
            <thead>
                <tr>
                    <th style="width: 85px;">Ref #</th>
                    <th>Guest Information</th>
                    <th>Sanctuary Stay</th>
                    <th style="width: 95px;">Guests</th>
                    <th>Stay Dates</th>
                    <th style="width: 105px;">Total (₹)</th>
                    <th class="adm-col-status">Status</th>
                    <th class="adm-col-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 50px 20px; color: var(--adm-text-muted);">
                            <i class="fa-solid fa-calendar-xmark" style="font-size: 32px; margin-bottom: 12px; display: block; color: var(--adm-text-muted);"></i>
                            No reservations found matching your criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bookings as $b): 
                        $room_info = $rooms_lookup[$b['villa_type']] ?? null;
                        $villa_title = $room_info ? $room_info['title'] : (($b['villa_type'] === 'treehouse') ? 'Canopy Treehouse' : 'Earthen Mudhouse');
                        $stay_type = $room_info ? ($room_info['stay_type'] ?? 'treehouse') : 'treehouse';
                        $base_guests = $room_info ? (int)($room_info['base_guests'] ?? 2) : 2;
                        $extra_guests = max(0, (int)$b['guests_count'] - $base_guests);
                    ?>
                        <tr data-status="<?php echo e($b['status']); ?>">
                            <td>
                                <span class="adm-ref-badge" title="Reservation Reference #">
                                    <?php echo e($b['reference_code']); ?>
                                </span>
                                <?php 
                                $src_badge = strtolower($b['booking_source'] ?? 'direct_website');
                                if (stripos($src_badge, 'make') !== false || stripos($src_badge, 'mmt') !== false): ?>
                                    <div style="font-size: 9.5px; font-weight: 700; color: #FFFFFF; background: #e74c3c; padding: 1px 6px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px; margin-top: 4px;">
                                        <i class="fa-solid fa-plane-arrival"></i> MakeMyTrip
                                    </div>
                                <?php elseif (stripos($src_badge, 'air') !== false): ?>
                                    <div style="font-size: 9.5px; font-weight: 700; color: #FFFFFF; background: #FF5A5F; padding: 1px 6px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px; margin-top: 4px;">
                                        <i class="fa-brands fa-airbnb"></i> Airbnb
                                    </div>
                                <?php elseif (stripos($src_badge, 'booking') !== false): ?>
                                    <div style="font-size: 9.5px; font-weight: 700; color: #FFFFFF; background: #003580; padding: 1px 6px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px; margin-top: 4px;">
                                        <i class="fa-solid fa-b"></i> Booking.com
                                    </div>
                                <?php elseif ($src_badge === 'offline_direct'): ?>
                                    <div style="font-size: 9.5px; font-weight: 700; color: #FFFFFF; background: #27ae60; padding: 1px 6px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px; margin-top: 4px;">
                                        <i class="fa-solid fa-phone"></i> Offline Direct
                                    </div>
                                <?php else: ?>
                                    <div style="font-size: 9.5px; font-weight: 700; color: #101F15; background: #C5A059; padding: 1px 6px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px; margin-top: 4px;">
                                        <i class="fa-solid fa-globe"></i> Direct Web
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: var(--adm-text-primary); font-size: 14px; margin-bottom: 3px;">
                                    <?php echo e($b['guest_name']); ?>
                                </div>
                                <div style="font-size: 11.5px; color: var(--adm-text-muted); display: flex; align-items: center; gap: 5px;">
                                    <i class="fa-solid fa-phone" style="font-size: 10px; color: var(--adm-gold);"></i> 
                                    <a href="tel:<?php echo e($b['guest_phone']); ?>" style="color: inherit;" title="Call Guest"><?php echo e($b['guest_phone']); ?></a>
                                </div>
                                <?php if (!empty($b['guest_email'])): ?>
                                    <div style="font-size: 11px; color: var(--adm-text-muted); display: flex; align-items: center; gap: 5px; margin-top: 1px;">
                                        <i class="fa-regular fa-envelope" style="font-size: 10px; color: var(--adm-gold);"></i> 
                                        <a href="mailto:<?php echo e($b['guest_email']); ?>" style="color: inherit;" title="Email Guest"><?php echo e($b['guest_email']); ?></a>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($b['id_proof_file'])): ?>
                                    <div style="margin-top: 5px;">
                                        <a href="../uploads/id_proofs/<?php echo urlencode($b['id_proof_file']); ?>" 
                                           target="_blank" 
                                           download 
                                           class="adm-badge" 
                                           style="background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.35); font-size: 10px; padding: 2px 7px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; text-decoration: none;" 
                                           title="Download ID Document (<?php echo htmlspecialchars($b['id_proof_type'] ?? 'ID'); ?>)">
                                            <i class="fa-solid fa-file-arrow-down"></i> ID Proof
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-size: 13px; font-weight: 600; color: #FFFFFF;">
                                    <i class="fa-solid <?php echo $stay_type === 'mudhouse' ? 'fa-house-chimney' : 'fa-tree'; ?>" style="color: <?php echo $stay_type === 'mudhouse' ? '#fb923c' : '#2ecc71'; ?>; margin-right: 5px;"></i>
                                    <?php echo e($villa_title); ?>
                                </div>
                                <?php if (!empty($b['addons'])): ?>
                                    <div style="font-size: 11px; color: var(--adm-gold-light); margin-top: 3px; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; background: rgba(197, 160, 89, 0.08); border: 1px solid rgba(197, 160, 89, 0.2); padding: 2px 7px; border-radius: 4px;" title="<?php echo e($b['addons']); ?>">
                                        <i class="fa-solid fa-sparkles" style="color: var(--adm-gold);"></i> <?php echo e($b['addons']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-weight: 600; font-size: 13px; color: var(--adm-text-secondary); white-space: nowrap;">
                                    <i class="fa-solid fa-user-group" style="font-size: 10.5px; color: var(--adm-gold); margin-right: 4px;"></i> 
                                    <?php echo e($b['guests_count']); ?> Guests
                                </span>
                                <?php if (!empty($b['adults_count'])): ?>
                                    <div style="font-size: 10.5px; color: var(--adm-text-muted); margin-top: 2px;">
                                        <?php echo (int)$b['adults_count']; ?> Ad<?php echo !empty($b['kids_count']) ? ', ' . (int)$b['kids_count'] . ' Ch' : ''; ?>
                                    </div>
                                <?php endif; ?>
                                <?php 
                                $ext_ad = (int)($b['extra_adults'] ?? 0);
                                $ext_kd = (int)($b['extra_kids'] ?? 0);
                                if ($ext_ad > 0 || $ext_kd > 0): 
                                ?>
                                    <div style="margin-top: 3px; display: flex; gap: 4px; flex-wrap: wrap;">
                                        <?php if ($ext_ad > 0): ?>
                                            <span class="adm-badge" style="background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3); font-size: 9.5px; padding: 1px 5px; border-radius: 4px;">
                                                +<?php echo $ext_ad; ?> Ext Ad
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($ext_kd > 0): ?>
                                            <span class="adm-badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); font-size: 9.5px; padding: 1px 5px; border-radius: 4px;">
                                                +<?php echo $ext_kd; ?> Ext Ch
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif ($extra_guests > 0): ?>
                                    <div style="margin-top: 3px;">
                                        <span class="adm-badge" style="background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.3); font-size: 9.5px; padding: 1px 6px; border-radius: 4px;">
                                            +<?php echo $extra_guests; ?> Extra
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="white-space: nowrap;">
                                <div style="font-size: 13px; font-weight: 600; color: var(--adm-text-primary);">
                                    <i class="fa-regular fa-calendar" style="font-size: 11px; color: var(--adm-gold); margin-right: 4px;"></i> 
                                    <?php echo date('d M Y', strtotime($b['checkin_date'])); ?>
                                </div>
                                <div style="font-size: 11.5px; color: var(--adm-text-muted); margin-top: 2px; display: flex; align-items: center; gap: 6px;">
                                    <span>until <?php echo date('d M Y', strtotime($b['checkout_date'])); ?></span>
                                    <span class="adm-night-pill"><?php echo e($b['nights']); ?>N</span>
                                </div>
                                <?php if (!empty($b['checked_in_at']) || !empty($b['checked_out_at'])): ?>
                                    <div style="margin-top: 4px; display: flex; flex-direction: column; gap: 2px;">
                                        <?php if (!empty($b['checked_in_at'])): ?>
                                            <div style="font-size: 10px; color: #22d3ee; display: flex; align-items: center; gap: 4px;" title="Actual Recorded Check-In Time">
                                                <i class="fa-solid fa-hotel" style="font-size: 9px;"></i>
                                                <span>In: <?php echo date('d M, h:i A', strtotime($b['checked_in_at'])); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($b['checked_out_at'])): ?>
                                            <div style="font-size: 10px; color: #c084fc; display: flex; align-items: center; gap: 4px;" title="Actual Recorded Check-Out Time">
                                                <i class="fa-solid fa-door-open" style="font-size: 9px;"></i>
                                                <span>Out: <?php echo date('d M, h:i A', strtotime($b['checked_out_at'])); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="font-family: var(--adm-font-title); font-weight: 700; color: var(--adm-gold-light); font-size: 15px; white-space: nowrap;">
                                ₹<?php echo number_format($b['total_amount'], 0, '.', ','); ?>
                            </td>
                            <td class="adm-col-status">
                                <?php 
                                $st = strtolower($b['status']);
                                $today = date('Y-m-d');
                                $is_recent_checkout = (!empty($b['checked_out_at']) && (strtotime($b['checked_out_at']) >= time() - 86400)) || (empty($b['checked_out_at']) && $b['checkout_date'] === $today);

                                if ($st === 'inhouse') {
                                    $icon = 'fa-hotel';
                                    $label = 'In-House';
                                    $badge_class = 'inhouse';
                                } elseif ($st === 'confirmed') {
                                    if ($is_inhouse_window) {
                                        $icon = 'fa-user-clock';
                                        $label = 'Arrival Expected';
                                        $badge_class = 'waitlist';
                                    } elseif ($is_past) {
                                        $icon = 'fa-flag-checkered';
                                        $label = 'Past Stay';
                                        $badge_class = 'completed';
                                    } else {
                                        $icon = 'fa-circle-check';
                                        $label = 'Confirmed';
                                        $badge_class = 'confirmed';
                                    }
                                } elseif ($st === 'waitlist') {
                                    $icon = 'fa-user-clock';
                                    $label = 'Arrival Expected';
                                    $badge_class = 'waitlist';
                                } elseif ($st === 'completed') {
                                    $icon = $is_recent_checkout ? 'fa-door-open' : 'fa-clock-rotate-left';
                                    $label = $is_recent_checkout ? 'Checked-Out (24h)' : 'Former Guest';
                                    $badge_class = 'completed';
                                } elseif ($st === 'cancelled' || $st === 'rejected') {
                                    $icon = 'fa-ban';
                                    $label = 'Cancelled';
                                    $badge_class = 'cancelled';
                                } else {
                                    $icon = 'fa-clock';
                                    $label = 'Pending Review';
                                    $badge_class = 'pending';
                                }
                                ?>
                                <span class="adm-badge <?php echo e($badge_class); ?>" title="Status: <?php echo e($label); ?>">
                                    <span class="adm-badge-dot"></span>
                                    <i class="fa-solid <?php echo $icon; ?>" style="font-size: 10px;"></i>
                                    <span><?php echo $label; ?></span>
                                </span>
                            </td>
                            <td class="adm-col-actions">
                                <div class="adm-actions-cell">
                                    <!-- Dynamic Context-Aware Primary Stage Action -->
                                    <?php if ($st === 'pending'): ?>
                                        <form method="POST" style="display:inline; margin:0;" title="Approve & Confirm Reservation">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                            <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                            <input type="hidden" name="status" value="confirmed">
                                            <button type="submit" class="adm-btn-stage approve" title="Approve Reservation">
                                                <i class="fa-solid fa-check"></i>
                                                <span>Approve</span>
                                            </button>
                                        </form>
                                    <?php elseif ($st === 'waitlist' || ($st === 'confirmed' && $b['checkin_date'] <= $today)): ?>
                                        <!-- Check-In Guest from Waiting List / Arrivals -->
                                        <form method="POST" style="display:inline; margin:0;" title="Check-In Guest (Mark In-House with current time)">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                            <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                            <input type="hidden" name="status" value="inhouse">
                                            <button type="submit" class="adm-btn-stage checkin" title="Guest Arrival / Check-In">
                                                <i class="fa-solid fa-hotel"></i>
                                                <span>Check-In</span>
                                            </button>
                                        </form>
                                    <?php elseif ($st === 'inhouse'): ?>
                                        <!-- Check-Out Guest to Recent Departures (24h) -->
                                        <form method="POST" style="display:inline; margin:0;" title="Check-Out Guest (Mark Departed with current time)">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                            <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" class="adm-btn-stage checkout" title="Check-Out Guest">
                                                <i class="fa-solid fa-door-open"></i>
                                                <span>Check-Out</span>
                                            </button>
                                        </form>
                                    <?php elseif ($st === 'confirmed' && !$is_past): ?>
                                        <form method="POST" style="display:inline; margin:0;" title="Early Check-In Guest">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                            <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                            <input type="hidden" name="status" value="inhouse">
                                            <button type="submit" class="adm-btn-stage checkin" title="Guest Arrival / Check-In">
                                                <i class="fa-solid fa-hotel"></i>
                                                <span>Check-In</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <!-- WhatsApp Concierge Direct Action -->
                                    <button type="button" class="adm-btn-icon whatsapp" title="Send WhatsApp Concierge Message"
                                        onclick='openWhatsAppConcierge({
                                            guest_name: <?php echo json_encode($b['guest_name']); ?>,
                                            phone: <?php echo json_encode($b['guest_phone']); ?>,
                                            reference_code: <?php echo json_encode($b['reference_code']); ?>,
                                            villa_title: <?php echo json_encode($villa_title); ?>,
                                            checkin_date: <?php echo json_encode($b['checkin_date']); ?>,
                                            checkout_date: <?php echo json_encode($b['checkout_date']); ?>,
                                            guests_count: <?php echo json_encode($b['guests_count']); ?>,
                                            total_amount: <?php echo json_encode($b['total_amount']); ?>
                                        })'>
                                        <i class="fa-brands fa-whatsapp"></i>
                                    </button>

                                    <!-- Direct Print Bill Action -->
                                    <a href="print_bill.php?ref=<?php echo urlencode($b['reference_code']); ?>" 
                                       target="_blank" 
                                       class="adm-btn-icon" 
                                       style="color: var(--adm-gold); background: rgba(197, 160, 89, 0.15); border: 1px solid rgba(197, 160, 89, 0.3);" 
                                       title="Print Luxury Bill & Guest Folio">
                                        <i class="fa-solid fa-print"></i>
                                    </a>

                                    <!-- View / Manage Modal Trigger -->
                                    <button type="button" class="adm-btn-icon view" title="View Full Details & Manage Booking"
                                        onclick='viewBookingDetails(<?php echo json_encode($b); ?>)'>
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal 1: Add Manual Booking -->
<div class="adm-modal-backdrop" id="modal-add-booking">
    <div class="adm-modal-content">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-calendar-plus" style="color: var(--adm-gold); margin-right: 8px;"></i> Record Reservation</h3>
            <button type="button" class="adm-modal-close" data-close-modal="modal-add-booking">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_manual_booking">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

            <div class="adm-modal-body">
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Guest Full Name *</label>
                        <input type="text" name="guest_name" class="adm-input" placeholder="e.g. Vikram Malhotra" required style="padding-left: 14px;">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">WhatsApp / Phone *</label>
                        <input type="tel" name="guest_phone" class="adm-input" placeholder="+91 98765 43210" required style="padding-left: 14px;">
                    </div>
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Email Address</label>
                        <input type="email" name="guest_email" class="adm-input" placeholder="guest@example.com" style="padding-left: 14px;">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Sanctuary Villa *</label>
                        <select name="villa_type" class="adm-input" style="padding-left: 14px;">
                            <?php foreach ($all_rooms_list as $r): ?>
                                <option value="<?php echo htmlspecialchars($r['slug']); ?>">
                                    <?php echo ($r['stay_type'] === 'mudhouse' ? '🌿 Mudhouse: ' : '🌲 Treehouse: ') . htmlspecialchars($r['title']); ?> (₹<?php echo number_format($r['rate_per_night'], 0, '.', ','); ?>/N • Base <?php echo (int)($r['base_guests'] ?? 2); ?> Guests)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">City / State of Origin</label>
                        <input type="text" name="city_state" class="adm-input" placeholder="e.g. Kochi, Kerala" style="padding-left: 14px;">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Government ID Proof Type</label>
                        <select name="id_proof_type" class="adm-input" style="padding-left: 14px;">
                            <option value="Aadhaar Card">Aadhaar Card (Indian Residents)</option>
                            <option value="Driving License">Driving License</option>
                            <option value="Passport">Passport (International / NRI)</option>
                            <option value="Voter ID">Voter ID Card</option>
                            <option value="PAN Card">PAN Card</option>
                            <option value="Government ID">Other Government Photo ID</option>
                        </select>
                    </div>
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">ID Number / Reference</label>
                        <input type="text" name="id_proof_number" class="adm-input" placeholder="e.g. XXXX-XXXX-1234" style="padding-left: 14px;">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Attach ID Document (JPG, PNG, PDF)</label>
                        <input type="file" name="id_proof_file" accept=".jpg,.jpeg,.png,.webp,.pdf" class="adm-input" style="padding: 7px 10px; font-size: 12px;">
                    </div>
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Check-In Date *</label>
                        <input type="date" name="checkin_date" class="adm-input" required style="padding-left: 14px;" value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Check-Out Date *</label>
                        <input type="date" name="checkout_date" class="adm-input" required style="padding-left: 14px;" value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>">
                    </div>
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Adults (12+ yrs) *</label>
                        <input type="number" name="adults_count" class="adm-input" value="2" min="1" max="10" required style="padding-left: 14px;">
                        <input type="hidden" name="guests_count" value="2">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Children (5–11 yrs)</label>
                        <input type="number" name="kids_count" class="adm-input" value="0" min="0" max="8" style="padding-left: 14px;">
                    </div>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Initial Status</label>
                    <select name="status" class="adm-input" style="padding-left: 14px;">
                        <option value="confirmed">Confirmed</option>
                        <option value="pending">Pending Review</option>
                    </select>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Curated Add-Ons / Notes</label>
                    <input type="text" name="addons" class="adm-input" placeholder="e.g. Candlelight Dinner, Mud Pottery..." style="padding-left: 14px;">
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Guest Preferences / Dietary Requests</label>
                    <textarea name="special_notes" class="adm-input" rows="2" placeholder="e.g. Honeymoon setup, pure vegetarian..." style="padding-left: 14px; resize: vertical;"></textarea>
                </div>
            </div>

            <div class="adm-modal-footer">
                <button type="button" class="adm-btn-action outline" data-close-modal="modal-add-booking">Cancel</button>
                <button type="submit" class="adm-btn-action gold">Record Reservation</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: View / Edit Booking Details -->
<div class="adm-modal-backdrop" id="modal-view-booking">
    <div class="adm-modal-content">
        <div class="adm-modal-header">
            <div>
                <h3 class="adm-modal-title" id="view-modal-title">Reservation Details</h3>
                <span style="font-size: 12px; color: var(--adm-gold);" id="view-modal-ref">REF</span>
            </div>
            <button type="button" class="adm-modal-close" data-close-modal="modal-view-booking">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" id="form-update-status">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="booking_id" id="view-booking-id">

            <div class="adm-modal-body">
                <div style="background: var(--adm-bg-main); border: var(--adm-border-subtle); border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px; border-bottom: var(--adm-border-subtle); padding-bottom: 10px;">
                        <div>
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-muted);">Guest Name</span>
                            <div style="font-size: 16px; font-weight: 700; color: var(--adm-text-primary);" id="view-guest-name">-</div>
                        </div>
                        <div style="text-align: right;">
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-muted);">Total Tariff</span>
                            <div style="font-family: var(--adm-font-title); font-size: 18px; font-weight: 700; color: var(--adm-gold-light);" id="view-total-amount">₹0</div>
                        </div>
                    </div>

                    <div class="adm-grid-2" style="margin-bottom: 10px;">
                        <div>
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-muted);">Contact Phone</span>
                            <div style="color: var(--adm-text-primary);" id="view-guest-phone">-</div>
                        </div>
                        <div>
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-muted);">Email</span>
                            <div style="color: var(--adm-text-primary);" id="view-guest-email">-</div>
                        </div>
                    </div>

                    <div class="adm-grid-2" style="margin-bottom: 10px;">
                        <div>
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-muted);">City / Origin</span>
                            <div style="color: var(--adm-text-primary);" id="view-guest-city">-</div>
                        </div>
                        <div>
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-muted);">Government ID Proof</span>
                            <div style="color: #2ecc71; font-weight: 600;" id="view-guest-id-proof">-</div>
                            <div id="view-guest-id-file-wrap" style="margin-top: 6px; display: none;">
                                <a id="view-id-proof-download-btn" href="#" target="_blank" download class="adm-btn-action" style="background: rgba(34, 197, 94, 0.18); border: 1px solid #22c55e; color: #22c55e; padding: 5px 12px; font-size: 11.5px; text-decoration: none; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; font-weight: 600;">
                                    <i class="fa-solid fa-cloud-arrow-down"></i> Download ID (<span id="view-id-file-ext">DOC</span>)
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="adm-grid-2">
                        <div>
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-muted);">Sanctuary Stay</span>
                            <div style="color: var(--adm-gold-light); font-weight: 600;" id="view-villa-stay">-</div>
                        </div>
                        <div>
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-muted);">Dates & Duration</span>
                            <div style="color: var(--adm-text-primary);" id="view-dates-duration">-</div>
                        </div>
                    </div>

                    <div style="margin-top: 12px; padding-top: 10px; border-top: var(--adm-border-subtle);" id="view-addons-box">
                        <span style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-muted);">Selected Add-on Experiences (Payable On-Site)</span>
                        <div style="color: var(--adm-text-secondary); font-size: 13px;" id="view-addons-text">-</div>
                    </div>

                    <div style="margin-top: 12px; padding-top: 10px; border-top: var(--adm-border-subtle);" id="view-notes-box">
                        <span style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-muted);">Special Requests / Dietary</span>
                        <div style="color: var(--adm-text-secondary); font-size: 13px; font-style: italic;" id="view-notes-text">-</div>
                    </div>

                    <!-- Actual Recorded Check-In & Check-Out Timestamps -->
                    <div style="margin-top: 14px; padding-top: 12px; border-top: var(--adm-border-subtle); background: rgba(0,0,0,0.2); border-radius: 8px; padding: 12px;">
                        <div style="font-size: 11.5px; font-weight: 700; text-transform: uppercase; color: var(--adm-gold-light); margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-clock-rotate-left"></i> Recorded Check-In & Check-Out Timestamps
                        </div>
                        <div class="adm-grid-2">
                            <div class="adm-form-group" style="margin-bottom: 0;">
                                <label class="adm-label" style="font-size: 11px;"><i class="fa-solid fa-hotel" style="color: #22d3ee; margin-right: 4px;"></i> Actual Check-In Date & Time</label>
                                <input type="datetime-local" name="checked_in_at" id="view-checked-in-at" class="adm-input" style="padding-left: 12px; font-size: 12.5px;">
                            </div>
                            <div class="adm-form-group" style="margin-bottom: 0;">
                                <label class="adm-label" style="font-size: 11px;"><i class="fa-solid fa-door-open" style="color: #c084fc; margin-right: 4px;"></i> Actual Check-Out Date & Time</label>
                                <input type="datetime-local" name="checked_out_at" id="view-checked-out-at" class="adm-input" style="padding-left: 12px; font-size: 12.5px;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fast 1-Click Concierge Stage Buttons -->
                <div style="margin-bottom: 18px;">
                    <label class="adm-label" style="margin-bottom: 8px;">1-Click Concierge Actions</label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 8px;">
                        <button type="button" class="adm-btn-action emerald" style="padding: 9px 10px; font-weight: 700; font-size: 11.5px; justify-content: center;" onclick="quickSetModalStatus('confirmed');" title="Approve & Confirm Reservation">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Approve</span>
                        </button>
                        <button type="button" class="adm-btn-action cyan" style="padding: 9px 10px; font-weight: 700; font-size: 11.5px; justify-content: center; background: rgba(6, 182, 212, 0.18); border: 1px solid rgba(6, 182, 212, 0.4); color: #22d3ee;" onclick="quickSetModalStatus('inhouse');" title="Guest Arrival / Check-In">
                            <i class="fa-solid fa-hotel"></i>
                            <span>Check-In</span>
                        </button>
                        <button type="button" class="adm-btn-action purple" style="padding: 9px 10px; font-weight: 700; font-size: 11.5px; justify-content: center; background: rgba(168, 85, 247, 0.18); border: 1px solid rgba(168, 85, 247, 0.4); color: #c084fc;" onclick="quickSetModalStatus('completed');" title="Guest Departure / Check-Out">
                            <i class="fa-solid fa-door-open"></i>
                            <span>Check-Out</span>
                        </button>
                        <button type="button" class="adm-btn-action amber" style="padding: 9px 10px; font-weight: 700; font-size: 11.5px; justify-content: center; background: rgba(245, 158, 11, 0.18); border: 1px solid rgba(245, 158, 11, 0.4); color: #fbbf24;" onclick="quickSetModalStatus('waitlist');" title="Move to Waiting List">
                            <i class="fa-solid fa-user-clock"></i>
                            <span>Waitlist</span>
                        </button>
                        <button type="button" class="adm-btn-action danger" style="padding: 9px 10px; font-weight: 700; font-size: 11.5px; justify-content: center;" onclick="quickSetModalStatus('cancelled');" title="Reject / Cancel Reservation">
                            <i class="fa-solid fa-ban"></i>
                            <span>Reject</span>
                        </button>
                    </div>
                </div>

                <!-- Update Status Selector -->
                <div class="adm-form-group">
                    <label class="adm-label">Change Reservation Status</label>
                    <select name="status" id="view-status-select" class="adm-input" style="padding-left: 14px;">
                        <option value="pending">Pending Concierge Review</option>
                        <option value="confirmed">Confirmed / Approved (Upcoming)</option>
                        <option value="inhouse">In-House (Checked-In)</option>
                        <option value="waitlist">Waiting List</option>
                        <option value="completed">Checked-Out & Completed</option>
                        <option value="cancelled">Cancelled / Rejected</option>
                    </select>
                </div>
            </div>

            <div class="adm-modal-footer">
                <button type="button" class="adm-btn-action outline" id="btn-modal-whatsapp" style="margin-right: auto;">
                    <i class="fa-brands fa-whatsapp" style="color: #25D366;"></i> WhatsApp
                </button>
                <a href="#" target="_blank" class="adm-btn-action gold" id="btn-modal-print-bill" style="text-decoration: none; padding: 9px 14px; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-print"></i> Print Bill
                </a>
                <button type="button" class="adm-btn-action danger" id="btn-modal-delete" style="padding: 9px 12px;" onclick="deleteCurrentModalBooking();" title="Delete Reservation Record">
                    <i class="fa-solid fa-trash-can"></i> Delete
                </button>
                <button type="button" class="adm-btn-action outline" data-close-modal="modal-view-booking">Close</button>
                <button type="submit" class="adm-btn-action gold">Save Status & Dates</button>
            </div>
        </form>

        <!-- Hidden delete form for modal -->
        <form method="POST" id="form-modal-delete" style="display:none;">
            <input type="hidden" name="action" value="delete_booking">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="booking_id" id="modal-delete-booking-id">
        </form>
    </div>
</div>

<script>
function quickSetModalStatus(statusVal) {
    if (statusVal === 'cancelled') {
        if (!confirm('Are you sure you want to reject/cancel this reservation?')) {
            return;
        }
    }
    document.getElementById('view-status-select').value = statusVal;
    document.getElementById('form-update-status').submit();
}

function deleteCurrentModalBooking() {
    const bId = document.getElementById('view-booking-id').value;
    const ref = document.getElementById('view-modal-ref').innerText;
    if (confirm('Are you sure you want to permanently delete this reservation record (' + ref + ')?')) {
        document.getElementById('modal-delete-booking-id').value = bId;
        document.getElementById('form-modal-delete').submit();
    }
}

function formatDatetimeForInput(dtStr) {
    if (!dtStr) return '';
    const d = new Date(dtStr);
    if (isNaN(d.getTime())) {
        // Fallback replace space with T
        return dtStr.replace(' ', 'T').substring(0, 16);
    }
    const pad = (n) => String(n).padStart(2, '0');
    return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
}

function viewBookingDetails(b) {
    document.getElementById('view-booking-id').value = b.id;
    document.getElementById('view-modal-title').innerText = b.guest_name;
    document.getElementById('view-modal-ref').innerText = 'Reference ID: ' + b.reference_code;
    document.getElementById('view-guest-name').innerText = b.guest_name;
    document.getElementById('view-guest-phone').innerText = b.guest_phone;
    document.getElementById('view-guest-email').innerText = b.guest_email || 'Not provided';
    document.getElementById('view-guest-city').innerText = b.city_state || 'Not provided';
    
    let idProofText = b.id_proof_type || 'Aadhaar Card';
    if (b.id_proof_number) {
        idProofText += ' (' + b.id_proof_number + ')';
    }
    document.getElementById('view-guest-id-proof').innerText = idProofText;

    const idFileWrap = document.getElementById('view-guest-id-file-wrap');
    const idDownloadBtn = document.getElementById('view-id-proof-download-btn');
    const idExtSpan = document.getElementById('view-id-file-ext');
    if (b.id_proof_file) {
        if (idFileWrap) idFileWrap.style.display = 'block';
        if (idDownloadBtn) {
            idDownloadBtn.href = '../uploads/id_proofs/' + encodeURIComponent(b.id_proof_file);
        }
        if (idExtSpan) {
            const ext = (b.id_proof_file.split('.').pop() || 'FILE').toUpperCase();
            idExtSpan.innerText = ext;
        }
    } else {
        if (idFileWrap) idFileWrap.style.display = 'none';
    }
    
    const villaTitle = (b.villa_type === 'treehouse') ? 'Luxury Canopy Treehouse' : 'Traditional Earthen Mudhouse';
    document.getElementById('view-villa-stay').innerText = villaTitle + ' (' + b.guests_count + ' Guests)';
    document.getElementById('view-dates-duration').innerText = b.checkin_date + ' → ' + b.checkout_date + ' (' + b.nights + ' Nights)';
    document.getElementById('view-total-amount').innerText = '₹' + Number(b.total_amount).toLocaleString('en-IN');
    
    document.getElementById('view-addons-text').innerText = b.addons || 'None selected';
    document.getElementById('view-notes-text').innerText = b.special_notes || 'No special requests noted.';
    
    document.getElementById('view-checked-in-at').value = formatDatetimeForInput(b.checked_in_at);
    document.getElementById('view-checked-out-at').value = formatDatetimeForInput(b.checked_out_at);

    document.getElementById('view-status-select').value = b.status;

    // Attach Print Bill URL
    var printBtn = document.getElementById('btn-modal-print-bill');
    if (printBtn) {
        printBtn.href = 'print_bill.php?ref=' + encodeURIComponent(b.reference_code);
    }

    // Attach WhatsApp Concierge trigger
    document.getElementById('btn-modal-whatsapp').onclick = function() {
        openWhatsAppConcierge({
            guest_name: b.guest_name,
            phone: b.guest_phone,
            reference_code: b.reference_code,
            villa_title: villaTitle,
            checkin_date: b.checkin_date,
            checkout_date: b.checkout_date,
            guests_count: b.guests_count,
            total_amount: b.total_amount
        });
    };

    openAdmModal('modal-view-booking');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
