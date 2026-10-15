<?php
// =========================================================================
// Food Forest Sanctuary — Executive Concierge Dashboard
// =========================================================================
require_once __DIR__ . '/includes/auth.php';
require_admin_auth();
require_once __DIR__ . '/includes/db.php';

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

$page_title = 'Sanctuary Overview';
$page_subtitle = 'Estate Performance & Reservation Concierge';
require_once __DIR__ . '/includes/header.php';
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

<!-- Collapsible Compact KPI Metrics Strip (Single Horizontal Row) -->
<section class="adm-kpi-strip-container" id="adm-kpi-container">
    <!-- Header bar with Title, Summary Pills (when collapsed), and Toggle Button -->
    <div class="adm-kpi-strip-header">
        <div class="adm-kpi-strip-title-group">
            <span class="adm-kpi-strip-tag"><i class="fa-solid fa-chart-line"></i></span>
            <span class="adm-kpi-strip-title">Sanctuary Live Performance</span>

            <!-- Compact inline preview pills visible when cards are collapsed -->
            <div class="adm-kpi-collapsed-summary" id="adm-kpi-collapsed-summary">
                <a href="reports.php" class="adm-kpi-summary-pill gold" title="Gross Confirmed Stays">
                    <i class="fa-solid fa-coins"></i> ₹<?php echo number_format($total_revenue, 0, '.', ','); ?>
                </a>
                <a href="#occupied-table-wrap" class="adm-kpi-summary-pill emerald" title="Currently In-House Stays">
                    <i class="fa-solid fa-bed"></i> <?php echo $occupied_count; ?> In-House
                </a>
                <a href="bookings.php?filter=pending" class="adm-kpi-summary-pill amber" title="Pending Concierge Requests">
                    <i class="fa-solid fa-hourglass-half"></i> <?php echo $pending_bookings; ?> Pending
                </a>
                <a href="bookings.php" class="adm-kpi-summary-pill terracotta" title="Total Reservations">
                    <i class="fa-solid fa-calendar-days"></i> <?php echo $total_bookings; ?> Total
                </a>
            </div>
        </div>

        <div class="adm-kpi-strip-actions">
            <button type="button" class="adm-kpi-toggle-btn" id="btn-toggle-kpi" onclick="toggleKpiStrip();" title="Collapse / Expand KPI Metrics" aria-expanded="true">
                <span class="adm-kpi-toggle-label" id="adm-kpi-toggle-label">Collapse</span>
                <i class="fa-solid fa-chevron-up" id="adm-kpi-toggle-icon"></i>
            </button>
        </div>
    </div>

    <!-- 4 Small Compact Cards in a Single Horizontal Row -->
    <div class="adm-kpi-row-wrapper" id="adm-kpi-row-wrapper">
        <div class="adm-kpi-compact-row">
            <!-- 1. Gross Revenue -->
            <a href="reports.php" class="adm-kpi-mini-card gold" title="View Detailed Revenue & Reservation Analytics">
                <div class="adm-kpi-mini-icon">
                    <i class="fa-solid fa-coins"></i>
                </div>
                <div class="adm-kpi-mini-body">
                    <div class="adm-kpi-mini-label">Gross Confirmed Stays</div>
                    <div class="adm-kpi-mini-value">₹<?php echo number_format($total_revenue, 0, '.', ','); ?></div>
                </div>
                <div class="adm-kpi-mini-trend positive" title="Premium Organic Stays">
                    <i class="fa-solid fa-arrow-trend-up"></i> Stays
                </div>
            </a>

            <!-- 2. Currently In-House Stays -->
            <a href="#occupied-table-wrap" class="adm-kpi-mini-card emerald" title="Jump to Current In-House Guests Table">
                <div class="adm-kpi-mini-icon">
                    <i class="fa-solid fa-bed"></i>
                </div>
                <div class="adm-kpi-mini-body">
                    <div class="adm-kpi-mini-label">Currently In-House</div>
                    <div class="adm-kpi-mini-value"><?php echo $occupied_count; ?></div>
                </div>
                <div class="adm-kpi-mini-trend emerald-trend">
                    <span class="adm-kpi-mini-dot"></span> <?php echo $occupied_count > 0 ? $occupied_count . ' Active' : 'Vacant'; ?>
                </div>
            </a>

            <!-- 3. Pending Concierge Requests -->
            <a href="bookings.php?filter=pending" class="adm-kpi-mini-card amber" title="View Pending Reservations Requiring Review">
                <div class="adm-kpi-mini-icon">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
                <div class="adm-kpi-mini-body">
                    <div class="adm-kpi-mini-label">Pending Concierge</div>
                    <div class="adm-kpi-mini-value"><?php echo $pending_bookings; ?></div>
                </div>
                <div class="adm-kpi-mini-trend amber-trend">
                    <?php echo $pending_bookings > 0 ? '<span class="adm-kpi-badge-pulse">' . $pending_bookings . ' Action</span>' : '<i class="fa-solid fa-check"></i> Clean'; ?>
                </div>
            </a>

            <!-- 4. Total Reservations -->
            <a href="bookings.php" class="adm-kpi-mini-card terracotta" title="View All Reservations in Master Log">
                <div class="adm-kpi-mini-icon">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
                <div class="adm-kpi-mini-body">
                    <div class="adm-kpi-mini-label">Total Reservations</div>
                    <div class="adm-kpi-mini-value"><?php echo $total_bookings; ?></div>
                </div>
                <div class="adm-kpi-mini-trend slate-trend">
                    All-Time
                </div>
            </a>
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
        <div style="display: flex; align-items: center; gap: 8px;">
            <a href="settings.php?tab=rooms" class="adm-btn-action outline" style="padding: 6px 12px; font-size: 12px;">
                <i class="fa-solid fa-gear"></i> Manage Villas
            </a>
            <button type="button" class="adm-btn-action outline" id="btn-toggle-villas" onclick="toggleSectionCollapse('villas-table-wrap', this);" title="Expand / Collapse Villas" style="padding: 6px 11px; font-size: 12px; cursor: pointer;">
                <i class="fa-solid fa-chevron-up"></i>
            </button>
        </div>
    </div>
    <div id="villas-table-wrap" class="adm-table-responsive">
        <table class="adm-data-table">
            <thead>
                <tr>
                    <th>Villa Concept</th>
                    <th>Concept / Feature</th>
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
                        <td><?php echo !empty($villa['elevation']) ? e($villa['elevation']) : '<span style="color:var(--adm-text-secondary); opacity:0.6;">—</span>'; ?></td>
                        <td>Up to <?php echo e($villa['max_guests']); ?> Guests</td>
                        <td style="font-family: var(--adm-font-title); font-weight: 700; color: var(--adm-gold-light);">
                            ₹<?php echo number_format($villa['rate_per_night'], 0, '.', ','); ?>
                        </td>
                        <td>
                            <?php 
                            $v_st = $villa['availability_status'] ?? (!empty($villa['is_available']) ? 'available' : 'maintenance');
                            if ($v_st === 'closed_renovation'): ?>
                                <span class="adm-badge" style="background: rgba(245, 158, 11, 0.2); color: #FBBF24; border: 1px solid rgba(245, 158, 11, 0.45);"><i class="fa-solid fa-person-digging"></i> Renovation</span>
                            <?php elseif ($v_st === 'opening_soon'): ?>
                                <span class="adm-badge" style="background: rgba(56, 189, 248, 0.2); color: #38BDF8; border: 1px solid rgba(56, 189, 248, 0.45);"><i class="fa-solid fa-sparkles"></i> Opening Soon</span>
                            <?php elseif ($v_st === 'available'): ?>
                                <span class="adm-badge confirmed"><i class="fa-solid fa-circle-check"></i> Available</span>
                            <?php else: ?>
                                <span class="adm-badge cancelled"><i class="fa-solid fa-wrench"></i> Offline</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="rooms.php" class="adm-btn-icon" title="Edit Villa & Availability Status">
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
        <div style="display: flex; align-items: center; gap: 8px;">
            <a href="bookings.php" class="adm-btn-action outline" style="padding: 6px 12px; font-size: 12px;">
                <span>View All Reservations (<?php echo $total_bookings; ?>)</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
            <button type="button" class="adm-btn-action outline" id="btn-toggle-occupied" onclick="toggleSectionCollapse('occupied-table-wrap', this);" title="Expand / Collapse Reservations" style="padding: 6px 11px; font-size: 12px; cursor: pointer;">
                <i class="fa-solid fa-chevron-up"></i>
            </button>
        </div>
    </div>

    <div id="occupied-table-wrap" class="adm-table-responsive">
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

                                    <!-- Stay Audit & Check-Out Wizard Button -->
                                    <button type="button" class="adm-btn-icon" style="background: rgba(168, 85, 247, 0.18); border: 1px solid rgba(168, 85, 247, 0.4); color: #c084fc;" title="Stay Audit & Check-Out" onclick="openCheckoutAuditModal(<?php echo $b['id']; ?>);">
                                        <i class="fa-solid fa-door-open"></i>
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

