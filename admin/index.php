<?php
// =========================================================================
// Food Forest Sanctuary — Executive Concierge Dashboard
// =========================================================================
$page_title = 'Sanctuary Overview';
$page_subtitle = 'Estate Performance & Reservation Concierge';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db();

// Handle quick status updates from dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_status') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $b_id = (int)$_POST['booking_id'];
        $new_status = $_POST['status'];
        if (in_array($new_status, ['pending', 'confirmed', 'completed', 'cancelled'])) {
            $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $b_id]);
            header("Location: index.php?msg=status_updated");
            exit;
        }
    }
}

// Calculate KPI Metrics
$total_bookings = (int) $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$pending_bookings = (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$confirmed_bookings = (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
$total_revenue = (float) $pdo->query("SELECT SUM(total_amount) FROM bookings WHERE status IN ('confirmed', 'completed')")->fetchColumn();

// Fetch Recent Bookings
$recent_bookings = $pdo->query("
    SELECT * FROM bookings 
    ORDER BY created_at DESC 
    LIMIT 6
")->fetchAll();

// Fetch Villas
$villas = $pdo->query("SELECT * FROM rooms ORDER BY id ASC")->fetchAll();

// Fetch Recent Inquiries
$recent_inquiries = $pdo->query("
    SELECT * FROM inquiries 
    ORDER BY created_at DESC 
    LIMIT 4
")->fetchAll();
?>

<!-- KPI Stat Cards -->
<section class="adm-kpi-grid">
    <!-- Revenue -->
    <div class="adm-kpi-card gold">
        <div class="adm-kpi-data">
            <span class="adm-kpi-label">Gross Confirmed Stays</span>
            <div class="adm-kpi-number">₹<?php echo number_format($total_revenue, 0, '.', ','); ?></div>
            <span class="adm-kpi-trend positive"><i class="fa-solid fa-arrow-trend-up"></i> Premium Organic Stays</span>
        </div>
        <div class="adm-kpi-icon-wrap">
            <i class="fa-solid fa-coins"></i>
        </div>
    </div>

    <!-- Total Bookings -->
    <div class="adm-kpi-card emerald">
        <div class="adm-kpi-data">
            <span class="adm-kpi-label">Total Reservations</span>
            <div class="adm-kpi-number"><?php echo $total_bookings; ?></div>
            <span class="adm-kpi-trend">All Time Volume</span>
        </div>
        <div class="adm-kpi-icon-wrap">
            <i class="fa-solid fa-calendar-days"></i>
        </div>
    </div>

    <!-- Pending Review -->
    <div class="adm-kpi-card amber">
        <div class="adm-kpi-data">
            <span class="adm-kpi-label">Pending Concierge</span>
            <div class="adm-kpi-number"><?php echo $pending_bookings; ?></div>
            <span class="adm-kpi-trend"><?php echo $pending_bookings > 0 ? 'Requires Concierge Attention' : 'All Requests Processed'; ?></span>
        </div>
        <div class="adm-kpi-icon-wrap">
            <i class="fa-solid fa-hourglass-half"></i>
        </div>
    </div>

    <!-- Confirmed Stays -->
    <div class="adm-kpi-card terracotta">
        <div class="adm-kpi-data">
            <span class="adm-kpi-label">Confirmed Guests</span>
            <div class="adm-kpi-number"><?php echo $confirmed_bookings; ?></div>
            <span class="adm-kpi-trend">Guaranteed Bookings</span>
        </div>
        <div class="adm-kpi-icon-wrap">
            <i class="fa-solid fa-bed"></i>
        </div>
    </div>
</section>

<!-- Action & Toolbar Strip -->
<div class="adm-toolbar-card">
    <div class="adm-toolbar-left">
        <span style="font-family: var(--adm-font-title); font-weight: 600; color: var(--adm-text-gold); letter-spacing: 1px;">
            <i class="fa-solid fa-bolt-lightning"></i> CONCIERGE QUICK ACTIONS
        </span>
    </div>
    <div class="adm-toolbar-right">
        <a href="bookings.php?filter=pending" class="adm-btn-action outline">
            <i class="fa-solid fa-clock"></i>
            <span>Pending Requests (<?php echo $pending_bookings; ?>)</span>
        </a>
        <a href="bookings.php?action=new" class="adm-btn-action gold">
            <i class="fa-solid fa-plus"></i>
            <span>New Manual Booking</span>
        </a>
    </div>
</div>

<!-- Villa Occupancy & Status Glance -->
<div class="adm-table-card">
    <div class="adm-table-header">
        <div>
            <h2 class="adm-table-title">Sanctuary Stays & Villa Availability</h2>
            <p class="adm-table-subtitle">Current active status and nightly tariff configuration</p>
        </div>
        <a href="settings.php?tab=rooms" class="adm-btn-action outline" style="padding: 6px 12px; font-size: 12px;">
            <i class="fa-solid fa-gear"></i> Manage Villas
        </a>
    </div>
    <div class="adm-table-responsive">
        <table class="adm-data-table">
            <thead>
                <tr>
                    <th>Villa Concept</th>
                    <th>Elevation / Feature</th>
                    <th>Capacity</th>
                    <th>Nightly Rate</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($villas as $villa): ?>
                    <tr>
                        <td style="font-weight: 600; color: var(--adm-text-primary);">
                            <i class="fa-solid <?php echo $villa['slug'] === 'treehouse' ? 'fa-tree' : 'fa-house-chimney'; ?>" style="color: var(--adm-gold); margin-right: 8px;"></i>
                            <?php echo e($villa['title']); ?>
                        </td>
                        <td><?php echo e($villa['elevation']); ?></td>
                        <td>Up to <?php echo e($villa['max_guests']); ?> Guests</td>
                        <td style="font-family: var(--adm-font-title); font-weight: 700; color: var(--adm-gold-light);">
                            ₹<?php echo number_format($villa['rate_per_night'], 0, '.', ','); ?>
                        </td>
                        <td>
                            <?php if ($villa['is_available']): ?>
                                <span class="adm-badge confirmed"><i class="fa-solid fa-circle-check"></i> Available</span>
                            <?php else: ?>
                                <span class="adm-badge cancelled"><i class="fa-solid fa-ban"></i> Blocked / Maint.</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="settings.php?tab=rooms" class="adm-btn-icon" title="Edit Rate & Availability">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Bookings Table -->
<div class="adm-table-card">
    <div class="adm-table-header">
        <div>
            <h2 class="adm-table-title">Recent Guest Reservations</h2>
            <p class="adm-table-subtitle">Latest booking requests submitted online or via concierge</p>
        </div>
        <a href="bookings.php" class="adm-btn-action outline" style="padding: 6px 12px; font-size: 12px;">
            <span>View All Reservations (<?php echo $total_bookings; ?>)</span>
            <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>

    <div class="adm-table-responsive">
        <table class="adm-data-table">
            <thead>
                <tr>
                    <th>Ref #</th>
                    <th>Guest Information</th>
                    <th>Villa Choice</th>
                    <th>Check-in / Out</th>
                    <th>Nights</th>
                    <th>Total (₹)</th>
                    <th>Status</th>
                    <th>Concierge Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_bookings)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: var(--adm-text-muted);">
                            No reservations on record yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_bookings as $b): 
                        $villa_title = ($b['villa_type'] === 'treehouse') ? 'Canopy Treehouse' : 'Earthen Mudhouse';
                    ?>
                        <tr data-status="<?php echo e($b['status']); ?>">
                            <td>
                                <strong style="font-family: monospace; color: var(--adm-gold-light); font-size: 13px;">
                                    <?php echo e($b['reference_code']); ?>
                                </strong>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--adm-text-primary);"><?php echo e($b['guest_name']); ?></div>
                                <div style="font-size: 11.5px; color: var(--adm-text-muted);"><i class="fa-solid fa-phone" style="font-size: 10px;"></i> <?php echo e($b['guest_phone']); ?></div>
                            </td>
                            <td>
                                <span style="font-size: 12.5px;">
                                    <i class="fa-solid <?php echo $b['villa_type'] === 'treehouse' ? 'fa-tree' : 'fa-house-chimney'; ?>" style="color: var(--adm-gold); margin-right: 4px;"></i>
                                    <?php echo e($villa_title); ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-size: 12.5px;"><?php echo date('d M Y', strtotime($b['checkin_date'])); ?></div>
                                <div style="font-size: 11px; color: var(--adm-text-muted);">to <?php echo date('d M Y', strtotime($b['checkout_date'])); ?></div>
                            </td>
                            <td><?php echo e($b['nights']); ?>N</td>
                            <td style="font-family: var(--adm-font-title); font-weight: 700; color: var(--adm-gold-light);">
                                ₹<?php echo number_format($b['total_amount'], 0, '.', ','); ?>
                            </td>
                            <td>
                                <span class="adm-badge <?php echo e($b['status']); ?>">
                                    <?php echo e($b['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="adm-actions-cell">
                                    <!-- WhatsApp Concierge Quick Trigger -->
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

                                    <!-- Quick Confirm if pending -->
                                    <?php if ($b['status'] === 'pending'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="quick_status">
                                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                            <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                            <input type="hidden" name="status" value="confirmed">
                                            <button type="submit" class="adm-btn-icon" title="Confirm Reservation" style="color: #48BB78;">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <!-- View Details in Full Bookings Page -->
                                    <a href="bookings.php?search=<?php echo urlencode($b['reference_code']); ?>" class="adm-btn-icon" title="View Full Details">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
