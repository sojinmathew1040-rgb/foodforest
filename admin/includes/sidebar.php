<?php
// =========================================================================
// Food Forest Sanctuary — Admin Sidebar Navigation
// =========================================================================
$current_script = basename($_SERVER['PHP_SELF']);
$pdo = get_db();

// Counts for badges
$pending_count = (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$unread_inquiries = (int) $pdo->query("SELECT COUNT(*) FROM inquiries WHERE status = 'unread'")->fetchColumn();
?>

<aside class="adm-sidebar" id="adm-sidebar">
    <!-- Brand Emblem & Title -->
    <div class="adm-sidebar-brand">
        <div class="adm-sidebar-logo">
            <i class="fa-solid fa-seedling"></i>
        </div>
        <div class="adm-sidebar-brand-text">
            <span class="adm-sidebar-title">FOOD FOREST</span>
            <span class="adm-sidebar-badge">CONCIERGE PORTAL</span>
        </div>
    </div>

    <!-- Navigation List -->
    <div class="adm-nav-section">Concierge Hub</div>
    <ul class="adm-nav-list">
        <li class="adm-nav-item">
            <a href="index.php" class="adm-nav-link <?php echo ($current_script === 'index.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-compass"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="adm-nav-item">
            <a href="bookings.php" class="adm-nav-link <?php echo ($current_script === 'bookings.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-calendar-check"></i>
                <span>Reservations</span>
                <?php if ($pending_count > 0): ?>
                    <span class="adm-nav-badge adm-nav-badge-alert"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="adm-nav-item">
            <a href="inquiries.php" class="adm-nav-link <?php echo ($current_script === 'inquiries.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-envelope-open-text"></i>
                <span>Inquiries</span>
                <?php if ($unread_inquiries > 0): ?>
                    <span class="adm-nav-badge"><?php echo $unread_inquiries; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <div class="adm-nav-section" style="margin-top: 18px;">Sanctuary CMS & Control</div>

        <li class="adm-nav-item">
            <a href="settings.php?tab=menu" class="adm-nav-link <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'menu') ? 'active' : ''; ?>">
                <i class="fa-solid fa-utensils"></i>
                <span>Food Menu Hub</span>
            </a>
        </li>

        <li class="adm-nav-item">
            <a href="settings.php" class="adm-nav-link <?php echo (in_array($current_script, ['settings.php', 'rooms.php', 'gallery.php', 'testimonials.php', 'experiences.php', 'content.php']) && (!isset($_GET['tab']) || $_GET['tab'] !== 'menu')) ? 'active' : ''; ?>">
                <i class="fa-solid fa-sliders"></i>
                <span>Estate Settings</span>
            </a>
        </li>
    </ul>

    <!-- Sidebar Bottom User Profile & Logout -->
    <div class="adm-sidebar-footer">
        <div class="adm-user-profile-row">
            <div class="adm-user-avatar">
                <?php 
                $initials = 'FF';
                if (!empty($admin['full_name'])) {
                    $parts = explode(' ', trim($admin['full_name']));
                    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
                }
                echo e($initials);
                ?>
            </div>
            <div class="adm-user-info">
                <div class="adm-user-name" title="<?php echo e($admin['full_name'] ?? 'Admin'); ?>">
                    <?php echo e($admin['full_name'] ?? 'Admin'); ?>
                </div>
                <div class="adm-user-role"><?php echo e($admin['role'] ?? 'Concierge'); ?></div>
            </div>
            <a href="logout.php" class="adm-btn-logout" title="Log Out" onclick="return confirm('Confirm log out from Food Forest Admin?');">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </a>
        </div>
    </div>
</aside>