<?php require_once __DIR__ . '/includes/checkout_audit_modal.php'; ?>

<script>
function toggleKpiStrip(forceState) {
    var wrapper = document.getElementById('adm-kpi-row-wrapper');
    var summary = document.getElementById('adm-kpi-collapsed-summary');
    var label = document.getElementById('adm-kpi-toggle-label');
    var icon = document.getElementById('adm-kpi-toggle-icon');
    var btn = document.getElementById('btn-toggle-kpi');
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
        try { localStorage.setItem('ff_dash_kpi_state', 'collapsed'); } catch(e){}
    } else {
        wrapper.style.display = 'block';
        if (summary) summary.style.display = 'none';
        if (label) label.textContent = 'Collapse';
        if (icon) icon.className = 'fa-solid fa-chevron-up';
        if (btn) btn.setAttribute('aria-expanded', 'true');
        try { localStorage.setItem('ff_dash_kpi_state', 'expanded'); } catch(e){}
    }
}

function toggleSectionCollapse(wrapId, btn) {
    var wrap = document.getElementById(wrapId);
    if (!wrap) return;
    var icon = btn ? btn.querySelector('i') : null;
    var isHidden = (wrap.style.display === 'none');
    if (isHidden) {
        wrap.style.display = 'block';
        if (icon) {
            icon.className = 'fa-solid fa-chevron-up';
        }
        try { localStorage.setItem('ff_dash_' + wrapId, 'expanded'); } catch(e){}
    } else {
        wrap.style.display = 'none';
        if (icon) {
            icon.className = 'fa-solid fa-chevron-down';
        }
        try { localStorage.setItem('ff_dash_' + wrapId, 'collapsed'); } catch(e){}
    }
}

// Restore saved collapse preferences
document.addEventListener('DOMContentLoaded', function() {
    try {
        var kpiState = localStorage.getItem('ff_dash_kpi_state');
        if (kpiState === 'collapsed') {
            toggleKpiStrip(false);
        }
    } catch(e){}

    ['villas-table-wrap', 'occupied-table-wrap'].forEach(function(wrapId) {
        try {
            var state = localStorage.getItem('ff_dash_' + wrapId);
            if (state === 'collapsed') {
                var btn = (wrapId === 'villas-table-wrap') ? document.getElementById('btn-toggle-villas') : document.getElementById('btn-toggle-occupied');
                toggleSectionCollapse(wrapId, btn);
            }
        } catch(e){}
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
