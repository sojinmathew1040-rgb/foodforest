<?php
// =========================================================================
// Food Forest Sanctuary — Admin Sidebar Navigation
// =========================================================================
$current_script = basename($_SERVER['PHP_SELF']);
$pdo = get_db();

// Counts for badges
$pending_count = (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$inhouse_count = (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'inhouse'")->fetchColumn();
$unread_inquiries = (int) $pdo->query("SELECT COUNT(*) FROM inquiries WHERE status = 'unread'")->fetchColumn();
ensure_testimonials_columns($pdo);
$pending_reviews_count = (int) $pdo->query("SELECT COUNT(*) FROM testimonials WHERE status = 'pending'")->fetchColumn();
$today_kitchen_orders_count = (int) $pdo->query("SELECT COUNT(*) FROM bookings 
    WHERE status != 'cancelled' 
    AND checkin_date <= CURDATE() AND checkout_date >= CURDATE()
    AND food_items IS NOT NULL AND food_items != '' AND food_items != '[]'")->fetchColumn();
$site_name = get_setting('site_name', get_setting('estate_name', 'FOOD FOREST'));
$site_logo = get_setting('site_logo', '');
?>

<aside class="adm-sidebar" id="adm-sidebar">
    <!-- Brand Emblem, Title & Minimize Toggle Button -->
    <div class="adm-sidebar-brand">
        <div class="adm-sidebar-logo" title="<?php echo htmlspecialchars($site_name); ?>">
            <?php if (!empty($site_logo)): ?>
                <img src="../<?php echo htmlspecialchars(ltrim($site_logo, '/')); ?>" alt="<?php echo htmlspecialchars($site_name); ?>" style="max-height: 24px; max-width: 24px; object-fit: contain;">
            <?php else: ?>
                <i class="fa-solid fa-seedling"></i>
            <?php endif; ?>
        </div>
        <div class="adm-sidebar-brand-text">
            <span class="adm-sidebar-title"><?php echo htmlspecialchars($site_name); ?></span>
            <span class="adm-sidebar-badge">CONCIERGE PORTAL</span>
        </div>
        <button type="button" class="adm-sidebar-toggle-btn" id="adm-sidebar-toggle-btn" title="Minimize / Expand Menu" aria-label="Toggle Sidebar Menu">
            <i class="fa-solid fa-angles-left"></i>
        </button>
        <button type="button" class="adm-sidebar-close-mob" id="adm-sidebar-close-mob" title="Close Menu" aria-label="Close Mobile Menu">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <!-- Mobile App Quick Actions Row (Visible only in mobile drawer) -->
    <div class="adm-sidebar-mob-quick-actions" style="padding: 10px 14px 6px; display: none; gap: 6px;">
        <a href="billing.php" class="adm-btn-action outline" style="flex: 1; padding: 6px 8px; font-size: 11px; justify-content: center; text-decoration: none; border-radius: 6px;" title="Billing & Invoices">
            <i class="fa-solid fa-receipt"></i> <span>Billing</span>
        </a>
        <a href="calendar.php" class="adm-btn-action outline" style="flex: 1; padding: 6px 8px; font-size: 11px; justify-content: center; text-decoration: none; border-radius: 6px;" title="Booking Calendar">
            <i class="fa-solid fa-calendar-days"></i> <span>Calendar</span>
        </a>
        <a href="settings.php" class="adm-btn-action gold" style="flex: 1; padding: 6px 8px; font-size: 11px; justify-content: center; text-decoration: none; border-radius: 6px;" title="Estate Settings">
            <i class="fa-solid fa-sliders"></i> <span>Settings</span>
        </a>
    </div>

    <!-- Navigation List -->
    <div class="adm-nav-section">Concierge Hub</div>
    <ul class="adm-nav-list">
        <li class="adm-nav-item">
            <a href="index.php" class="adm-nav-link <?php echo ($current_script === 'index.php') ? 'active' : ''; ?>" title="Dashboard">
                <i class="fa-solid fa-compass"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="adm-nav-item">
            <a href="bookings.php" class="adm-nav-link <?php echo ($current_script === 'bookings.php') ? 'active' : ''; ?>" title="Reservations">
                <i class="fa-solid fa-calendar-check"></i>
                <span>Reservations</span>
                <?php if ($pending_count > 0): ?>
                    <span class="adm-nav-badge adm-nav-badge-alert"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="adm-nav-item">
            <a href="billing.php" class="adm-nav-link <?php echo (in_array($current_script, ['billing.php', 'print_bill.php', 'custom_bill.php'])) ? 'active' : ''; ?>" title="Billing & Invoices">
                <i class="fa-solid fa-receipt"></i>
                <span>Billing & Invoices</span>
                <?php if ($inhouse_count > 0): ?>
                    <span class="adm-nav-badge" style="background: rgba(16, 185, 129, 0.25); color: #34D399; border: 1px solid rgba(16, 185, 129, 0.4);" title="<?php echo $inhouse_count; ?> in-house guests"><?php echo $inhouse_count; ?> Live</span>
                <?php endif; ?>
            </a>
        </li>

        <li class="adm-nav-item">
            <a href="kitchen.php" class="adm-nav-link <?php echo ($current_script === 'kitchen.php') ? 'active' : ''; ?>" title="Kitchen & Chef Orders">
                <i class="fa-solid fa-kitchen-set"></i>
                <span>Kitchen</span>
                <?php if ($today_kitchen_orders_count > 0): ?>
                    <span class="adm-nav-badge" style="background: rgba(245, 158, 11, 0.25); color: #F59E0B; border: 1px solid rgba(245, 158, 11, 0.4);" title="<?php echo $today_kitchen_orders_count; ?> food orders active today"><?php echo $today_kitchen_orders_count; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="adm-nav-item">
            <a href="calendar.php" class="adm-nav-link <?php echo ($current_script === 'calendar.php') ? 'active' : ''; ?>" title="Booking Calendar">
                <i class="fa-solid fa-calendar-days"></i>
                <span>Booking Calendar</span>
            </a>
        </li>

        <li class="adm-nav-item">
            <a href="channel_sync.php" class="adm-nav-link <?php echo ($current_script === 'channel_sync.php') ? 'active' : ''; ?>" title="OTA & Channel Sync">
                <i class="fa-solid fa-arrows-rotate"></i>
                <span>OTA Channel Sync</span>
            </a>
        </li>

        <li class="adm-nav-item">
            <a href="inquiries.php" class="adm-nav-link <?php echo ($current_script === 'inquiries.php') ? 'active' : ''; ?>" title="Inquiries">
                <i class="fa-solid fa-envelope-open-text"></i>
                <span>Inquiries</span>
                <?php if ($unread_inquiries > 0): ?>
                    <span class="adm-nav-badge"><?php echo $unread_inquiries; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="adm-nav-item">
            <a href="reports.php" class="adm-nav-link <?php echo ($current_script === 'reports.php') ? 'active' : ''; ?>" title="Reports &amp; Performance Audit">
                <i class="fa-solid fa-chart-pie"></i>
                <span>Reports &amp; Audit</span>
            </a>
        </li>

        <div class="adm-nav-section" style="margin-top: 18px;">Sanctuary CMS & Control</div>

        <li class="adm-nav-item">
            <a href="edit_section.php?section=testimonials" class="adm-nav-link <?php echo ($current_script === 'edit_section.php' && (($_GET['section'] ?? '') === 'testimonials' || ($_GET['tab'] ?? '') === 'testimonials')) ? 'active' : ''; ?>" title="Guest Stories & Reflections">
                <i class="fa-solid fa-comment-dots"></i>
                <span>Guest Reflections</span>
                <?php if ($pending_reviews_count > 0): ?>
                    <span class="adm-nav-badge adm-nav-badge-alert" title="<?php echo $pending_reviews_count; ?> pending verification"><?php echo $pending_reviews_count; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="adm-nav-item">
            <a href="settings.php" class="adm-nav-link <?php echo (in_array($current_script, ['settings.php', 'edit_section.php', 'rooms.php', 'gallery.php', 'testimonials.php', 'experiences.php', 'content.php']) && (($_GET['section'] ?? '') !== 'testimonials' && ($_GET['tab'] ?? '') !== 'testimonials')) ? 'active' : ''; ?>" title="Estate Settings">
                <i class="fa-solid fa-sliders"></i>
                <span>Estate Settings</span>
            </a>
        </li>

        <li class="adm-nav-item">
            <a href="user_manual.php" class="adm-nav-link <?php echo ($current_script === 'user_manual.php') ? 'active' : ''; ?>" title="User Manual & Onboarding Guide">
                <i class="fa-solid fa-book-open"></i>
                <span>User Manual</span>
                <span class="adm-nav-badge" style="background: rgba(197, 160, 89, 0.25); color: #DFC289; border: 1px solid rgba(197, 160, 89, 0.4);">Guide</span>
            </a>
        </li>
    </ul>

    <!-- Sidebar Bottom User Profile & Logout -->
    <div class="adm-sidebar-footer">
        <div class="adm-user-profile-row" style="cursor: pointer;" onclick="if(window.openProfileModal) openProfileModal();" title="Click to edit profile & manage users">
            <div class="adm-user-avatar" id="adm-sidebar-avatar" style="overflow: hidden; display: flex; align-items: center; justify-content: center;">
                <?php if (!empty($admin['avatar_url'])): ?>
                    <img src="<?php echo (strpos($admin['avatar_url'], 'http') === 0 || strpos($admin['avatar_url'], 'data:') === 0 ? e($admin['avatar_url']) : '../' . ltrim(e($admin['avatar_url']), '/')); ?>" alt="<?php echo e($admin['full_name'] ?? 'Admin'); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: inherit; display: block;">
                <?php else: ?>
                    <?php 
                    $initials = 'FF';
                    if (!empty($admin['full_name'])) {
                        $parts = explode(' ', trim($admin['full_name']));
                        $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
                    }
                    echo e($initials);
                    ?>
                <?php endif; ?>
            </div>
            <div class="adm-user-info">
                <div class="adm-user-name" id="adm-sidebar-name" title="<?php echo e($admin['full_name'] ?? 'Admin'); ?>">
                    <?php echo e($admin['full_name'] ?? 'Admin'); ?>
                </div>
                <div class="adm-user-role"><?php echo e($admin['role'] ?? 'Concierge'); ?></div>
            </div>
            <a href="logout.php" class="adm-btn-logout" title="Log Out" onclick="event.stopPropagation(); return confirm('Confirm log out from Food Forest Admin?');">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </a>
        </div>
    </div>
</aside>
