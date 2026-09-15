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
            if (in_array($new_status, ['pending', 'confirmed', 'completed', 'cancelled'])) {
                $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $b_id]);
                $alert_message = 'Reservation status successfully updated to ' . ucfirst($new_status) . '.';
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

                $custom_amount = !empty($_POST['custom_amount']) ? (float)$_POST['custom_amount'] : $calculated_total;

                // Generate Reference Code
                $ref = 'FF-' . rand(1000, 9999);

                $ins = $pdo->prepare("INSERT INTO bookings (reference_code, villa_type, guest_name, guest_phone, guest_email, guests_count, adults_count, kids_count, extra_adults, extra_kids, checkin_date, checkout_date, nights, addons, special_notes, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $ins->execute([$ref, $villa_type, $guest_name, $guest_phone, $guest_email, $guests_count, $adults_count, $kids_count, $extra_adults, $extra_kids, $checkin, $checkout, $nights, $addons, $special_notes, $custom_amount, $status]);

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
$filter_status = trim($_GET['status'] ?? ($_GET['filter'] ?? ''));
$filter_villa = trim($_GET['villa'] ?? '');

$query = "SELECT * FROM bookings WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (guest_name LIKE ? OR guest_phone LIKE ? OR reference_code LIKE ? OR guest_email LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
$all_rooms_list = $pdo->query("SELECT * FROM rooms ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$rooms_lookup = [];
foreach ($all_rooms_list as $r) {
    $rooms_lookup[$r['slug']] = $r;
}

if (!empty($filter_status) && in_array($filter_status, ['pending', 'confirmed', 'completed', 'cancelled'])) {
    $query .= " AND status = ?";
    $params[] = $filter_status;
}

if (!empty($filter_villa)) {
    $query .= " AND villa_type = ?";
    $params[] = $filter_villa;
}

$query .= " ORDER BY checkin_date DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>

<?php if (!empty($alert_message)): ?>
    <div class="adm-alert adm-alert-<?php echo $alert_type; ?>" style="margin-bottom: 20px;">
        <i class="fa-solid <?php echo $alert_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
        <span><?php echo e($alert_message); ?></span>
    </div>
<?php endif; ?>

<!-- Filtering & Action Toolbar -->
<div class="adm-toolbar-card">
    <div class="adm-toolbar-left">
        <!-- Live Search Box -->
        <form method="GET" class="adm-search-box" style="display:flex;">
            <input type="text" name="search" id="adm-table-search" class="adm-search-input" placeholder="Search by name, phone, ref #..." value="<?php echo e($search); ?>">
            <i class="fa-solid fa-magnifying-glass adm-search-icon"></i>
            <?php if (!empty($filter_status)): ?>
                <input type="hidden" name="status" value="<?php echo e($filter_status); ?>">
            <?php endif; ?>
        </form>

        <!-- Status Filter Select -->
        <select class="adm-filter-select" onchange="location.href='bookings.php?status=' + this.value + '&search=<?php echo urlencode($search); ?>';">
            <option value="" <?php echo empty($filter_status) ? 'selected' : ''; ?>>All Statuses</option>
            <option value="pending" <?php echo ($filter_status === 'pending') ? 'selected' : ''; ?>>Pending Concierge</option>
            <option value="confirmed" <?php echo ($filter_status === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
            <option value="completed" <?php echo ($filter_status === 'completed') ? 'selected' : ''; ?>>Completed</option>
            <option value="cancelled" <?php echo ($filter_status === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
        </select>

        <!-- Villa Filter Select -->
        <select class="adm-filter-select" onchange="location.href='bookings.php?villa=' + this.value + '&status=<?php echo urlencode($filter_status); ?>';">
            <option value="" <?php echo empty($filter_villa) ? 'selected' : ''; ?>>All Sanctuary Stays</option>
            <?php foreach ($all_rooms_list as $r): ?>
                <option value="<?php echo htmlspecialchars($r['slug']); ?>" <?php echo ($filter_villa === $r['slug']) ? 'selected' : ''; ?>>
                    <?php echo ($r['stay_type'] === 'mudhouse' ? '🌿 ' : '🌲 ') . htmlspecialchars($r['title']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="adm-toolbar-right">
        <!-- Export CSV Button -->
        <a href="bookings.php?export=csv" class="adm-btn-action outline" title="Export Bookings to CSV">
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
            <h2 class="adm-table-title">All Reservations (<?php echo count($bookings); ?>)</h2>
            <p class="adm-table-subtitle">Showing guest itineraries matching active filters</p>
        </div>
        <?php if (!empty($search) || !empty($filter_status) || !empty($filter_villa)): ?>
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
                            </td>
                            <td style="font-family: var(--adm-font-title); font-weight: 700; color: var(--adm-gold-light); font-size: 15px; white-space: nowrap;">
                                ₹<?php echo number_format($b['total_amount'], 0, '.', ','); ?>
                            </td>
                            <td class="adm-col-status">
                                <?php 
                                $st = strtolower($b['status']);
                                $icon = 'fa-clock';
                                $label = 'Pending';
                                if ($st === 'confirmed') {
                                    $icon = 'fa-circle-check';
                                    $label = 'Confirmed';
                                } elseif ($st === 'completed') {
                                    $icon = 'fa-flag-checkered';
                                    $label = 'Completed';
                                } elseif ($st === 'cancelled') {
                                    $icon = 'fa-ban';
                                    $label = 'Cancelled';
                                }
                                ?>
                                <span class="adm-badge <?php echo e($st); ?>" title="Reservation Status: <?php echo ucfirst($st); ?>">
                                    <span class="adm-badge-dot"></span>
                                    <i class="fa-solid <?php echo $icon; ?>" style="font-size: 10px;"></i>
                                    <span><?php echo $label; ?></span>
                                </span>
                            </td>
                            <td class="adm-col-actions">
                                <div class="adm-actions-cell">
                                    <!-- WhatsApp Concierge Quick Confirmation Trigger -->
                                    <button type="button" class="adm-btn-icon whatsapp" title="Send WhatsApp Concierge Confirmation"
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

                                    <!-- View / Edit Modal Trigger -->
                                    <button type="button" class="adm-btn-icon view" title="View & Modify Reservation"
                                        onclick='viewBookingDetails(<?php echo json_encode($b); ?>)'>
                                        <i class="fa-solid fa-eye"></i>
                                    </button>

                                    <!-- Delete Button -->
                                    <form method="POST" style="display:inline; margin:0;" onsubmit="return confirm('Are you sure you want to permanently delete reservation #<?php echo e($b['reference_code']); ?>?');">
                                        <input type="hidden" name="action" value="delete_booking">
                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                        <button type="submit" class="adm-btn-icon danger" title="Delete Reservation Record">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
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

        <form method="POST">
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
                        <span style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-muted);">Selected Add-on Experiences</span>
                        <div style="color: var(--adm-text-secondary); font-size: 13px;" id="view-addons-text">-</div>
                    </div>

                    <div style="margin-top: 12px; padding-top: 10px; border-top: var(--adm-border-subtle);" id="view-notes-box">
                        <span style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-muted);">Special Requests / Dietary</span>
                        <div style="color: var(--adm-text-secondary); font-size: 13px; font-style: italic;" id="view-notes-text">-</div>
                    </div>
                </div>

                <!-- Update Status Selector -->
                <div class="adm-form-group">
                    <label class="adm-label">Change Reservation Status</label>
                    <select name="status" id="view-status-select" class="adm-input" style="padding-left: 14px;">
                        <option value="pending">Pending Concierge Review</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed / Checked Out</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>

            <div class="adm-modal-footer">
                <button type="button" class="adm-btn-action outline" id="btn-modal-whatsapp" style="margin-right: auto;">
                    <i class="fa-brands fa-whatsapp" style="color: #25D366;"></i> WhatsApp Guest
                </button>
                <button type="button" class="adm-btn-action outline" data-close-modal="modal-view-booking">Close</button>
                <button type="submit" class="adm-btn-action gold">Save Status</button>
            </div>
        </form>
    </div>
</div>

<script>
function viewBookingDetails(b) {
    document.getElementById('view-booking-id').value = b.id;
    document.getElementById('view-modal-title').innerText = b.guest_name;
    document.getElementById('view-modal-ref').innerText = 'Reference ID: ' + b.reference_code;
    document.getElementById('view-guest-name').innerText = b.guest_name;
    document.getElementById('view-guest-phone').innerText = b.guest_phone;
    document.getElementById('view-guest-email').innerText = b.guest_email || 'Not provided';
    
    const villaTitle = (b.villa_type === 'treehouse') ? 'Luxury Canopy Treehouse' : 'Traditional Earthen Mudhouse';
    document.getElementById('view-villa-stay').innerText = villaTitle + ' (' + b.guests_count + ' Guests)';
    document.getElementById('view-dates-duration').innerText = b.checkin_date + ' → ' + b.checkout_date + ' (' + b.nights + ' Nights)';
    document.getElementById('view-total-amount').innerText = '₹' + Number(b.total_amount).toLocaleString('en-IN');
    
    document.getElementById('view-addons-text').innerText = b.addons || 'None selected';
    document.getElementById('view-notes-text').innerText = b.special_notes || 'No special requests noted.';
    
    document.getElementById('view-status-select').value = b.status;

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
