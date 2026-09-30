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

    <a href="billing.php" class="adm-mob-dock-item <?php echo (in_array($current_page_file, ['billing.php', 'print_bill.php'])) ? 'active' : ''; ?>">
        <div class="adm-mob-dock-icon">
            <i class="fa-solid fa-receipt"></i>
            <?php if (isset($inhouse_count) && $inhouse_count > 0): ?>
                <span class="adm-mob-dock-badge green"><?php echo $inhouse_count; ?></span>
            <?php endif; ?>
        </div>
        <span>Billing</span>
    </a>

    <a href="calendar.php" class="adm-mob-dock-item <?php echo ($current_page_file === 'calendar.php') ? 'active' : ''; ?>">
        <div class="adm-mob-dock-icon">
            <i class="fa-solid fa-calendar-days"></i>
        </div>
        <span>Calendar</span>
    </a>

    <button type="button" class="adm-mob-dock-item <?php echo (in_array($current_page_file, ['edit_section.php', 'settings.php', 'inquiries.php', 'user_manual.php', 'channel_sync.php'])) ? 'active' : ''; ?>" id="btn-mob-more-menu" aria-label="Open Full Admin Menu">
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
