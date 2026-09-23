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

// Fetch Currently Occupied / In-House Stays (Guests staying at the sanctuary today)
$today = date('Y-m-d');
$occupied_stmt = $pdo->query("
    SELECT * FROM bookings 
    WHERE checkin_date <= CURDATE() 
      AND checkout_date >= CURDATE() 
      AND status NOT IN ('cancelled', 'rejected')
    ORDER BY checkin_date ASC, id ASC
");
$occupied_bookings = $occupied_stmt->fetchAll();
$occupied_count = count($occupied_bookings);

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

    <!-- Currently Occupied / In-House Stays -->
    <div class="adm-kpi-card emerald">
        <div class="adm-kpi-data">
            <span class="adm-kpi-label">Currently In-House Stays</span>
            <div class="adm-kpi-number"><?php echo $occupied_count; ?></div>
            <span class="adm-kpi-trend positive"><i class="fa-solid fa-hotel"></i> <?php echo $occupied_count > 0 ? $occupied_count . ' Active In-House ' . ($occupied_count === 1 ? 'Stay' : 'Stays') : 'All Villas Vacant Today'; ?></span>
        </div>
        <div class="adm-kpi-icon-wrap">
            <i class="fa-solid fa-bed"></i>
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

    <!-- Total All-Time Reservations -->
    <div class="adm-kpi-card terracotta">
        <div class="adm-kpi-data">
            <span class="adm-kpi-label">Total Reservations</span>
            <div class="adm-kpi-number"><?php echo $total_bookings; ?></div>
            <span class="adm-kpi-trend">All Time Volume</span>
        </div>
        <div class="adm-kpi-icon-wrap">
            <i class="fa-solid fa-calendar-days"></i>
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

<!-- Currently Occupied Reservations Table (In-House Stays) -->
<div class="adm-table-card">
    <div class="adm-table-header">
        <div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <h2 class="adm-table-title">Currently Occupied Reservations</h2>
                <span class="adm-badge confirmed" style="background: rgba(46, 204, 113, 0.18); border-color: rgba(46, 204, 113, 0.4); color: #2ecc71; font-size: 11px;">
                    <span class="adm-pulse-dot" style="background: #2ecc71; width: 6px; height: 6px; margin-right: 4px;"></span>
                    <?php echo $occupied_count; ?> IN-HOUSE <?php echo ($occupied_count === 1) ? 'GUEST' : 'GUESTS'; ?>
                </span>
            </div>
            <p class="adm-table-subtitle">Live status of guests currently residing in Food Forest Sanctuary today (<?php echo date('d M Y'); ?>)</p>
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
                    <th style="width: 85px;">Ref #</th>
                    <th>In-House Guest</th>
                    <th>Occupied Villa</th>
                    <th>Stay Period</th>
                    <th style="width: 105px;">Total (₹)</th>
                    <th class="adm-col-status">Occupancy Status</th>
                    <th class="adm-col-actions">Concierge Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($occupied_bookings)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 45px 20px; color: var(--adm-text-muted);">
                            <div style="width: 52px; height: 52px; border-radius: 50%; background: rgba(197, 160, 89, 0.1); border: 1px solid rgba(197, 160, 89, 0.25); display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-size: 20px; color: var(--adm-gold);">
                                <i class="fa-solid fa-hotel"></i>
                            </div>
                            <h4 style="font-family: var(--adm-font-title); font-size: 14.5px; color: #FFFFFF; margin: 0 0 4px;">No Guests Currently In-House Today</h4>
                            <p style="font-size: 12px; color: var(--adm-text-secondary); max-width: 440px; margin: 0 auto 14px;">
                                All villas are currently vacant or awaiting upcoming reservation arrivals for today (<?php echo date('d M Y'); ?>).
                            </p>
                            <a href="bookings.php" class="adm-btn-action gold" style="display: inline-flex; align-items: center; gap: 8px; padding: 7px 16px; font-size: 11.5px;">
                                <i class="fa-solid fa-calendar-days"></i>
                                <span>Browse All Upcoming Reservations</span>
                            </a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($occupied_bookings as $b): 
                        $villa_title = ($b['villa_type'] === 'treehouse') ? 'Canopy Treehouse' : 'Earthen Mudhouse';
                        
                        // Calculate days remaining in stay
                        $checkout_ts = strtotime($b['checkout_date']);
                        $today_ts = strtotime($today);
                        $days_left = (int) round(($checkout_ts - $today_ts) / 86400);
                        if ($days_left <= 0) {
                            $stay_hint = '<span style="color: #F59E0B; font-weight: 700;"><i class="fa-solid fa-clock-rotate-left"></i> Checking out today</span>';
                        } elseif ($days_left === 1) {
                            $stay_hint = '<span style="color: #2ECC71; font-weight: 600;"><i class="fa-solid fa-moon"></i> 1 night remaining</span>';
                        } else {
                            $stay_hint = '<span style="color: #2ECC71; font-weight: 600;"><i class="fa-solid fa-moon"></i> ' . $days_left . ' nights remaining</span>';
                        }
                    ?>
                        <tr data-status="occupied">
                            <td>
                                <span class="adm-ref-badge" title="Reservation Reference #">
                                    <?php echo e($b['reference_code']); ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: var(--adm-text-primary); margin-bottom: 2px;">
                                    <?php echo e($b['guest_name']); ?>
                                    <span style="font-size: 11px; font-weight: 500; color: var(--adm-gold); margin-left: 6px;">(<?php echo (int)($b['guests_count'] ?? 2); ?> Guests)</span>
                                </div>
                                <div style="font-size: 11.5px; color: var(--adm-text-muted); display: flex; align-items: center; gap: 5px;">
                                    <i class="fa-solid fa-phone" style="font-size: 10px; color: var(--adm-gold);"></i> 
                                    <a href="tel:<?php echo e($b['guest_phone']); ?>" style="color: inherit;" title="Call In-House Guest"><?php echo e($b['guest_phone']); ?></a>
                                </div>
                            </td>
                            <td>
                                <span style="font-size: 13px; font-weight: 600; color: #FFFFFF;">
                                    <i class="fa-solid <?php echo $b['villa_type'] === 'treehouse' ? 'fa-tree' : 'fa-house-chimney'; ?>" style="color: var(--adm-gold); margin-right: 5px;"></i>
                                    <?php echo e($villa_title); ?>
                                </span>
                            </td>
                            <td style="white-space: nowrap;">
                                <div style="font-size: 13px; font-weight: 600; color: var(--adm-text-primary);">
                                    <i class="fa-regular fa-calendar-check" style="font-size: 11px; color: #2ecc71; margin-right: 4px;"></i> 
                                    <?php echo date('d M Y', strtotime($b['checkin_date'])); ?> to <?php echo date('d M Y', strtotime($b['checkout_date'])); ?>
                                    <span class="adm-night-pill"><?php echo e($b['nights']); ?>N</span>
                                </div>
                                <div style="font-size: 11px; margin-top: 3px;">
                                    <?php echo $stay_hint; ?>
                                </div>
                                <?php if (!empty($b['checked_in_at'])): ?>
                                    <div style="font-size: 10px; color: #22d3ee; margin-top: 3px; display: flex; align-items: center; gap: 4px;" title="Actual Check-In Time">
                                        <i class="fa-solid fa-clock"></i> In: <?php echo date('d M, h:i A', strtotime($b['checked_in_at'])); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="font-family: var(--adm-font-title); font-weight: 700; color: var(--adm-gold-light); font-size: 15px; white-space: nowrap;">
                                ₹<?php echo number_format($b['total_amount'], 0, '.', ','); ?>
                            </td>
                            <td class="adm-col-status">
                                <span class="adm-badge confirmed" style="background: rgba(46, 204, 113, 0.18); border-color: rgba(46, 204, 113, 0.4); color: #2ecc71;">
                                    <span class="adm-pulse-dot" style="background: #2ecc71; width: 6px; height: 6px;"></span>
                                    <i class="fa-solid fa-hotel" style="font-size: 10px;"></i>
                                    <span>IN-HOUSE GUEST</span>
                                </span>
                            </td>
                            <td class="adm-col-actions">
                                <div class="adm-actions-cell">
                                    <!-- WhatsApp Concierge Quick Trigger -->
                                    <button type="button" class="adm-btn-icon whatsapp" title="Send WhatsApp Message to In-House Guest"
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

                                    <!-- View Details in Full Bookings Page -->
                                    <a href="bookings.php?search=<?php echo urlencode($b['reference_code']); ?>" class="adm-btn-icon view" title="View Full Reservation Record">
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
