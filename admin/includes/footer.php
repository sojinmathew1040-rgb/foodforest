        </main>
        <!-- Main Content Area Ends -->
    </div>
</div>

<?php
$current_page_file = basename($_SERVER['PHP_SELF']);
?>
<!-- Mobile App Bottom Navigation Dock (Native App Style for Mobile Devices) -->
<nav class="adm-mobile-bottom-dock" id="adm-mobile-bottom-dock" aria-label="Mobile Bottom Navigation">
    <a href="index.php" class="adm-mob-dock-item <?php echo ($current_page_file === 'index.php') ? 'active' : ''; ?>">
        <div class="adm-mob-dock-icon">
            <i class="fa-solid fa-compass"></i>
        </div>
        <span>Overview</span>
    </a>
    
    <a href="bookings.php" class="adm-mob-dock-item <?php echo ($current_page_file === 'bookings.php') ? 'active' : ''; ?>">
        <div class="adm-mob-dock-icon">
            <i class="fa-solid fa-calendar-check"></i>
            <?php if (isset($pending_count) && $pending_count > 0): ?>
                <span class="adm-mob-dock-badge"><?php echo $pending_count; ?></span>
            <?php endif; ?>
        </div>
        <span>Stays</span>
    </a>

    <a href="kitchen.php" class="adm-mob-dock-item <?php echo ($current_page_file === 'kitchen.php') ? 'active' : ''; ?>">
        <div class="adm-mob-dock-icon">
            <i class="fa-solid fa-kitchen-set"></i>
            <?php if (isset($today_kitchen_orders_count) && $today_kitchen_orders_count > 0): ?>
                <span class="adm-mob-dock-badge" style="background:#F59E0B; color:#051109;"><?php echo $today_kitchen_orders_count; ?></span>
            <?php endif; ?>
        </div>
        <span>Kitchen</span>
    </a>

    <a href="reports.php" class="adm-mob-dock-item <?php echo ($current_page_file === 'reports.php') ? 'active' : ''; ?>">
        <div class="adm-mob-dock-icon">
            <i class="fa-solid fa-chart-pie"></i>
        </div>
        <span>Reports</span>
    </a>

    <button type="button" class="adm-mob-dock-item <?php echo (in_array($current_page_file, ['edit_section.php', 'settings.php', 'inquiries.php', 'user_manual.php', 'channel_sync.php', 'billing.php', 'calendar.php'])) ? 'active' : ''; ?>" id="btn-mob-more-menu" aria-label="Open Full Admin Menu">
        <div class="adm-mob-dock-icon">
            <i class="fa-solid fa-bars-staggered"></i>
            <?php if ((isset($unread_inquiries) && $unread_inquiries > 0) || (isset($pending_reviews_count) && $pending_reviews_count > 0)): ?>
                <span class="adm-mob-dock-badge red">!</span>
            <?php endif; ?>
        </div>
        <span>More</span>
    </button>
</nav>

<!-- Mobile Sidebar Drawer Backdrop Overlay -->
<div class="adm-mobile-sidebar-backdrop" id="adm-mobile-sidebar-backdrop"></div>

<!-- Admin Javascript -->
<script src="assets/js/admin.js?v=<?php echo time(); ?>"></script>
</body>
</html>
