<?php
// =========================================================================
// Food Forest Sanctuary — Resident Guest Portal & Executive Hub
// =========================================================================

require_once __DIR__ . '/admin/includes/db.php';
require_once __DIR__ . '/includes/client_auth.php';

client_session_start();

// 1. Enforce 5-Minute Inactivity Auto-Logout Server-Side
if (check_client_inactivity_timeout()) {
    header("Location: guest_portal.php?timeout=1");
    exit;
}

$is_user = is_client_user_logged_in();
$is_guest = is_guest_booking_session_active();
$current_user = $is_user ? get_logged_in_client_user() : null;
$guest_booking = $is_guest ? get_active_guest_booking() : null;

// Timeout Notice Check
$timeout_notice = isset($_GET['timeout']) || !empty($_SESSION['ff_session_timeout']);
if (isset($_SESSION['ff_session_timeout'])) {
    unset($_SESSION['ff_session_timeout']);
}

// Pre-fill ref if redirected
$prefill_ref = strtoupper(trim($_GET['ref'] ?? ''));

$pdo = get_db();
ensure_food_menu_table_exists($pdo);
ensure_users_and_guest_columns($pdo);
ensure_booking_gst_columns($pdo);

// 2. Fetch Reservations if Authenticated
$bookings_list = [];
if ($is_user && $current_user) {
    $bookings_list = get_client_bookings($current_user['id']);
} elseif ($is_guest && $guest_booking) {
    $bookings_list = [$guest_booking];
}

// Enrich each booking with full parsed billing calculations & room data
foreach ($bookings_list as &$bk_item) {
    $billing = get_booking_billing_details($pdo, $bk_item['id']);
    $bk_item['billing'] = $billing ? $billing['parsed'] : null;

    // Fetch Room Specifications & Photos
    $room_stmt = null;
    if (!empty($bk_item['room_id'])) {
        $room_stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
        $room_stmt->execute([(int)$bk_item['room_id']]);
    } else {
        $v_type = $bk_item['villa_type'] ?? 'treehouse';
        $room_stmt = $pdo->prepare("SELECT * FROM rooms WHERE slug LIKE ? OR stay_type LIKE ? LIMIT 1");
        $room_stmt->execute(['%' . $v_type . '%', '%' . $v_type . '%']);
    }
    $bk_item['room_details'] = $room_stmt ? $room_stmt->fetch(PDO::FETCH_ASSOC) : null;

    // Folio Secure Access Token for Guest PDF Printing
    $bk_item['folio_token'] = substr(hash('sha256', (string)$bk_item['reference_code'] . 'ff_sanctuary_folio_secret'), 0, 16);
}
unset($bk_item);

// 3. Resolve Currently Active Selected Booking
$active_booking_id = (int)($_GET['booking_id'] ?? ($bookings_list[0]['id'] ?? 0));
$active_booking = null;
foreach ($bookings_list as $b_check) {
    if ((int)$b_check['id'] === $active_booking_id) {
        $active_booking = $b_check;
        break;
    }
}
if (!$active_booking && !empty($bookings_list)) {
    $active_booking = $bookings_list[0];
    $active_booking_id = (int)$active_booking['id'];
}

// Active Booking Metrics
$b_image = 'assets/images/treehouse_exterior.png';
$b_title = 'Sanctuary Luxury Suite';
$b_ref = '';
$room_val = 0.0;
$food_val = 0.0;
$act_val = 0.0;
$custom_val = 0.0;
$gst_pct = 5.0;
$cgst_val = 0.0;
$sgst_val = 0.0;
$grand_val = 0.0;
$adv_val = 0.0;
$bal_val = 0.0;
$f_items = [];
$b_food_count = 0;
$folio_token = '';
$room_photos = [];

if ($active_booking) {
    $b_image = $active_booking['room_image'] ?? ($active_booking['room_details']['image_url'] ?? ($active_booking['villa_type'] === 'treehouse' ? 'assets/images/treehouse_exterior.png' : 'assets/images/mudhouse_exterior.png'));
    $b_title = $active_booking['room_title'] ?? ($active_booking['room_details']['title'] ?? ($active_booking['villa_type'] === 'treehouse' ? 'Luxury Canopy Treehouse' : 'Traditional Earthen Mudhouse'));
    $b_ref = htmlspecialchars($active_booking['reference_code']);
    $folio_token = $active_booking['folio_token'];

    $bp = $active_booking['billing'] ?? null;
    $room_val = $bp ? (float)$bp['room_amount'] : (float)($active_booking['room_amount'] ?? 0);
    $food_val = $bp ? (float)$bp['food_total'] : (float)($active_booking['food_amount'] ?? 0);
    $act_val = $bp ? (float)$bp['activities_total'] : 0.00;
    $custom_val = $bp ? ((float)$bp['custom_total'] + (float)$bp['extra_charges']) : (float)($active_booking['extra_charges'] ?? 0);
    $gst_pct = $bp ? (float)$bp['gst_percentage'] : 5.00;
    $cgst_val = $bp ? (float)$bp['cgst_amount'] : round(($room_val + $food_val + $act_val + $custom_val) * 0.025, 2);
    $sgst_val = $bp ? (float)$bp['sgst_amount'] : round(($room_val + $food_val + $act_val + $custom_val) * 0.025, 2);
    $grand_val = $bp ? (float)$bp['net_total'] : (float)($active_booking['total_amount'] ?? 0);
    $adv_val = $bp ? (float)$bp['advance_paid'] : (float)($active_booking['advance_paid'] ?? 0);
    $bal_val = $bp ? (float)$bp['balance_due'] : max(0, $grand_val - $adv_val);

    $f_items = $bp ? ($bp['food_items'] ?? []) : (!empty($active_booking['food_items']) ? json_decode($active_booking['food_items'], true) : []);
    if (!is_array($f_items)) $f_items = [];
    $b_food_count = count($f_items);

    if (!empty($active_booking['room_details']['photos'])) {
        $dec = json_decode($active_booking['room_details']['photos'], true);
        if (is_array($dec)) $room_photos = $dec;
    }
}

// 4. Load Complete Estate Food Menu
$portal_menu_items = [];
try {
    $m_stmt = $pdo->query("SELECT id, category, heading, subtitle, price, dietary_type, default_meal_time, inclusions FROM food_menu WHERE is_active = 1 ORDER BY category ASC, heading ASC");
    $portal_menu_items = $m_stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($portal_menu_items as &$pmi) {
        $pmi['price'] = (float)$pmi['price'];
        $pmi['inclusions'] = !empty($pmi['inclusions']) ? json_decode($pmi['inclusions'], true) : [];
    }
    unset($pmi);
} catch (Exception $e) {
    $portal_menu_items = [];
}

// 5. Load Sanctuary Experiences
$all_experiences = [];
try {
    $exp_stmt = $pdo->query("SELECT * FROM experiences WHERE is_active = 1 ORDER BY display_order ASC");
    $all_experiences = $exp_stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all_experiences as &$exp) {
        $exp['highlights'] = !empty($exp['highlights']) ? json_decode($exp['highlights'], true) : [];
        $exp['gallery_images'] = !empty($exp['gallery_images']) ? json_decode($exp['gallery_images'], true) : [];
    }
    unset($exp);
} catch (Exception $e) {
    $all_experiences = [];
}

$currency = get_setting('currency_symbol', '₹');
$concierge_wa = get_setting('concierge_whatsapp', '919234567890');
$concierge_phone = get_setting('concierge_phone', '+91 923 456 7890');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Guest Hub | Food Forest Sanctuary Kanthalloor</title>

    <!-- Google Fonts: Cinzel, Cormorant Garamond, Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700;800&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
    /* =========================================================================
       Food Forest Sanctuary — Dedicated Resident Guest Executive Console
       Theme: Ultra-Luxury High-Range Emerald, Terracotta, Gold & Obsidian Slate
       ========================================================================= */
    :root {
        --adm-bg-main: #09130D;
        --adm-bg-surface: #101F15;
        --adm-bg-surface-elevated: #15291C;
        --adm-bg-card: #132419;
        --adm-bg-card-hover: #193122;
        --adm-bg-input: #0A160F;
        --adm-bg-glass: rgba(12, 24, 16, 0.94);

        --adm-gold: #C5A059;
        --adm-gold-light: #F0D59D;
        --adm-gold-bright: #FFDE9E;
        --adm-gold-dark: #987838;
        --adm-gold-dim: rgba(197, 160, 89, 0.14);
        --adm-gold-border: rgba(197, 160, 89, 0.28);
        --adm-gold-glow: 0 0 24px rgba(197, 160, 89, 0.28);
        --adm-gold-gradient: linear-gradient(135deg, #ECC880 0%, #C5A059 50%, #9D7B37 100%);
        --adm-gold-gradient-hover: linear-gradient(135deg, #F5DC9B 0%, #D8B26E 50%, #B59048 100%);

        --adm-emerald: #10B981;
        --adm-emerald-bright: #34D399;
        --adm-emerald-dim: rgba(16, 185, 129, 0.16);
        --adm-emerald-border: rgba(16, 185, 129, 0.38);

        --adm-amber: #F59E0B;
        --adm-amber-bright: #FBBF24;
        --adm-amber-dim: rgba(245, 158, 11, 0.16);

        --adm-terracotta: #EF4444;
        --adm-terracotta-bright: #F87171;
        --adm-terracotta-dim: rgba(239, 68, 68, 0.16);

        --adm-text-primary: #FFFFFF;
        --adm-text-secondary: #E2E8F0;
        --adm-text-muted: #94A3B8;
        --adm-text-gold: #C5A059;

        --adm-font-sans: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        --adm-font-title: 'Cinzel', serif;
        --adm-font-serif: 'Cormorant Garamond', Georgia, serif;

        --adm-sidebar-width: 260px;
        --adm-header-height: 68px;
        --adm-transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
    }

    *, *::before, *::after {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        background-color: var(--adm-bg-main);
        color: var(--adm-text-secondary);
        font-family: var(--adm-font-sans);
        font-size: 14px;
        line-height: 1.6;
        min-height: 100vh;
        background-image: 
            radial-gradient(circle at 12% 15%, rgba(16, 185, 129, 0.08) 0%, transparent 45%),
            radial-gradient(circle at 88% 85%, rgba(197, 160, 89, 0.07) 0%, transparent 45%);
        background-attachment: fixed;
        -webkit-font-smoothing: antialiased;
    }

    a {
        color: inherit;
        text-decoration: none;
        transition: var(--adm-transition);
    }

    button {
        font-family: inherit;
        cursor: pointer;
        border: none;
        outline: none;
        transition: var(--adm-transition);
    }

    input, select, textarea {
        font-family: inherit;
        color: inherit;
        box-sizing: border-box;
    }

    /* =========================================================================
       1. AUTHENTICATION SCREENS (When Logged Out)
       ========================================================================= */
    .guest-login-wrapper {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 30px 20px;
        position: relative;
    }

    .guest-login-card {
        width: 100%;
        max-width: 480px;
        background: var(--adm-bg-surface);
        border: 1px solid var(--adm-gold-border);
        border-radius: 18px;
        padding: 40px 34px;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6), var(--adm-gold-glow);
        backdrop-filter: blur(24px);
    }

    .guest-login-header {
        text-align: center;
        margin-bottom: 26px;
    }

    .guest-brand-emblem {
        width: 58px;
        height: 58px;
        margin: 0 auto 16px;
        background: linear-gradient(135deg, var(--adm-bg-surface-elevated), var(--adm-bg-main));
        border: 1px solid var(--adm-gold-border);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--adm-gold-light);
        font-size: 24px;
        box-shadow: var(--adm-gold-glow);
    }

    .guest-brand-title {
        font-family: var(--adm-font-title);
        font-size: 21px;
        letter-spacing: 2.5px;
        color: var(--adm-text-primary);
        font-weight: 700;
        margin-bottom: 4px;
    }

    .guest-brand-sub {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 3px;
        color: var(--adm-gold);
        font-weight: 600;
    }

    .auth-tabs-nav {
        display: flex;
        background: rgba(0, 0, 0, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 10px;
        padding: 4px;
        margin-bottom: 24px;
        gap: 4px;
    }

    .auth-tab-btn {
        flex: 1;
        padding: 9px 8px;
        background: transparent;
        color: var(--adm-text-muted);
        font-size: 12px;
        font-weight: 600;
        border-radius: 7px;
        text-align: center;
        transition: var(--adm-transition);
    }

    .auth-tab-btn:hover {
        color: var(--adm-text-primary);
    }

    .auth-tab-btn.active {
        background: var(--adm-gold-gradient);
        color: #08120B;
        font-weight: 700;
        box-shadow: 0 4px 12px rgba(197, 160, 89, 0.3);
    }

    .auth-pane {
        display: none;
    }

    .auth-pane.active {
        display: block;
        animation: fadeIn 0.2s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(4px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .guest-form-group {
        margin-bottom: 16px;
    }

    .guest-label {
        display: block;
        font-size: 11.5px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--adm-gold);
        font-weight: 600;
        margin-bottom: 6px;
    }

    .guest-input-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }

    .guest-input {
        width: 100%;
        padding: 12px 14px 12px 40px;
        background: var(--adm-bg-input);
        border: 1px solid rgba(197, 160, 89, 0.25);
        border-radius: 8px;
        color: #FFFFFF;
        font-size: 13.5px;
        outline: none;
        transition: var(--adm-transition);
    }

    .guest-input:focus {
        border-color: var(--adm-gold);
        background: rgba(10, 22, 15, 0.9);
        box-shadow: 0 0 10px rgba(197, 160, 89, 0.25);
    }

    .guest-input-icon {
        position: absolute;
        left: 14px;
        color: var(--adm-text-muted);
        font-size: 14px;
        pointer-events: none;
    }

    .guest-btn-submit {
        width: 100%;
        background: var(--adm-gold-gradient);
        color: #08120B;
        font-weight: 700;
        font-size: 13.5px;
        letter-spacing: 1px;
        padding: 13px 20px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        box-shadow: 0 6px 20px rgba(197, 160, 89, 0.3);
        margin-top: 10px;
    }

    .guest-btn-submit:hover {
        background: var(--adm-gold-gradient-hover);
        transform: translateY(-1px);
        box-shadow: 0 8px 25px rgba(197, 160, 89, 0.45);
    }

    .portal-alert {
        padding: 12px 14px;
        border-radius: 8px;
        font-size: 13px;
        margin-bottom: 20px;
        display: none;
        align-items: center;
        gap: 10px;
    }

    .portal-alert.error {
        background: var(--adm-terracotta-dim);
        border: 1px solid var(--adm-terracotta);
        color: var(--adm-terracotta-bright);
        display: flex;
    }

    .portal-alert.success {
        background: var(--adm-emerald-dim);
        border: 1px solid var(--adm-emerald);
        color: var(--adm-emerald-bright);
        display: flex;
    }

    /* =========================================================================
       2. FULL DASHBOARD LAYOUT (Left Sidebar & Scaled Viewport)
       ========================================================================= */
    .guest-app-layout {
        display: block;
        width: 100%;
        min-height: 100vh;
        position: relative;
    }

    /* Fixed Left Sidebar */
    .guest-sidebar {
        width: var(--adm-sidebar-width);
        background: #08100B;
        border-right: 1px solid rgba(197, 160, 89, 0.18);
        display: flex;
        flex-direction: column;
        position: fixed;
        top: 0;
        bottom: 0;
        left: 0;
        z-index: 100;
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .guest-sidebar-brand {
        padding: 18px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    }

    .guest-sidebar-logo {
        width: 38px;
        height: 38px;
        background: var(--adm-bg-surface-elevated);
        border: 1px solid var(--adm-gold-border);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--adm-gold-light);
        font-size: 17px;
        flex-shrink: 0;
    }

    .guest-sidebar-brand-text {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        overflow: hidden;
    }

    .guest-sidebar-title {
        font-family: var(--adm-font-title);
        font-size: 14px;
        letter-spacing: 1.5px;
        font-weight: 700;
        color: var(--adm-text-primary);
    }

    .guest-sidebar-badge {
        font-size: 9.5px;
        text-transform: uppercase;
        letter-spacing: 1.8px;
        color: var(--adm-gold);
        font-weight: 600;
    }

    .guest-sidebar-close-mob {
        display: none;
        background: transparent;
        color: var(--adm-text-muted);
        font-size: 18px;
        padding: 4px;
    }

    /* Active Cottage Quick Badge */
    .guest-active-cottage-chip {
        margin: 12px 12px 8px;
        padding: 12px;
        background: rgba(16, 31, 21, 0.85);
        border: 1px solid var(--adm-gold-border);
        border-radius: 10px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .cottage-chip-label {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--adm-gold);
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .cottage-chip-name {
        font-size: 13px;
        font-weight: 700;
        color: #FFFFFF;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .cottage-chip-meta {
        font-size: 11px;
        color: var(--adm-text-muted);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* Multi-Reservation Switcher Dropdown */
    .cottage-switcher-select {
        width: 100%;
        margin-top: 6px;
        padding: 5px 8px;
        background: #0A160F;
        border: 1px solid rgba(197, 160, 89, 0.35);
        border-radius: 6px;
        color: var(--adm-gold-light);
        font-size: 11px;
        outline: none;
    }

    /* Navigation List */
    .guest-nav-section {
        padding: 16px 16px 6px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: var(--adm-text-muted);
    }

    .guest-nav-list {
        list-style: none;
        padding: 0 10px;
        flex-grow: 1;
        overflow-y: auto;
    }

    .guest-nav-item {
        margin-bottom: 4px;
    }

    .guest-nav-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10.5px 14px;
        border-radius: 9px;
        color: var(--adm-text-secondary);
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: var(--adm-transition);
    }

    .guest-nav-link i {
        font-size: 15px;
        width: 20px;
        text-align: center;
        color: var(--adm-text-muted);
        transition: var(--adm-transition);
        flex-shrink: 0;
    }

    .guest-nav-link:hover {
        background: var(--adm-bg-surface);
        color: var(--adm-text-primary);
    }

    .guest-nav-link:hover i {
        color: var(--adm-gold-light);
    }

    .guest-nav-link.active {
        background: linear-gradient(90deg, rgba(197, 160, 89, 0.18), rgba(16, 185, 129, 0.12));
        border: 1px solid var(--adm-gold-border);
        color: #FFFFFF;
        font-weight: 600;
    }

    .guest-nav-link.active i {
        color: var(--adm-gold-light);
    }

    .guest-nav-badge {
        margin-left: auto;
        padding: 2px 7px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 700;
        background: var(--adm-gold);
        color: #08120B;
    }

    .guest-sidebar-footer {
        padding: 14px 14px;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
        background: rgba(6, 13, 8, 0.85);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .guest-avatar-thumb {
        width: 36px;
        height: 36px;
        border-radius: 9px;
        background: linear-gradient(135deg, #1A3825, #0E2215);
        border: 1px solid var(--adm-gold-border);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--adm-gold-light);
        font-weight: 700;
        font-size: 13px;
        flex-shrink: 0;
    }

    .guest-profile-meta {
        flex-grow: 1;
        overflow: hidden;
    }

    .guest-profile-name {
        font-size: 12.5px;
        font-weight: 600;
        color: var(--adm-text-primary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .guest-profile-badge {
        font-size: 10.5px;
        color: var(--adm-gold);
        display: flex;
        align-items: center;
        gap: 4px;
    }

    /* Main Viewport */
    .guest-main-viewport {
        margin-left: var(--adm-sidebar-width);
        width: calc(100% - var(--adm-sidebar-width));
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        transition: margin-left 0.25s ease, width 0.25s ease;
    }

    /* Persistent Topbar */
    .guest-topbar {
        height: var(--adm-header-height);
        background: var(--adm-bg-glass);
        backdrop-filter: blur(18px);
        border-bottom: 1px solid rgba(197, 160, 89, 0.16);
        padding: 0 28px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: sticky;
        top: 0;
        z-index: 90;
    }

    .guest-topbar-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .guest-mobile-toggle {
        display: none;
        background: transparent;
        color: var(--adm-text-primary);
        font-size: 20px;
        padding: 6px;
    }

    .guest-heading-title {
        font-family: var(--adm-font-title);
        font-size: 18px;
        font-weight: 700;
        color: var(--adm-text-primary);
        letter-spacing: 0.5px;
    }

    .guest-heading-sub {
        font-size: 11.5px;
        color: var(--adm-text-muted);
    }

    .guest-topbar-right {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    /* 5-Minute Inactivity Auto-Lock Countdown Pill */
    .guest-inactivity-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(16, 31, 21, 0.9);
        border: 1px solid rgba(197, 160, 89, 0.35);
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 11.5px;
        color: var(--adm-gold-light);
    }

    .guest-inactivity-pill strong {
        color: #34D399;
        font-family: monospace;
        font-size: 12px;
    }

    .btn-topbar-action {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 13px;
        border-radius: 7px;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: rgba(255, 255, 255, 0.05);
        color: var(--adm-text-secondary);
        transition: var(--adm-transition);
    }

    .btn-topbar-action:hover {
        background: rgba(255, 255, 255, 0.12);
        color: #FFFFFF;
    }

    .btn-topbar-wa {
        border-color: rgba(37, 211, 102, 0.4);
        background: rgba(37, 211, 102, 0.1);
        color: #25D366;
    }

    .btn-topbar-wa:hover {
        background: rgba(37, 211, 102, 0.2);
        color: #4ADE80;
    }

    /* Main Content Body */
    .guest-content-body {
        padding: 28px;
        flex-grow: 1;
    }

    /* Tab View Containers */
    .guest-view-pane {
        display: none;
        animation: fadeIn 0.25s ease-out;
    }

    .guest-view-pane.active {
        display: block;
    }

    /* Shared Dashboard Card Styles */
    .g-card {
        background: var(--adm-bg-surface);
        border: 1px solid rgba(197, 160, 89, 0.2);
        border-radius: 14px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
    }

    .g-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        padding-bottom: 14px;
        margin-bottom: 18px;
        flex-wrap: wrap;
        gap: 10px;
    }

    .g-card-title {
        font-family: var(--adm-font-title);
        font-size: 17px;
        color: var(--adm-gold);
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
    }

    /* Stats Grid */
    .g-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 18px;
        margin-bottom: 24px;
    }

    .g-stat-box {
        background: var(--adm-bg-surface);
        border: 1px solid rgba(197, 160, 89, 0.2);
        border-radius: 12px;
        padding: 18px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .g-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .g-stat-icon.villa { background: rgba(197, 160, 89, 0.15); color: #C5A059; border: 1px solid rgba(197, 160, 89, 0.3); }
    .g-stat-icon.guests { background: rgba(16, 185, 129, 0.15); color: #34D399; border: 1px solid rgba(16, 185, 129, 0.3); }
    .g-stat-icon.food { background: rgba(245, 158, 11, 0.15); color: #FBBF24; border: 1px solid rgba(245, 158, 11, 0.3); }
    .g-stat-icon.balance { background: rgba(56, 189, 248, 0.15); color: #38BDF8; border: 1px solid rgba(56, 189, 248, 0.3); }

    .g-stat-data {
        display: flex;
        flex-direction: column;
    }

    .g-stat-label {
        font-size: 11.5px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--adm-text-muted);
    }

    .g-stat-val {
        font-size: 20px;
        font-weight: 700;
        color: #FFFFFF;
        font-family: var(--adm-font-title);
    }

    /* Buttons & CTAs */
    .btn-gold-action {
        background: var(--adm-gold-gradient);
        color: #08120B;
        padding: 8px 16px;
        border-radius: 7px;
        font-weight: 700;
        font-size: 12.5px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        box-shadow: 0 4px 14px rgba(197, 160, 89, 0.25);
    }

    .btn-gold-action:hover {
        background: var(--adm-gold-gradient-hover);
        transform: translateY(-1px);
    }

    .btn-outline-action {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(197, 160, 89, 0.35);
        color: var(--adm-gold-light);
        padding: 8px 16px;
        border-radius: 7px;
        font-weight: 600;
        font-size: 12.5px;
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }

    .btn-outline-action:hover {
        background: rgba(197, 160, 89, 0.15);
        border-color: var(--adm-gold);
        color: #FFFFFF;
    }

    /* Dining & Tables */
    .g-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
    }

    .g-table th {
        text-align: left;
        color: var(--adm-text-muted);
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 10px 12px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .g-table td {
        padding: 12px 12px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        color: #E2E8F0;
    }

    .g-table tr:last-child td {
        border-bottom: none;
    }

    .status-badge-kitchen {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 9px;
        border-radius: 5px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .status-badge-kitchen.served { background: rgba(16, 185, 129, 0.15); color: #34D399; border: 1px solid rgba(16, 185, 129, 0.3); }
    .status-badge-kitchen.preparing { background: rgba(14, 116, 144, 0.2); color: #38BDF8; border: 1px solid rgba(14, 116, 144, 0.4); }
    .status-badge-kitchen.queued { background: rgba(245, 158, 11, 0.15); color: #FBBF24; border: 1px solid rgba(245, 158, 11, 0.3); }

    /* Menu Items Grid */
    .menu-filter-bar {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding-bottom: 10px;
        margin-bottom: 16px;
    }

    .menu-filter-pill {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        background: rgba(255, 255, 255, 0.05);
        color: #CBD5E1;
        border: 1px solid rgba(255, 255, 255, 0.12);
        cursor: pointer;
        white-space: nowrap;
        transition: var(--adm-transition);
    }

    .menu-filter-pill:hover, .menu-filter-pill.active {
        background: var(--adm-gold);
        color: #08120B;
        border-color: var(--adm-gold);
    }

    .menu-items-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 16px;
    }

    .menu-dish-card {
        background: var(--adm-bg-surface-elevated);
        border: 1px solid rgba(197, 160, 89, 0.2);
        border-radius: 10px;
        padding: 16px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: var(--adm-transition);
    }

    .menu-dish-card:hover {
        border-color: var(--adm-gold);
        transform: translateY(-2px);
    }

    /* Experiences Grid */
    .experiences-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
    }

    .exp-card {
        background: var(--adm-bg-surface);
        border: 1px solid rgba(197, 160, 89, 0.22);
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .exp-thumb {
        width: 100%;
        height: 190px;
        object-fit: cover;
    }

    .exp-body {
        padding: 18px;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        justify-content: space-between;
    }

    /* Floating Toast */
    #portal-toast-container {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .portal-toast {
        background: #101F15;
        color: #FFFFFF;
        border: 1.5px solid var(--adm-gold);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
        padding: 14px 20px;
        border-radius: 8px;
        font-size: 13.5px;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideInToast 0.3s ease;
    }

    @keyframes slideInToast {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    /* Floating Order Tray Bar */
    .guest-cart-bar {
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%);
        width: calc(100% - 48px);
        max-width: 760px;
        background: rgba(14, 28, 19, 0.96);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1.5px solid var(--adm-gold);
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.75), 0 0 24px rgba(197, 160, 89, 0.3);
        border-radius: 14px;
        padding: 12px 20px;
        z-index: 9990;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        animation: slideUpBar 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes slideUpBar {
        from { transform: translate(-50%, 100%); opacity: 0; }
        to { transform: translate(-50%, 0); opacity: 1; }
    }

    .cart-bar-info {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .cart-bar-badge {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: var(--adm-gold-gradient);
        color: #08120B;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 14px;
        gap: 4px;
        box-shadow: 0 4px 12px rgba(197, 160, 89, 0.4);
        flex-shrink: 0;
    }

    .cart-bar-actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .btn-cart-clear {
        background: transparent;
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: #94A3B8;
        padding: 9px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--adm-transition);
    }

    .btn-cart-clear:hover {
        color: #F87171;
        border-color: rgba(239, 68, 68, 0.4);
        background: rgba(239, 68, 68, 0.1);
    }

    .btn-cart-review {
        background: var(--adm-gold-gradient);
        color: #08120B;
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.5px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 15px rgba(197, 160, 89, 0.35);
        transition: var(--adm-transition);
        border: none;
    }

    .btn-cart-review:hover {
        background: var(--adm-gold-gradient-hover);
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(197, 160, 89, 0.5);
    }

    /* Modal Overlay & Card */
    .portal-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
        animation: fadeInOverlay 0.2s ease-out;
    }

    @keyframes fadeInOverlay {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .portal-modal-card {
        background: #0D1C12;
        border: 1.5px solid rgba(197, 160, 89, 0.4);
        border-radius: 16px;
        width: 100%;
        max-width: 680px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.85), 0 0 30px rgba(197, 160, 89, 0.2);
        animation: scaleUpModal 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        overflow: hidden;
    }

    @keyframes scaleUpModal {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    .portal-modal-header {
        padding: 18px 24px;
        border-bottom: 1px solid rgba(197, 160, 89, 0.2);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: rgba(0, 0, 0, 0.3);
    }

    .portal-modal-title {
        font-family: var(--adm-font-title);
        font-size: 18px;
        color: #FFFFFF;
        margin: 0 0 3px 0;
        letter-spacing: 0.5px;
    }

    .portal-modal-sub {
        font-size: 12px;
        color: #94A3B8;
        display: block;
    }

    .portal-modal-close {
        background: transparent;
        border: none;
        color: #94A3B8;
        font-size: 18px;
        cursor: pointer;
        padding: 6px;
        border-radius: 6px;
        transition: var(--adm-transition);
    }

    .portal-modal-close:hover {
        color: #FFFFFF;
        background: rgba(255, 255, 255, 0.1);
    }

    .portal-modal-body {
        padding: 20px 24px;
        overflow-y: auto;
        flex-grow: 1;
    }

    .portal-modal-footer {
        padding: 16px 24px;
        border-top: 1px solid rgba(197, 160, 89, 0.2);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: rgba(0, 0, 0, 0.3);
        gap: 12px;
        flex-wrap: wrap;
    }

    .btn-modal-cancel {
        background: transparent;
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: #CBD5E1;
        padding: 10px 18px;
        border-radius: 8px;
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--adm-transition);
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-modal-cancel:hover {
        background: rgba(255, 255, 255, 0.08);
        color: #FFFFFF;
    }

    .tray-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12.5px;
    }

    .tray-table th {
        text-align: left;
        color: var(--adm-gold);
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        padding: 8px 10px;
        border-bottom: 1px solid rgba(197, 160, 89, 0.2);
    }

    .tray-table td {
        padding: 12px 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        color: #E2E8F0;
        vertical-align: middle;
    }

    /* Mobile Responsive */
    @media (max-width: 992px) {
        .guest-sidebar {
            transform: translateX(-100%);
            box-shadow: 10px 0 30px rgba(0, 0, 0, 0.7);
        }

        .guest-sidebar.is-open {
            transform: translateX(0);
        }

        .guest-sidebar-close-mob {
            display: block;
        }

        .guest-main-viewport {
            margin-left: 0;
            width: 100%;
        }

        .guest-mobile-toggle {
            display: block;
        }

        .guest-content-body {
            padding: 16px;
        }
    }
    </style>
</head>
<body>

<?php if (!$is_user && !$is_guest): ?>
    <!-- ===================================================================== -->
    <!-- UN-AUTHENTICATED: LUXURY LOGIN, GUEST PASS & REGISTRATION SCREEN      -->
    <!-- ===================================================================== -->
    <div class="guest-login-wrapper">
        <div class="guest-login-card">
            
            <div class="guest-login-header">
                <div class="guest-brand-emblem">
                    <i class="fa-solid fa-seedling"></i>
                </div>
                <h1 class="guest-brand-title">FOOD FOREST</h1>
                <span class="guest-brand-sub">RESIDENT GUEST PORTAL</span>
            </div>

            <!-- Tab Selectors (Only 2 Tabs: Sign In & 30-Day Guest Pass) -->
            <div class="auth-tabs-nav font-sans" id="main-auth-tabs">
                <button type="button" class="auth-tab-btn active" data-target="pane-signin" onclick="showAuthView('signin')">Sign In</button>
                <button type="button" class="auth-tab-btn" data-target="pane-guest" onclick="showAuthView('guest')">30-Day Guest Pass</button>
            </div>

            <!-- Alert Box -->
            <div id="auth-alert-box" class="portal-alert <?php echo $timeout_notice ? 'error' : ''; ?>" style="<?php echo $timeout_notice ? 'display:flex;' : ''; ?>">
                <?php if ($timeout_notice): ?>
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Your session has expired due to 5 minutes of inactivity for security. Please sign in again.</span>
                <?php endif; ?>
            </div>

            <!-- 1. Permanent Member Sign In (Default Active) -->
            <div class="auth-pane active" id="pane-signin">
                <form id="form-portal-login" onsubmit="event.preventDefault(); submitLogin();">
                    <div class="guest-form-group">
                        <label for="login-email" class="guest-label">Registered Email</label>
                        <div class="guest-input-wrap">
                            <i class="fa-solid fa-envelope guest-input-icon"></i>
                            <input type="email" id="login-email" class="guest-input" required placeholder="guest@example.com" autocomplete="username">
                        </div>
                    </div>

                    <div class="guest-form-group">
                        <label for="login-password" class="guest-label">Password</label>
                        <div class="guest-input-wrap">
                            <i class="fa-solid fa-lock guest-input-icon"></i>
                            <input type="password" id="login-password" class="guest-input" required placeholder="••••••••••••" autocomplete="current-password">
                        </div>
                    </div>

                    <!-- Captcha Challenge -->
                    <div class="guest-form-group">
                        <label for="login-captcha" class="guest-label">Security Captcha Challenge *</label>
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                            <img class="guest-captcha-img" src="api/captcha.php?type=guest&v=<?php echo time(); ?>" alt="Captcha" style="height: 44px; border-radius: 6px; cursor: pointer;" onclick="refreshGuestCaptcha();" title="Click to refresh">
                            <button type="button" onclick="refreshGuestCaptcha();" style="padding: 10px 14px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #C5A059; border-radius: 6px;" title="Refresh Captcha">
                                <i class="fa-solid fa-arrows-rotate"></i>
                            </button>
                        </div>
                        <div class="guest-input-wrap">
                            <i class="fa-solid fa-shield-halved guest-input-icon"></i>
                            <input type="text" id="login-captcha" class="guest-input" required placeholder="Enter characters shown above" style="letter-spacing: 2px; font-weight: 700; text-transform: uppercase;">
                        </div>
                    </div>

                    <button type="submit" class="guest-btn-submit" id="btn-login-submit">
                        <span>Sign In to Food Forest Account</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>

                    <!-- Links below Login Button -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; font-size: 12.5px;">
                        <button type="button" onclick="showAuthView('forgot')" style="background: none; border: none; color: var(--adm-gold); cursor: pointer; text-decoration: underline; font-weight: 600; padding: 0;">
                            Forgot Password?
                        </button>
                        <button type="button" onclick="showAuthView('signup')" style="background: none; border: none; color: var(--adm-gold); cursor: pointer; text-decoration: underline; font-weight: 600; padding: 0;">
                            Create New Account
                        </button>
                    </div>
                </form>
            </div>

            <!-- 2. 30-Day Guest Pass Access -->
            <div class="auth-pane" id="pane-guest">
                <form id="form-portal-guest" onsubmit="event.preventDefault(); submitGuestAccess();">
                    <div class="guest-form-group">
                        <label for="guest-ref" class="guest-label">Reservation Reference / Guest ID</label>
                        <div class="guest-input-wrap">
                            <i class="fa-solid fa-receipt guest-input-icon"></i>
                            <input type="text" id="guest-ref" class="guest-input" required placeholder="e.g. FF-4829" value="<?php echo htmlspecialchars($prefill_ref); ?>" style="text-transform: uppercase; font-weight: 600;">
                        </div>
                    </div>

                    <div class="guest-form-group">
                        <label for="guest-passcode" class="guest-label">Access Passcode or Phone Number</label>
                        <div class="guest-input-wrap">
                            <i class="fa-solid fa-key guest-input-icon"></i>
                            <input type="text" id="guest-passcode" class="guest-input" required placeholder="4-digit Passcode or WhatsApp Phone">
                        </div>
                    </div>

                    <!-- Captcha Challenge -->
                    <div class="guest-form-group">
                        <label for="guest-captcha" class="guest-label">Security Captcha Challenge *</label>
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                            <img class="guest-captcha-img" src="api/captcha.php?type=guest&v=<?php echo time(); ?>" alt="Captcha" style="height: 44px; border-radius: 6px; cursor: pointer;" onclick="refreshGuestCaptcha();" title="Click to refresh">
                            <button type="button" onclick="refreshGuestCaptcha();" style="padding: 10px 14px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #C5A059; border-radius: 6px;" title="Refresh Captcha">
                                <i class="fa-solid fa-arrows-rotate"></i>
                            </button>
                        </div>
                        <div class="guest-input-wrap">
                            <i class="fa-solid fa-shield-halved guest-input-icon"></i>
                            <input type="text" id="guest-captcha" class="guest-input" required placeholder="Enter characters shown above" style="letter-spacing: 2px; font-weight: 700; text-transform: uppercase;">
                        </div>
                    </div>

                    <button type="submit" class="guest-btn-submit" id="btn-guest-submit">
                        <span>Access My Reservation</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>
            </div>

            <!-- 3. New Account Registration View -->
            <div class="auth-pane" id="pane-signup">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                    <button type="button" onclick="showAuthView('signin')" style="background: none; border: none; color: var(--adm-text-muted); cursor: pointer; font-size: 12.5px; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-arrow-left"></i> Back to Sign In
                    </button>
                    <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: var(--adm-gold); font-weight: 700;">Account Registration</span>
                </div>

                <form id="form-portal-register" onsubmit="event.preventDefault(); submitRegister();">
                    <div class="guest-form-group">
                        <label for="reg-name" class="guest-label">Full Name *</label>
                        <div class="guest-input-wrap">
                            <i class="fa-solid fa-user guest-input-icon"></i>
                            <input type="text" id="reg-name" class="guest-input" required placeholder="e.g. Ananya Nair">
                        </div>
                    </div>

                    <div class="guest-form-group">
                        <label for="reg-email" class="guest-label">Email Address *</label>
                        <div class="guest-input-wrap">
                            <i class="fa-solid fa-envelope guest-input-icon"></i>
                            <input type="email" id="reg-email" class="guest-input" required placeholder="ananya@example.com">
                        </div>
                    </div>

                    <!-- Optional Google Email Verification Box -->
                    <div class="google-verify-card" id="reg-google-verify-card" style="background: rgba(197, 160, 89, 0.05); border: 1px dashed rgba(197, 160, 89, 0.35); border-radius: 10px; padding: 14px; margin-bottom: 18px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 8px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <i class="fa-brands fa-google" style="color: #EA4335; font-size: 15px;"></i>
                                <strong style="color: #FFFFFF; font-size: 13px;">Google Verification</strong>
                            </div>
                            <span id="reg-verify-badge-status" style="font-size: 11px; background: rgba(255,255,255,0.08); color: var(--adm-text-muted); padding: 2px 8px; border-radius: 20px; font-weight: 600;">Optional</span>
                        </div>
                        <p style="font-size: 12px; color: var(--adm-text-secondary); line-height: 1.5; margin: 0 0 10px 0;">
                            Verify your email to unlock the prestigious <strong style="color: #38BDF8;"><i class="fa-solid fa-circle-check"></i> Blue Tick VIP Badge</strong> on your profile for VIP concierge priority, express check-in, and premium consideration.
                        </p>
                        
                        <div id="reg-verify-action-box">
                            <button type="button" id="btn-reg-send-otp" onclick="requestRegOtp()" style="padding: 7px 12px; background: rgba(56, 189, 248, 0.12); border: 1px solid rgba(56, 189, 248, 0.35); color: #38BDF8; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-paper-plane"></i> Send 5-Digit Verification Code
                            </button>
                        </div>

                        <div id="reg-verify-otp-box" style="display: none; margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.08);">
                            <div style="font-size: 11.5px; color: var(--adm-gold-light); margin-bottom: 6px;">
                                Enter 5-digit code sent to <span id="reg-verify-target-email" style="font-weight:700;"></span>:
                            </div>
                            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                <input type="text" id="reg-otp-code-input" maxlength="5" placeholder="• • • • •" style="width: 130px; text-align: center; font-size: 18px; font-weight: 700; letter-spacing: 6px; font-family: monospace; padding: 6px 10px; background: var(--adm-bg-input); border: 1px solid var(--adm-gold); border-radius: 6px; color: #FFFFFF;">
                                <button type="button" id="btn-reg-verify-otp" onclick="verifyRegOtp()" style="padding: 8px 14px; background: var(--adm-gold); color: #08120B; font-weight: 700; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;">
                                    Verify
                                </button>
                                <button type="button" onclick="cancelRegOtp()" style="background: none; border: none; color: var(--adm-text-muted); font-size: 12px; cursor: pointer;">Cancel</button>
                            </div>
                            <div id="reg-dev-notice" style="display:none; font-size:11px; color:#34D399; margin-top:6px; cursor:pointer;" onclick="autofillRegDevOtp()"></div>
                        </div>

                        <div id="reg-verify-success-box" style="display: none; align-items: center; gap: 8px; color: #38BDF8; font-size: 12.5px; font-weight: 700;">
                            <i class="fa-solid fa-circle-check" style="font-size: 16px;"></i>
                            <span>Email Verified! Blue Tick VIP Badge unlocked for your account.</span>
                        </div>
                    </div>

                    <div class="guest-form-group">
                        <label for="reg-phone" class="guest-label">WhatsApp Contact *</label>
                        <div class="guest-input-wrap">
                            <i class="fa-brands fa-whatsapp guest-input-icon"></i>
                            <input type="tel" id="reg-phone" class="guest-input" required placeholder="+91 98765 43210">
                        </div>
                    </div>

                    <div class="guest-form-group">
                        <label for="reg-password" class="guest-label">Create Password *</label>
                        <div class="guest-input-wrap">
                            <i class="fa-solid fa-lock guest-input-icon"></i>
                            <input type="password" id="reg-password" class="guest-input" required placeholder="Minimum 6 characters">
                        </div>
                    </div>

                    <!-- Captcha Challenge -->
                    <div class="guest-form-group">
                        <label for="reg-captcha" class="guest-label">Security Captcha Challenge *</label>
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                            <img class="guest-captcha-img" src="api/captcha.php?type=guest&v=<?php echo time(); ?>" alt="Captcha" style="height: 44px; border-radius: 6px; cursor: pointer;" onclick="refreshGuestCaptcha();" title="Click to refresh">
                            <button type="button" onclick="refreshGuestCaptcha();" style="padding: 10px 14px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #C5A059; border-radius: 6px;" title="Refresh Captcha">
                                <i class="fa-solid fa-arrows-rotate"></i>
                            </button>
                        </div>
                        <div class="guest-input-wrap">
                            <i class="fa-solid fa-shield-halved guest-input-icon"></i>
                            <input type="text" id="reg-captcha" class="guest-input" required placeholder="Enter characters shown above" style="letter-spacing: 2px; font-weight: 700; text-transform: uppercase;">
                        </div>
                    </div>

                    <button type="submit" class="guest-btn-submit" id="btn-reg-submit">
                        <span>Create Food Forest Account</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>

                    <div style="text-align: center; margin-top: 14px;">
                        <button type="button" onclick="showAuthView('signin')" style="background: none; border: none; color: var(--adm-gold); cursor: pointer; font-size: 12.5px; text-decoration: underline;">
                            Already have an account? Sign In
                        </button>
                    </div>
                </form>
            </div>

            <!-- 4. Forgot Password View -->
            <div class="auth-pane" id="pane-forgot">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                    <button type="button" onclick="showAuthView('signin')" style="background: none; border: none; color: var(--adm-text-muted); cursor: pointer; font-size: 12.5px; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-arrow-left"></i> Back to Sign In
                    </button>
                    <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: var(--adm-gold); font-weight: 700;">Password Recovery</span>
                </div>

                <!-- Step 1: Request Code -->
                <div id="forgot-step-1">
                    <p style="font-size: 13px; color: var(--adm-text-secondary); margin-bottom: 18px; line-height: 1.5;">
                        Enter your registered Food Forest email address. We will send a 5-digit verification code to reset your password.
                    </p>
                    <form id="form-forgot-request" onsubmit="event.preventDefault(); submitForgotSendOtp();">
                        <div class="guest-form-group">
                            <label for="forgot-email" class="guest-label">Registered Email</label>
                            <div class="guest-input-wrap">
                                <i class="fa-solid fa-envelope guest-input-icon"></i>
                                <input type="email" id="forgot-email" class="guest-input" required placeholder="guest@example.com" autocomplete="email">
                            </div>
                        </div>

                        <!-- Captcha Challenge -->
                        <div class="guest-form-group">
                            <label for="forgot-captcha" class="guest-label">Security Captcha Challenge *</label>
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                <img class="guest-captcha-img" src="api/captcha.php?type=guest&v=<?php echo time(); ?>" alt="Captcha" style="height: 44px; border-radius: 6px; cursor: pointer;" onclick="refreshGuestCaptcha();" title="Click to refresh">
                                <button type="button" onclick="refreshGuestCaptcha();" style="padding: 10px 14px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #C5A059; border-radius: 6px;" title="Refresh Captcha">
                                    <i class="fa-solid fa-arrows-rotate"></i>
                                </button>
                            </div>
                            <div class="guest-input-wrap">
                                <i class="fa-solid fa-shield-halved guest-input-icon"></i>
                                <input type="text" id="forgot-captcha" class="guest-input" required placeholder="Enter characters shown above" style="letter-spacing: 2px; font-weight: 700; text-transform: uppercase;">
                            </div>
                        </div>

                        <button type="submit" class="guest-btn-submit" id="btn-forgot-request">
                            <span>Send 5-Digit Verification Code</span>
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </form>
                </div>

                <!-- Step 2: Reset Password with OTP -->
                <div id="forgot-step-2" style="display: none;">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <div style="width: 50px; height: 50px; margin: 0 auto 10px; border-radius: 50%; background: rgba(197, 160, 89, 0.15); border: 1px solid var(--adm-gold); display: flex; align-items: center; justify-content: center; color: var(--adm-gold-light); font-size: 20px;">
                            <i class="fa-solid fa-key"></i>
                        </div>
                        <div style="font-size: 15px; font-weight: 700; color: #FFFFFF; font-family: var(--adm-font-title); letter-spacing: 1px;">SET NEW PASSWORD</div>
                        <p style="font-size: 12.5px; color: var(--adm-text-muted); margin-top: 4px;">
                            Verification code dispatched to <strong id="forgot-display-email" style="color: var(--adm-gold-light);"></strong>
                        </p>
                    </div>

                    <form id="form-forgot-reset" onsubmit="event.preventDefault(); submitForgotReset();">
                        <div class="guest-form-group">
                            <label for="forgot-otp-input" class="guest-label" style="text-align: center;">Enter 5-Digit Code *</label>
                            <div class="guest-input-wrap">
                                <input type="text" id="forgot-otp-input" class="guest-input" maxlength="5" placeholder="• • • • •" style="text-align: center; font-size: 24px; font-weight: 800; letter-spacing: 12px; font-family: monospace; color: var(--adm-gold-bright); height: 52px;" required pattern="\d{5}">
                            </div>
                        </div>

                        <div id="forgot-dev-notice" style="display: none; margin: 10px 0; padding: 8px 12px; background: rgba(16, 185, 129, 0.1); border: 1px dashed var(--adm-emerald); border-radius: 6px; font-size: 12px; color: var(--adm-emerald-bright); text-align: center; cursor: pointer;" onclick="autofillForgotDevOtp()"></div>

                        <div class="guest-form-group">
                            <label for="forgot-new-password" class="guest-label">New Password *</label>
                            <div class="guest-input-wrap">
                                <i class="fa-solid fa-lock guest-input-icon"></i>
                                <input type="password" id="forgot-new-password" class="guest-input" required placeholder="Minimum 6 characters">
                            </div>
                        </div>

                        <button type="submit" class="guest-btn-submit" id="btn-forgot-reset">
                            <span>Reset Password &amp; Sign In</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; font-size: 12px;">
                            <button type="button" id="btn-forgot-resend" onclick="submitForgotSendOtp(true)" style="background: none; border: none; color: var(--adm-gold); cursor: pointer; text-decoration: underline;">Resend Code</button>
                            <button type="button" onclick="showAuthView('signin')" style="background: none; border: none; color: var(--adm-text-muted); cursor: pointer;">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div style="text-align: center; margin-top: 24px;">
                <a href="index.php" style="color: var(--adm-text-muted); font-size: 12.5px;">
                    <i class="fa-solid fa-arrow-left"></i> Return to Main Food Forest Website
                </a>
            </div>

        </div>
    </div>

<?php else: ?>
    <!-- ===================================================================== -->
    <!-- AUTHENTICATED: ULTRA-LUXURY EXECUTIVE GUEST DASHBOARD                -->
    <!-- ===================================================================== -->
    <div class="guest-app-layout">

        <!-- 1. LEFT SIDEBAR NAVIGATION (Mirroring Admin Dashboard) -->
        <aside class="guest-sidebar" id="guest-sidebar">
            <!-- Brand Emblem & Title -->
            <div class="guest-sidebar-brand">
                <div class="guest-sidebar-logo">
                    <i class="fa-solid fa-seedling"></i>
                </div>
                <div class="guest-sidebar-brand-text">
                    <span class="guest-sidebar-title">FOOD FOREST</span>
                    <span class="guest-sidebar-badge">RESIDENT GUEST HUB</span>
                </div>
                <button type="button" class="guest-sidebar-close-mob" id="guest-sidebar-close-mob" onclick="toggleGuestSidebar()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Active Cottage Chip & Switcher -->
            <?php if ($active_booking): ?>
                <div class="guest-active-cottage-chip">
                    <div class="cottage-chip-label">
                        <span><i class="fa-solid fa-house-chimney" style="color:#C5A059;"></i> Active Cottage</span>
                        <span style="color:#10B981; font-weight:700;"><i class="fa-solid fa-circle-dot"></i> LIVE</span>
                    </div>
                    <div class="cottage-chip-name" title="<?php echo htmlspecialchars($b_title); ?>">
                        <?php echo htmlspecialchars($b_title); ?>
                    </div>
                    <div class="cottage-chip-meta">
                        <span>#<?php echo $b_ref; ?></span>
                        <span>•</span>
                        <span><?php echo (int)$active_booking['nights']; ?> Night<?php echo $active_booking['nights'] > 1 ? 's' : ''; ?></span>
                    </div>

                    <?php if (count($bookings_list) > 1): ?>
                        <select class="cottage-switcher-select font-sans" onchange="window.location.href='guest_portal.php?booking_id=' + this.value;">
                            <?php foreach ($bookings_list as $b_opt): ?>
                                <option value="<?php echo $b_opt['id']; ?>" <?php echo (int)$b_opt['id'] === $active_booking_id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($b_opt['room_title'] ?? $b_opt['villa_type']); ?> (Ref: #<?php echo htmlspecialchars($b_opt['reference_code']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Navigation Sections -->
            <div class="guest-nav-section">Sanctuary Residency</div>
            <ul class="guest-nav-list font-sans">
                
                <li class="guest-nav-item">
                    <a class="guest-nav-link active" data-view="dashboard" onclick="switchGuestTab('dashboard')">
                        <i class="fa-solid fa-compass"></i>
                        <span>Stay Dashboard</span>
                    </a>
                </li>

                <li class="guest-nav-item">
                    <a class="guest-nav-link" data-view="my_villa" onclick="switchGuestTab('my_villa')">
                        <i class="fa-solid fa-hotel"></i>
                        <span>My Villa &amp; Details</span>
                    </a>
                </li>

                <li class="guest-nav-item">
                    <a class="guest-nav-link" data-view="kitchen" onclick="switchGuestTab('kitchen')">
                        <i class="fa-solid fa-kitchen-set"></i>
                        <span>Our Kitchen</span>
                        <?php if ($b_food_count > 0): ?>
                            <span class="guest-nav-badge" id="nav-food-count-badge"><?php echo $b_food_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <li class="guest-nav-item">
                    <a class="guest-nav-link" data-view="menu" onclick="switchGuestTab('menu')">
                        <i class="fa-solid fa-utensils"></i>
                        <span>Our Farm Menu</span>
                    </a>
                </li>

                <li class="guest-nav-item">
                    <a class="guest-nav-link" data-view="experiences" onclick="switchGuestTab('experiences')">
                        <i class="fa-solid fa-sparkles"></i>
                        <span>Other Experiences</span>
                    </a>
                </li>

                <li class="guest-nav-item">
                    <a class="guest-nav-link" data-view="folio" onclick="switchGuestTab('folio')">
                        <i class="fa-solid fa-receipt"></i>
                        <span>Payable Folio &amp; Bills</span>
                    </a>
                </li>

                <div class="guest-nav-section" style="margin-top: 14px;">Concierge &amp; Account</div>

                <li class="guest-nav-item">
                    <a href="https://wa.me/<?php echo htmlspecialchars($concierge_wa); ?>?text=Hello%20Concierge,%20I%20am%20residing%20at%20<?php echo urlencode($b_title); ?>%20(Ref:%20<?php echo urlencode($b_ref); ?>)." target="_blank" class="guest-nav-link" style="color: #4ADE80;">
                        <i class="fa-brands fa-whatsapp" style="color: #25D366;"></i>
                        <span>Master Concierge</span>
                    </a>
                </li>

                <li class="guest-nav-item">
                    <a onclick="portalLogout()" class="guest-nav-link" style="color: #F87171;">
                        <i class="fa-solid fa-right-from-bracket" style="color: #EF4444;"></i>
                        <span>Sign Out</span>
                    </a>
                </li>
            </ul>

            <!-- Sidebar Footer Profile -->
            <div class="guest-sidebar-footer">
                <div class="guest-avatar-thumb">
                    <?php 
                    $disp_name = $is_user ? $current_user['full_name'] : ($guest_booking['guest_name'] ?? 'Resident Guest');
                    echo strtoupper(substr($disp_name, 0, 1)); 
                    ?>
                </div>
                <div class="guest-profile-meta">
                    <div class="guest-profile-name" title="<?php echo htmlspecialchars($disp_name); ?>">
                        <?php echo htmlspecialchars($disp_name); ?>
                        <?php if ($is_user && !empty($current_user['is_google_verified'])): ?>
                            <i class="fa-solid fa-circle-check" style="color: #38BDF8; margin-left: 5px; font-size: 13px;" title="Google Verified VIP Resident"></i>
                        <?php endif; ?>
                    </div>
                    <div class="guest-profile-badge">
                        <?php if ($is_user): ?>
                            <i class="fa-solid fa-crown" style="font-size: 10px;"></i> Permanent Member
                            <?php if (!empty($current_user['is_google_verified'])): ?>
                                <span style="color: #38BDF8; margin-left: 3px; font-weight: 700;">• <i class="fa-solid fa-shield-halved" style="font-size: 9px;"></i> Verified</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <i class="fa-solid fa-key" style="font-size: 10px;"></i> 30-Day Guest Pass
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </aside>

        <!-- 2. MAIN VIEWPORT -->
        <div class="guest-main-viewport">
            
            <!-- Persistent Topbar -->
            <header class="guest-topbar">
                <div class="guest-topbar-left">
                    <button type="button" class="guest-mobile-toggle" onclick="toggleGuestSidebar()" aria-label="Toggle Navigation">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div>
                        <h2 class="guest-heading-title" id="topbar-view-title">Stay Dashboard</h2>
                        <span class="guest-heading-sub" id="topbar-view-sub">Kanthalloor High Range • 1,600m Elevation</span>
                    </div>
                </div>

                <div class="guest-topbar-right">
                    <!-- 5-Minute Inactivity Auto-Lock Pill -->
                    <div class="guest-inactivity-pill" title="For your security, session auto-locks after 5 minutes of inactivity">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <span>Auto-Lock: </span>
                        <strong id="inactivity-countdown">05:00</strong>
                    </div>

                    <!-- Direct WhatsApp Concierge Button -->
                    <a href="https://wa.me/<?php echo htmlspecialchars($concierge_wa); ?>?text=Hello%20Concierge,%20resident%20enquiring%20about%20booking%20<?php echo urlencode($b_ref); ?>" target="_blank" class="btn-topbar-action btn-topbar-wa">
                        <i class="fa-brands fa-whatsapp"></i>
                        <span class="adm-hide-mob">Concierge</span>
                    </a>

                    <!-- Return to public site -->
                    <a href="index.php" class="btn-topbar-action adm-hide-mob" title="Visit Public Sanctuary Website">
                        <i class="fa-solid fa-house"></i>
                        <span>Main Site</span>
                    </a>

                    <!-- Sign Out Button -->
                    <button type="button" onclick="portalLogout()" class="btn-topbar-action" style="color: #F87171;" title="Sign out securely">
                        <i class="fa-solid fa-power-off"></i>
                    </button>
                </div>
            </header>

            <!-- Main Content Panes -->
            <main class="guest-content-body font-sans">

                <?php if (empty($bookings_list)): ?>
                    <!-- Zero Bookings State -->
                    <div class="g-card" style="text-align: center; padding: 60px 20px;">
                        <i class="fa-regular fa-calendar-xmark" style="font-size: 42px; color: #C5A059; margin-bottom: 16px; display: block;"></i>
                        <h3 class="font-serif" style="font-size: 22px; color: #FFFFFF; margin-bottom: 8px;">No Reservations Found</h3>
                        <p style="color: #94A3B8; max-width: 480px; margin: 0 auto 24px;">You do not currently have any active villa reservations under this account. Explore our canopy treehouses and earthen mudhouse suites to reserve your escape.</p>
                        <a href="index.php#rooms-experience" class="btn-gold-action">
                            <i class="fa-solid fa-compass"></i> Explore Villas &amp; Reserve
                        </a>
                    </div>

                <?php else: ?>

                    <!-- ========================================================= -->
                    <!-- VIEW 1: STAY DASHBOARD (Overview)                         -->
                    <!-- ========================================================= -->
                    <section class="guest-view-pane active" id="view-dashboard">
                        
                        <!-- 30-Day Temporary Pass Upgrade Banner if applicable -->
                        <?php if ($is_guest && !empty($guest_booking)): ?>
                            <div style="background: linear-gradient(90deg, rgba(180, 83, 9, 0.25), rgba(16, 31, 21, 0.9)); border: 1px solid rgba(245, 158, 11, 0.4); border-radius: 12px; padding: 16px 22px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                                <div>
                                    <h4 class="font-serif" style="color: #FBBF24; font-size: 16px; margin-bottom: 2px;">
                                        <i class="fa-solid fa-hourglass-half"></i> 30-Day Guest Access Pass
                                    </h4>
                                    <p style="font-size: 12.5px; color: #D1D5DB; margin: 0;">
                                        This reservation pass will expire on <strong><?php echo !empty($guest_booking['expires_at']) ? date('d M Y', strtotime($guest_booking['expires_at'])) : '30 days from booking'; ?></strong>. Convert to a permanent account to keep your stay history indefinitely.
                                    </p>
                                </div>
                                <button type="button" onclick="promptUpgradeAccount('<?php echo htmlspecialchars($guest_booking['reference_code']); ?>')" class="btn-gold-action">
                                    <i class="fa-solid fa-crown"></i> Convert to Free Account
                                </button>
                            </div>
                        <?php endif; ?>

                        <!-- Quick Glance KPI Stats -->
                        <div class="g-stats-grid">
                            <div class="g-stat-box">
                                <div class="g-stat-icon villa"><i class="fa-solid fa-house-chimney"></i></div>
                                <div class="g-stat-data">
                                    <span class="g-stat-label">Chalet &amp; Stay</span>
                                    <span class="g-stat-val" style="font-size: 16px;"><?php echo (int)$active_booking['nights']; ?> Night<?php echo $active_booking['nights'] > 1 ? 's' : ''; ?></span>
                                    <span style="font-size: 11px; color: #10B981; font-weight: 600;"><i class="fa-solid fa-circle-check"></i> <?php echo ucfirst($active_booking['status'] ?? 'confirmed'); ?></span>
                                </div>
                            </div>

                            <div class="g-stat-box">
                                <div class="g-stat-icon guests"><i class="fa-solid fa-users"></i></div>
                                <div class="g-stat-data">
                                    <span class="g-stat-label">Occupancy</span>
                                    <span class="g-stat-val"><?php echo (int)($active_booking['adults_count'] ?? 2); ?> Adults</span>
                                    <span style="font-size: 11px; color: #94A3B8;"><?php echo !empty($active_booking['kids_count']) ? (int)$active_booking['kids_count'] . ' Children' : '0 Children'; ?></span>
                                </div>
                            </div>

                            <div class="g-stat-box">
                                <div class="g-stat-icon food"><i class="fa-solid fa-utensils"></i></div>
                                <div class="g-stat-data">
                                    <span class="g-stat-label">In-Cottage Dining</span>
                                    <span class="g-stat-val" id="kpi-food-total"><?php echo $currency . number_format($food_val, 2); ?></span>
                                    <span style="font-size: 11px; color: #FBBF24;" id="kpi-food-count"><?php echo $b_food_count; ?> Dishes Ordered</span>
                                </div>
                            </div>

                            <div class="g-stat-box">
                                <div class="g-stat-icon balance"><i class="fa-solid fa-wallet"></i></div>
                                <div class="g-stat-data">
                                    <span class="g-stat-label">Payable Balance</span>
                                    <span class="g-stat-val" id="kpi-bal-val" style="color: <?php echo $bal_val <= 0 ? '#34D399' : '#FBBF24'; ?>;">
                                        <?php echo $currency . number_format($bal_val, 2); ?>
                                    </span>
                                    <span style="font-size: 11px; font-weight: 700; color: <?php echo $bal_val <= 0 ? '#34D399' : '#F59E0B'; ?>;" id="kpi-bal-status">
                                        <?php echo $bal_val <= 0 ? '✓ SETTLED IN FULL' : 'DUE AT CHECKOUT'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Control Action Tiles -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 24px;">
                            <div class="g-card" style="margin-bottom: 0; padding: 18px; cursor: pointer; transition: all 0.2s;" onclick="switchGuestTab('my_villa')">
                                <div style="display: flex; align-items: center; gap: 14px;">
                                    <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(197, 160, 89, 0.15); color: #C5A059; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                        <i class="fa-solid fa-hotel"></i>
                                    </div>
                                    <div>
                                        <h4 style="font-size: 14px; font-weight: 700; color: #FFFFFF; margin-bottom: 2px;">My Villa &amp; Details</h4>
                                        <p style="font-size: 11.5px; color: #94A3B8; margin: 0;">Amenities, photos &amp; Stay Bill PDF</p>
                                    </div>
                                </div>
                            </div>

                            <div class="g-card" style="margin-bottom: 0; padding: 18px; cursor: pointer; transition: all 0.2s;" onclick="switchGuestTab('kitchen')">
                                <div style="display: flex; align-items: center; gap: 14px;">
                                    <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(245, 158, 11, 0.15); color: #F59E0B; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                        <i class="fa-solid fa-kitchen-set"></i>
                                    </div>
                                    <div>
                                        <h4 style="font-size: 14px; font-weight: 700; color: #FFFFFF; margin-bottom: 2px;">Our Kitchen Orders</h4>
                                        <p style="font-size: 11.5px; color: #94A3B8; margin: 0;">Live chef prep &amp; Food Bill PDF</p>
                                    </div>
                                </div>
                            </div>

                            <div class="g-card" style="margin-bottom: 0; padding: 18px; cursor: pointer; transition: all 0.2s;" onclick="switchGuestTab('menu')">
                                <div style="display: flex; align-items: center; gap: 14px;">
                                    <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(16, 185, 129, 0.15); color: #34D399; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                        <i class="fa-solid fa-utensils"></i>
                                    </div>
                                    <div>
                                        <h4 style="font-size: 14px; font-weight: 700; color: #FFFFFF; margin-bottom: 2px;">Order Farm Dishes</h4>
                                        <p style="font-size: 11.5px; color: #94A3B8; margin: 0;">Browse 75 dishes to cottage</p>
                                    </div>
                                </div>
                            </div>

                            <div class="g-card" style="margin-bottom: 0; padding: 18px; cursor: pointer; transition: all 0.2s;" onclick="switchGuestTab('folio')">
                                <div style="display: flex; align-items: center; gap: 14px;">
                                    <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(56, 189, 248, 0.15); color: #38BDF8; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                        <i class="fa-solid fa-receipt"></i>
                                    </div>
                                    <div>
                                        <h4 style="font-size: 14px; font-weight: 700; color: #FFFFFF; margin-bottom: 2px;">Payable Folio &amp; Bills</h4>
                                        <p style="font-size: 11.5px; color: #94A3B8; margin: 0;">Itemized cart &amp; tax invoice PDF</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dual Column Stay Hub -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 24px;">
                            
                            <!-- Left: Cottage Dining Schedule Summary -->
                            <div class="g-card" style="margin-bottom: 0;">
                                <div class="g-card-header">
                                    <h3 class="g-card-title"><i class="fa-solid fa-plate-wheat"></i> Today's Cottage Dining</h3>
                                    <button type="button" onclick="switchGuestTab('menu')" class="btn-gold-action" style="padding: 5px 12px; font-size: 11.5px;">
                                        <i class="fa-solid fa-plus"></i> Order More
                                    </button>
                                </div>

                                <div id="dash-ordered-summary-box">
                                    <?php if (!empty($f_items)): ?>
                                        <table class="g-table">
                                            <thead>
                                                <tr>
                                                    <th>Dish</th>
                                                    <th>Meal Time</th>
                                                    <th style="text-align: center;">Qty</th>
                                                    <th style="text-align: right;">Price</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (array_slice($f_items, 0, 5) as $s_item): 
                                                    $s_name = $s_item['heading'] ?? 'Custom Farm Meal';
                                                    $s_slot = ucfirst($s_item['meal_time'] ?? ($s_item['category'] ?? 'Dining'));
                                                    $s_qty = (int)($s_item['quantity'] ?? 1);
                                                    $s_price = (float)($s_item['price'] ?? 0);
                                                ?>
                                                    <tr>
                                                        <td><strong style="color: #FFFFFF;"><?php echo htmlspecialchars($s_name); ?></strong></td>
                                                        <td><span style="font-size: 11px; color: #94A3B8; background: rgba(255,255,255,0.06); padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($s_slot); ?></span></td>
                                                        <td style="text-align: center;"><?php echo $s_qty; ?></td>
                                                        <td style="text-align: right; color: #C5A059; font-weight: 700;"><?php echo $currency . number_format($s_qty * $s_price, 2); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <?php if (count($f_items) > 5): ?>
                                            <div style="text-align: center; margin-top: 12px;">
                                                <button type="button" onclick="switchGuestTab('kitchen')" style="background: transparent; color: #C5A059; font-size: 12px; font-weight: 600;">
                                                    View all <?php echo count($f_items); ?> dishes in Kitchen &rarr;
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div style="text-align: center; padding: 30px 10px; color: #94A3B8;">
                                            <i class="fa-solid fa-bowl-food" style="font-size: 28px; color: #C5A059; margin-bottom: 8px; display: block;"></i>
                                            <p style="margin: 0; font-size: 13px;">No food orders placed yet for this stay.</p>
                                            <button type="button" onclick="switchGuestTab('menu')" class="btn-outline-action" style="margin-top: 14px;">
                                                <i class="fa-solid fa-utensils"></i> Browse Farm-to-Table Menu
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Right: Sanctuary Concierge & Operations Guidelines -->
                            <div class="g-card" style="margin-bottom: 0;">
                                <div class="g-card-header">
                                    <h3 class="g-card-title"><i class="fa-solid fa-bell-concierge"></i> Sanctuary Guest Concierge</h3>
                                    <span style="font-size: 11.5px; color: #10B981; font-weight: 700;"><i class="fa-solid fa-shield-halved"></i> 24x7 Active</span>
                                </div>

                                <div style="display: flex; flex-direction: column; gap: 14px; font-size: 13px;">
                                    <div style="display: flex; justify-content: space-between; padding: 10px 12px; background: rgba(0,0,0,0.25); border-radius: 8px;">
                                        <span style="color: #94A3B8;"><i class="fa-solid fa-clock" style="color: #C5A059; margin-right: 6px;"></i> Standard Check-in:</span>
                                        <strong style="color: #FFFFFF;">01:00 PM</strong>
                                    </div>

                                    <div style="display: flex; justify-content: space-between; padding: 10px 12px; background: rgba(0,0,0,0.25); border-radius: 8px;">
                                        <span style="color: #94A3B8;"><i class="fa-solid fa-door-open" style="color: #C5A059; margin-right: 6px;"></i> Standard Check-out:</span>
                                        <strong style="color: #FFFFFF;">11:00 AM</strong>
                                    </div>

                                    <div style="display: flex; justify-content: space-between; padding: 10px 12px; background: rgba(0,0,0,0.25); border-radius: 8px;">
                                        <span style="color: #94A3B8;"><i class="fa-solid fa-wifi" style="color: #C5A059; margin-right: 6px;"></i> Sanctuary High-Speed WiFi:</span>
                                        <strong style="color: #FFFFFF;">FoodForest_Sanctuary</strong>
                                    </div>

                                    <div style="margin-top: 8px;">
                                        <a href="https://wa.me/<?php echo htmlspecialchars($concierge_wa); ?>?text=Hello%20Concierge,%20I%20am%20residing%20at%20<?php echo urlencode($b_title); ?>%20(Ref:%20<?php echo urlencode($b_ref); ?>)%20and%20require%20assistance." target="_blank" class="guest-btn-submit" style="margin-top: 0; background: linear-gradient(135deg, #10B981, #059669); color: #FFFFFF;">
                                            <i class="fa-brands fa-whatsapp" style="font-size: 16px;"></i>
                                            <span>Chat with Resident Concierge</span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </section>


                    <!-- ========================================================= -->
                    <!-- VIEW 2: MY VILLA / MY COTTAGE DETAILS                     -->
                    <!-- ========================================================= -->
                    <section class="guest-view-pane" id="view-my_villa">
                        <div class="g-card">
                            <div class="g-card-header">
                                <div>
                                    <h3 class="g-card-title"><i class="fa-solid fa-hotel"></i> <?php echo htmlspecialchars($b_title); ?></h3>
                                    <span style="font-size: 12px; color: #94A3B8;">Reservation Reference: #<?php echo $b_ref; ?> • <?php echo (int)$active_booking['nights']; ?> Night<?php echo $active_booking['nights'] > 1 ? 's' : ''; ?> Stay</span>
                                </div>
                                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                    <!-- Print Villa Stay Bill PDF Button -->
                                    <a href="admin/print_bill.php?ref=<?php echo urlencode($active_booking['reference_code']); ?>&token=<?php echo urlencode($folio_token); ?>&type=stay" target="_blank" class="btn-gold-action" title="Print Villa Stay Bill (PDF)">
                                        <i class="fa-solid fa-file-pdf"></i> Print Villa Bill (PDF)
                                    </a>
                                </div>
                            </div>

                            <!-- Villa Showcase Gallery -->
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; margin-bottom: 24px;">
                                <div style="border-radius: 12px; overflow: hidden; height: 260px; border: 1px solid rgba(197, 160, 89, 0.25);">
                                    <img src="<?php echo htmlspecialchars($b_image); ?>" alt="<?php echo htmlspecialchars($b_title); ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='assets/images/treehouse_exterior.png'">
                                </div>
                                <?php if (!empty($room_photos)): ?>
                                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                                        <?php foreach (array_slice($room_photos, 0, 4) as $p_item): ?>
                                            <div style="border-radius: 8px; overflow: hidden; height: 125px; border: 1px solid rgba(255, 255, 255, 0.1);">
                                                <img src="<?php echo htmlspecialchars($p_item); ?>" alt="Photo" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='assets/images/treehouse_interior.png'">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Two Columns: Specs & Tariff -->
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
                                
                                <div>
                                    <h4 class="font-serif" style="color: #C5A059; font-size: 18px; margin-bottom: 12px;">Chalet Architectural Details</h4>
                                    <p style="color: #CBD5E1; font-size: 13.5px; line-height: 1.6; margin-bottom: 18px;">
                                        <?php echo htmlspecialchars($active_booking['room_details']['description'] ?? 'Naturally insulated sanctuary villa sculpted from living earth, teak, and terracotta nestled in the organic orchards of Kanthalloor.'); ?>
                                    </p>

                                    <h4 class="font-serif" style="color: #C5A059; font-size: 16px; margin-bottom: 10px;">Living Amenities</h4>
                                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                        <?php 
                                        $amenities_raw = $active_booking['room_details']['amenities'] ?? 'Hand-Carved Bed, Natural Clay Cooler, Slate Hearth, Forest Spring Water, Orchard Sit-out';
                                        $amenities_arr = explode(',', $amenities_raw);
                                        foreach ($amenities_arr as $am):
                                        ?>
                                            <span style="background: rgba(16, 31, 21, 0.8); border: 1px solid rgba(197, 160, 89, 0.25); padding: 5px 12px; border-radius: 6px; font-size: 12px; color: #FFFFFF;">
                                                <i class="fa-solid fa-check" style="color: #10B981; margin-right: 5px;"></i> <?php echo htmlspecialchars(trim($am)); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 12px; padding: 20px;">
                                    <h4 class="font-serif" style="color: #C5A059; font-size: 18px; margin-bottom: 14px;">Reservation Tariff Breakdown</h4>
                                    
                                    <div style="display: flex; flex-direction: column; gap: 12px; font-size: 13.5px;">
                                        <div style="display: flex; justify-content: space-between;">
                                            <span style="color: #94A3B8;">Check-in Date:</span>
                                            <strong style="color: #FFFFFF;"><?php echo date('d M Y', strtotime($active_booking['checkin_date'])); ?></strong>
                                        </div>
                                        <div style="display: flex; justify-content: space-between;">
                                            <span style="color: #94A3B8;">Check-out Date:</span>
                                            <strong style="color: #FFFFFF;"><?php echo date('d M Y', strtotime($active_booking['checkout_date'])); ?></strong>
                                        </div>
                                        <div style="display: flex; justify-content: space-between;">
                                            <span style="color: #94A3B8;">Duration of Residency:</span>
                                            <strong style="color: #FFFFFF;"><?php echo (int)$active_booking['nights']; ?> Night<?php echo $active_booking['nights'] > 1 ? 's' : ''; ?></strong>
                                        </div>
                                        <div style="display: flex; justify-content: space-between;">
                                            <span style="color: #94A3B8;">Occupancy:</span>
                                            <strong style="color: #FFFFFF;"><?php echo (int)($active_booking['adults_count'] ?? 2); ?> Adults<?php echo !empty($active_booking['kids_count']) ? (', ' . (int)$active_booking['kids_count'] . ' Kids') : ''; ?></strong>
                                        </div>
                                        <div style="border-top: 1px dashed rgba(255,255,255,0.1); margin: 4px 0;"></div>
                                        <div style="display: flex; justify-content: space-between; font-size: 15px;">
                                            <span style="color: #FFFFFF; font-weight: 600;">Villa Stay Tariff:</span>
                                            <strong style="color: #C5A059; font-family: monospace; font-size: 16px;"><?php echo $currency . number_format($room_val, 2); ?></strong>
                                        </div>
                                    </div>

                                    <div style="margin-top: 20px;">
                                        <a href="admin/print_bill.php?ref=<?php echo urlencode($active_booking['reference_code']); ?>&token=<?php echo urlencode($folio_token); ?>&type=stay" target="_blank" class="guest-btn-submit" style="margin-top: 0;">
                                            <i class="fa-solid fa-print"></i>
                                            <span>Generate &amp; Print Villa Stay Bill (PDF)</span>
                                        </a>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </section>


                    <!-- ========================================================= -->
                    <!-- VIEW 3: OUR KITCHEN (Active Cottage Orders & Status)      -->
                    <!-- ========================================================= -->
                    <section class="guest-view-pane" id="view-kitchen">
                        <div class="g-card">
                            <div class="g-card-header">
                                <div>
                                    <h3 class="g-card-title"><i class="fa-solid fa-kitchen-set"></i> Our Kitchen — In-Cottage Dining Hub</h3>
                                    <span style="font-size: 12px; color: #94A3B8;">Synchronized in real-time with estate chef stations &amp; farm hearth</span>
                                </div>
                                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                    <!-- Print Food Bill PDF Button -->
                                    <a href="admin/print_bill.php?ref=<?php echo urlencode($active_booking['reference_code']); ?>&token=<?php echo urlencode($folio_token); ?>&type=other" target="_blank" class="btn-gold-action" title="Print Food / Dining Bill (PDF)">
                                        <i class="fa-solid fa-file-pdf"></i> Print Food Bill (PDF)
                                    </a>
                                    <button type="button" onclick="switchGuestTab('menu')" class="btn-outline-action">
                                        <i class="fa-solid fa-plus"></i> Order More Dishes
                                    </button>
                                </div>
                            </div>

                            <!-- Kitchen Stats Strip -->
                            <div style="display: flex; gap: 14px; margin-bottom: 20px; flex-wrap: wrap;">
                                <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 8px; padding: 10px 16px; display: flex; align-items: center; gap: 10px;">
                                    <span style="font-size: 12px; color: #94A3B8;">Dishes Ordered:</span>
                                    <strong style="color: #FFFFFF; font-size: 15px;" id="kitchen-dishes-count-badge"><?php echo $b_food_count; ?> Items</strong>
                                </div>
                                <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 8px; padding: 10px 16px; display: flex; align-items: center; gap: 10px;">
                                    <span style="font-size: 12px; color: #94A3B8;">Dining Spend:</span>
                                    <strong style="color: #C5A059; font-size: 15px;" id="kitchen-dishes-total-badge"><?php echo $currency . number_format($food_val, 2); ?></strong>
                                </div>
                                <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 8px; padding: 10px 16px; display: flex; align-items: center; gap: 10px;">
                                    <span style="font-size: 12px; color: #94A3B8;">Preparation Status:</span>
                                    <span class="status-badge-kitchen <?php echo strtolower($active_booking['food_status'] ?? 'queued'); ?>">
                                        <?php echo ($active_booking['food_status'] ?? '') === 'served' ? '✓ Served' : (($active_booking['food_status'] ?? '') === 'preparing' ? '🍳 Preparing' : '⏳ Queued'); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Table of Ordered Dishes -->
                            <div id="cottage-kitchen-table-wrap">
                                <?php if (!empty($f_items)): ?>
                                    <table class="g-table">
                                        <thead>
                                            <tr>
                                                <th>Dish Name</th>
                                                <th>Meal Slot</th>
                                                <th style="text-align: center;">Qty</th>
                                                <th style="text-align: right;">Unit Rate</th>
                                                <th style="text-align: right;">Subtotal</th>
                                                <th style="text-align: center;">Kitchen Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="cottage-kitchen-tbody">
                                            <?php foreach ($f_items as $f_row): 
                                                $f_name = $f_row['heading'] ?? 'Custom Farm Meal';
                                                $f_slot = ucfirst($f_row['meal_time'] ?? ($f_row['category'] ?? 'Dining'));
                                                $f_qty = (int)($f_row['quantity'] ?? 1);
                                                $f_rate = (float)($f_row['price'] ?? 0);
                                                $f_sub = (float)($f_row['subtotal'] ?? ($f_qty * $f_rate));
                                                $f_status = strtolower($active_booking['food_status'] ?? 'queued');
                                            ?>
                                                <tr>
                                                    <td>
                                                        <strong style="color: #FFFFFF;"><?php echo htmlspecialchars($f_name); ?></strong>
                                                        <?php if (!empty($f_row['dietary_type'])): ?>
                                                            <span style="font-size: 11px; margin-left: 6px; color: <?php echo $f_row['dietary_type'] === 'veg' ? '#34D399' : ($f_row['dietary_type'] === 'non-veg' ? '#F87171' : '#FCD34D'); ?>;">
                                                                <?php echo $f_row['dietary_type'] === 'veg' ? '🌱 Veg' : ($f_row['dietary_type'] === 'non-veg' ? '🍗 Non-Veg' : '🌾 Vegan'); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span style="font-size: 11px; background: rgba(255,255,255,0.06); padding: 3px 8px; border-radius: 4px;"><?php echo htmlspecialchars($f_slot); ?></span>
                                                    </td>
                                                    <td style="text-align: center; font-weight: 700; color: #FFFFFF;"><?php echo $f_qty; ?></td>
                                                    <td style="text-align: right; color: #94A3B8;"><?php echo $currency . number_format($f_rate, 2); ?></td>
                                                    <td style="text-align: right; font-weight: 700; color: #C5A059;"><?php echo $currency . number_format($f_sub, 2); ?></td>
                                                    <td style="text-align: center;">
                                                        <span class="status-badge-kitchen <?php echo $f_status; ?>">
                                                            <?php echo $f_status === 'served' ? '✓ Served' : ($f_status === 'preparing' ? '🍳 Preparing' : '⏳ Queued'); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div style="text-align: center; padding: 40px 10px; color: #94A3B8;">
                                        <i class="fa-solid fa-utensils" style="font-size: 32px; color: #C5A059; margin-bottom: 10px; display: block;"></i>
                                        <h4 class="font-serif" style="font-size: 18px; color: #FFFFFF; margin-bottom: 6px;">No Meals Ordered Yet</h4>
                                        <p style="font-size: 13px; max-width: 440px; margin: 0 auto 18px;">Browse our 75 organic farm-to-table dishes from the Farm Menu tab to have delicious meals delivered straight to your cottage.</p>
                                        <button type="button" onclick="switchGuestTab('menu')" class="btn-gold-action">
                                            <i class="fa-solid fa-utensils"></i> Open Farm Menu &amp; Order
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>

                        </div>
                    </section>


                    <!-- ========================================================= -->
                    <!-- VIEW 4: OUR MENU (75 Dishes & In-Cottage Ordering)        -->
                    <!-- ========================================================= -->
                    <section class="guest-view-pane" id="view-menu">
                        <div class="g-card">
                            <div class="g-card-header">
                                <div>
                                    <h3 class="g-card-title"><i class="fa-solid fa-utensils"></i> Sanctuary Living Gastronomy Menu</h3>
                                    <span style="font-size: 12px; color: #94A3B8;">Add farm-fresh dishes to your dining tray, review your order, and confirm with security verification</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                                    <button type="button" class="btn-gold-action" onclick="openOrderReviewModal()" style="display: inline-flex; align-items: center; gap: 8px; padding: 7px 14px; font-size: 12.5px; font-weight: 700; border-radius: 7px; cursor: pointer;">
                                        <i class="fa-solid fa-bell-concierge"></i>
                                        <span>Order Tray</span>
                                        <span id="tray-header-count" style="background: rgba(0,0,0,0.5); padding: 2px 7px; border-radius: 10px; font-size: 11px;">0</span>
                                    </button>
                                    <div style="min-width: 220px; position: relative;">
                                        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 11px; color: #94A3B8; font-size: 12px;"></i>
                                        <input type="text" id="menu-search-input" oninput="searchGuestMenu(this.value)" placeholder="Search 75 farm dishes..." style="width: 100%; padding: 8px 12px 8px 34px; background: rgba(0,0,0,0.35); border: 1px solid rgba(197, 160, 89, 0.3); border-radius: 7px; color: #FFFFFF; font-size: 12.5px; outline: none;">
                                    </div>
                                </div>
                            </div>

                            <!-- Category Filter Pills -->
                            <div class="menu-filter-bar font-sans" id="menu-filter-bar">
                                <button type="button" class="menu-filter-pill active" onclick="filterGuestMenu('all')">🌟 All Dishes (<?php echo count($portal_menu_items); ?>)</button>
                                <button type="button" class="menu-filter-pill" onclick="filterGuestMenu('breakfast')">☀️ Breakfast</button>
                                <button type="button" class="menu-filter-pill" onclick="filterGuestMenu('lunch')">🍛 Lunch Sets</button>
                                <button type="button" class="menu-filter-pill" onclick="filterGuestMenu('curries')">🍲 Traditional Curries</button>
                                <button type="button" class="menu-filter-pill" onclick="filterGuestMenu('millet')">🌾 Organic Millets</button>
                                <button type="button" class="menu-filter-pill" onclick="filterGuestMenu('snacks')">☕ Evening Specials</button>
                                <button type="button" class="menu-filter-pill" onclick="filterGuestMenu('juices')">🥤 Fresh Juices</button>
                                <button type="button" class="menu-filter-pill" onclick="filterGuestMenu('dinner')">🌙 Dinner</button>
                            </div>

                            <!-- Dishes Grid -->
                            <div class="menu-items-grid" id="guest-menu-grid">
                                <?php foreach ($portal_menu_items as $d): 
                                    $d_cat = strtolower($d['category']);
                                    $d_diet = strtolower($d['dietary_type'] ?? 'veg');
                                    $d_meal = strtolower($d['default_meal_time'] ?? 'lunch');
                                ?>
                                    <div class="menu-dish-card" 
                                         data-dish-id="<?php echo $d['id']; ?>"
                                         data-category="<?php echo htmlspecialchars($d_cat); ?>"
                                         data-name="<?php echo htmlspecialchars(strtolower($d['heading'])); ?>">
                                        
                                        <div>
                                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px; gap: 8px;">
                                                <h4 style="font-size: 15px; font-weight: 700; color: #FFFFFF; margin: 0; line-height: 1.3;">
                                                    <?php echo htmlspecialchars($d['heading']); ?>
                                                </h4>
                                                <span style="font-size: 11px; padding: 2px 7px; border-radius: 4px; font-weight: 700; <?php echo $d_diet === 'veg' ? 'background: rgba(16,185,129,0.15); color: #34D399;' : ($d_diet === 'non-veg' ? 'background: rgba(239,68,68,0.15); color: #F87171;' : 'background: rgba(245,158,11,0.15); color: #FCD34D;'); ?>">
                                                    <?php echo $d_diet === 'veg' ? '🌱 Veg' : ($d_diet === 'non-veg' ? '🍗 Non-Veg' : '🌾 Vegan'); ?>
                                                </span>
                                            </div>

                                            <?php if (!empty($d['subtitle'])): ?>
                                                <p style="font-size: 12px; color: #94A3B8; margin-bottom: 12px; line-height: 1.4;">
                                                    <?php echo htmlspecialchars($d['subtitle']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>

                                        <div>
                                            <div style="margin-bottom: 10px;">
                                                <select class="guest-input font-sans" id="meal-select-<?php echo $d['id']; ?>" style="padding: 5px 8px; font-size: 11.5px; border-radius: 5px;">
                                                    <option value="breakfast" <?php echo ($d_meal === 'breakfast' || $d_cat === 'breakfast') ? 'selected' : ''; ?>>☀️ Breakfast (09:00 AM – 10:00 AM)</option>
                                                    <option value="lunch" <?php echo ($d_meal === 'lunch' || $d_cat === 'lunch' || $d_cat === 'curries' || $d_cat === 'millet') ? 'selected' : ''; ?>>🍛 Lunch (12:30 PM – 02:30 PM)</option>
                                                    <option value="snacks" <?php echo ($d_meal === 'snacks' || $d_cat === 'snacks' || $d_cat === 'juices') ? 'selected' : ''; ?>>☕ Evening Snacks (04:30 PM – 06:30 PM)</option>
                                                    <option value="dinner" <?php echo ($d_meal === 'dinner' || $d_cat === 'dinner') ? 'selected' : ''; ?>>🌙 Dinner (07:00 PM – 09:00 PM)</option>
                                                </select>
                                            </div>

                                            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed rgba(255,255,255,0.08); padding-top: 10px;">
                                                <span style="font-size: 16px; font-weight: 700; color: #C5A059; font-family: monospace;">
                                                    <?php echo $currency . number_format($d['price'], 2); ?>
                                                </span>

                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <div style="display: flex; align-items: center; background: rgba(0,0,0,0.4); border-radius: 5px; border: 1px solid rgba(255,255,255,0.1);">
                                                        <button type="button" style="width: 26px; height: 26px; background: transparent; color: #fff; font-weight: 700;" onclick="adjustPortalQty(<?php echo $d['id']; ?>, -1)">−</button>
                                                        <span id="qty-val-<?php echo $d['id']; ?>" style="width: 26px; text-align: center; font-size: 12px; font-weight: 700;">1</span>
                                                        <button type="button" style="width: 26px; height: 26px; background: transparent; color: #fff; font-weight: 700;" onclick="adjustPortalQty(<?php echo $d['id']; ?>, 1)">+</button>
                                                    </div>

                                                    <button type="button" 
                                                            id="btn-order-<?php echo $d['id']; ?>"
                                                            class="btn-gold-action" 
                                                            style="padding: 6px 12px; font-size: 12px; display: inline-flex; align-items: center; gap: 6px;"
                                                            onclick="addToDiningTray(<?php echo $d['id']; ?>, '<?php echo addslashes($d['heading']); ?>', <?php echo $d['price']; ?>, '<?php echo addslashes($d_diet); ?>')">
                                                        <i class="fa-solid fa-cart-plus"></i> Add
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                <?php endforeach; ?>
                            </div>

                        </div>
                    </section>


                    <!-- ========================================================= -->
                    <!-- VIEW 5: OTHER EXPERIENCES (Sanctuary Rituals & Trails)    -->
                    <!-- ========================================================= -->
                    <section class="guest-view-pane" id="view-experiences">
                        <div class="g-card">
                            <div class="g-card-header">
                                <div>
                                    <h3 class="g-card-title"><i class="fa-solid fa-sparkles"></i> Curated Sanctuary Experiences</h3>
                                    <span style="font-size: 12px; color: #94A3B8;">Ancestral rituals, guided orchard trails &amp; dark sky astronomy</span>
                                </div>
                            </div>

                            <div class="experiences-grid">
                                <?php foreach ($all_experiences as $exp): 
                                    $e_title = $exp['title'];
                                    $is_reserved = (!empty($active_booking['addons']) && stripos($active_booking['addons'], $e_title) !== false);
                                ?>
                                    <div class="exp-card">
                                        <div style="position: relative;">
                                            <img src="<?php echo htmlspecialchars($exp['image_url'] ?? 'assets/images/01 (18).jpeg'); ?>" alt="<?php echo htmlspecialchars($e_title); ?>" class="exp-thumb" onerror="this.src='assets/images/treehouse_exterior.png'">
                                            <span style="position: absolute; top: 12px; left: 12px; background: rgba(8,16,11,0.85); border: 1px solid var(--adm-gold-border); color: #C5A059; padding: 3px 9px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase;">
                                                <?php echo htmlspecialchars($exp['badge'] ?? 'SANCTUARY RITUAL'); ?>
                                            </span>
                                            <?php if ($is_reserved): ?>
                                                <span style="position: absolute; top: 12px; right: 12px; background: rgba(16,185,129,0.9); color: #FFFFFF; padding: 3px 9px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase;">
                                                    <i class="fa-solid fa-circle-check"></i> Reserved
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="exp-body">
                                            <div>
                                                <h4 class="font-serif" style="font-size: 18px; color: #FFFFFF; margin-bottom: 4px;">
                                                    <?php echo htmlspecialchars($e_title); ?>
                                                </h4>
                                                <div style="font-size: 12px; color: #C5A059; margin-bottom: 10px;">
                                                    <i class="fa-regular fa-clock" style="margin-right: 4px;"></i> <?php echo htmlspecialchars($exp['timing'] ?? 'Morning / Evening'); ?>
                                                </div>
                                                <p style="font-size: 12.5px; color: #94A3B8; line-height: 1.5; margin-bottom: 14px;">
                                                    <?php echo htmlspecialchars($exp['description'] ?? ''); ?>
                                                </p>
                                            </div>

                                            <div style="border-top: 1px solid rgba(255,255,255,0.08); padding-top: 12px;">
                                                <?php if ($is_reserved): ?>
                                                    <div style="background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); color: #34D399; padding: 8px 12px; border-radius: 6px; text-align: center; font-size: 12px; font-weight: 700;">
                                                        <i class="fa-solid fa-circle-check"></i> Booked for Your Stay
                                                    </div>
                                                <?php else: ?>
                                                    <a href="https://wa.me/<?php echo htmlspecialchars($concierge_wa); ?>?text=Hello%20Concierge,%20I%20am%20residing%20at%20<?php echo urlencode($b_title); ?>%20(Ref:%20<?php echo urlencode($b_ref); ?>)%20and%20would%20like%20to%20reserve%20the%20<?php echo urlencode($e_title); ?>%20experience." target="_blank" class="btn-outline-action" style="width: 100%; justify-content: center;">
                                                        <i class="fa-brands fa-whatsapp"></i> Reserve via Concierge
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                    </div>
                                <?php endforeach; ?>
                            </div>

                        </div>
                    </section>


                    <!-- ========================================================= -->
                    <!-- VIEW 6: PAYABLE FOLIO & BILLS (Cart-like Breakdown & PDF) -->
                    <!-- ========================================================= -->
                    <section class="guest-view-pane" id="view-folio">
                        <div class="g-card">
                            <div class="g-card-header">
                                <div>
                                    <h3 class="g-card-title"><i class="fa-solid fa-receipt"></i> Consolidated Tax Folio &amp; Statement</h3>
                                    <span style="font-size: 12px; color: #94A3B8;">Folio Reference #<?php echo $b_ref; ?> • Official GST Tax Breakdown</span>
                                </div>
                                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                    <!-- Print Master Combined Folio PDF -->
                                    <a href="admin/print_bill.php?ref=<?php echo urlencode($active_booking['reference_code']); ?>&token=<?php echo urlencode($folio_token); ?>&type=combined" target="_blank" class="btn-gold-action" title="Print Master Combined Folio (PDF)">
                                        <i class="fa-solid fa-file-pdf"></i> Print Master Folio (PDF)
                                    </a>
                                    <!-- Public Digital Receipt Link -->
                                    <a href="receipt.php?ref=<?php echo urlencode($active_booking['reference_code']); ?><?php echo !empty($active_booking['guest_access_token']) ? '&passcode=' . urlencode($active_booking['guest_access_token']) : ''; ?>" target="_blank" class="btn-outline-action" title="Public Shareable Folio Receipt">
                                        <i class="fa-solid fa-share-nodes"></i> Digital Receipt
                                    </a>
                                </div>
                            </div>

                            <!-- Folio Itemized Statement Table -->
                            <div style="background: rgba(0,0,0,0.25); border: 1px solid rgba(197, 160, 89, 0.2); border-radius: 10px; padding: 18px; margin-bottom: 24px;">
                                <table class="g-table">
                                    <thead>
                                        <tr>
                                            <th>Folio Component</th>
                                            <th>Particulars &amp; Details</th>
                                            <th style="text-align: right;">Amount (<?php echo $currency; ?>)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- 1. Villa Accommodation -->
                                        <tr>
                                            <td>
                                                <strong style="color: #FFFFFF;"><i class="fa-solid fa-house-chimney" style="color: #C5A059; margin-right: 6px;"></i> Villa Stay Tariff</strong>
                                            </td>
                                            <td style="color: #94A3B8;">
                                                <?php echo htmlspecialchars($b_title); ?> &bull; <?php echo (int)$active_booking['nights']; ?> Night<?php echo $active_booking['nights'] > 1 ? 's' : ''; ?>
                                            </td>
                                            <td style="text-align: right; font-weight: 600;" id="folio-room-val">
                                                <?php echo $currency . number_format($room_val, 2); ?>
                                            </td>
                                        </tr>

                                        <!-- 2. Gastronomy & Food Orders -->
                                        <tr>
                                            <td>
                                                <strong style="color: #FFFFFF;"><i class="fa-solid fa-utensils" style="color: #F59E0B; margin-right: 6px;"></i> Gastronomy &amp; Dining</strong>
                                            </td>
                                            <td style="color: #94A3B8;">
                                                In-cottage dining &amp; chef orders (<span id="folio-food-count"><?php echo $b_food_count; ?></span> items)
                                            </td>
                                            <td style="text-align: right; font-weight: 600; color: #FCD34D;" id="folio-food-val">
                                                <?php echo $currency . number_format($food_val, 2); ?>
                                            </td>
                                        </tr>

                                        <!-- 3. Curated Experiences -->
                                        <?php if ($act_val > 0 || (!empty($active_booking['addons']) && strtolower(trim($active_booking['addons'])) !== 'none')): ?>
                                        <tr>
                                            <td>
                                                <strong style="color: #FFFFFF;"><i class="fa-solid fa-compass" style="color: #38BDF8; margin-right: 6px;"></i> Sanctuary Experiences</strong>
                                            </td>
                                            <td style="color: #94A3B8;">
                                                Curated rituals &amp; guided orchard activities
                                            </td>
                                            <td style="text-align: right; font-weight: 600;">
                                                <?php echo $currency . number_format($act_val, 2); ?>
                                            </td>
                                        </tr>
                                        <?php endif; ?>

                                        <!-- 4. Incidentals / Extras -->
                                        <?php if ($custom_val > 0): ?>
                                        <tr>
                                            <td>
                                                <strong style="color: #FFFFFF;"><i class="fa-solid fa-bell-concierge" style="color: #A855F7; margin-right: 6px;"></i> Incidentals &amp; Amenities</strong>
                                            </td>
                                            <td style="color: #94A3B8;">
                                                Additional services &amp; concierge requests
                                            </td>
                                            <td style="text-align: right; font-weight: 600;">
                                                <?php echo $currency . number_format($custom_val, 2); ?>
                                            </td>
                                        </tr>
                                        <?php endif; ?>

                                        <!-- GST 5% Breakdown -->
                                        <tr>
                                            <td style="color: #94A3B8;"><i class="fa-solid fa-receipt" style="margin-right: 6px;"></i> CGST (2.5%)</td>
                                            <td style="color: #64748B;">Central Goods &amp; Services Tax</td>
                                            <td style="text-align: right; color: #94A3B8;" id="folio-cgst-val">
                                                <?php echo $currency . number_format($cgst_val, 2); ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="color: #94A3B8;"><i class="fa-solid fa-receipt" style="margin-right: 6px;"></i> SGST (2.5%)</td>
                                            <td style="color: #64748B;">Kerala State Goods &amp; Services Tax</td>
                                            <td style="text-align: right; color: #94A3B8;" id="folio-sgst-val">
                                                <?php echo $currency . number_format($sgst_val, 2); ?>
                                            </td>
                                        </tr>

                                        <!-- Grand Net Total -->
                                        <tr style="border-top: 1px solid rgba(197, 160, 89, 0.35); font-weight: 700;">
                                            <td colspan="2" style="color: #C5A059; font-size: 15px;">
                                                <i class="fa-solid fa-wallet" style="margin-right: 6px;"></i> CONSOLIDATED PAYABLE TOTAL (GST 5% INCLUDED)
                                            </td>
                                            <td style="text-align: right; color: #C5A059; font-size: 16px; font-family: monospace;" id="folio-net-total-val">
                                                <?php echo $currency . number_format($grand_val, 2); ?>
                                            </td>
                                        </tr>

                                        <!-- Advance Settled -->
                                        <tr>
                                            <td colspan="2" style="color: #34D399;">
                                                <i class="fa-solid fa-circle-check" style="margin-right: 6px;"></i> Less: Advance Paid / Settled
                                            </td>
                                            <td style="text-align: right; color: #34D399; font-weight: 600;">
                                                -<?php echo $currency . number_format($adv_val, 2); ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Balance Banner -->
                            <div style="background: rgba(16, 31, 21, 0.9); border: 1px solid rgba(197, 160, 89, 0.3); border-radius: 12px; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                                <div>
                                    <span style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 1px; color: #94A3B8; font-weight: 700; display: block;">
                                        Balance Payable on Departure:
                                    </span>
                                    <div style="font-size: 26px; font-weight: 700; color: <?php echo $bal_val <= 0 ? '#34D399' : '#F59E0B'; ?>; font-family: var(--adm-font-title);" id="folio-balance-val">
                                        <?php echo $currency . number_format($bal_val, 2); ?>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                    <span style="font-size: 12px; padding: 6px 14px; border-radius: 20px; font-weight: 700; <?php echo $bal_val <= 0 ? 'background: rgba(16, 185, 129, 0.2); color: #34D399; border: 1px solid rgba(16, 185, 129, 0.4);' : 'background: rgba(245, 158, 11, 0.2); color: #FBBF24; border: 1px solid rgba(245, 158, 11, 0.4);'; ?>" id="folio-balance-status-badge">
                                        <?php echo $bal_val <= 0 ? '✓ SETTLED IN FULL' : 'PAYMENT DUE AT CHECKOUT'; ?>
                                    </span>
                                    <a href="admin/print_bill.php?ref=<?php echo urlencode($active_booking['reference_code']); ?>&token=<?php echo urlencode($folio_token); ?>&type=combined" target="_blank" class="btn-gold-action">
                                        <i class="fa-solid fa-print"></i> Print Full Bill (PDF)
                                    </a>
                                </div>
                            </div>

                        </div>
                    </section>

                <?php endif; ?>

            </main>
        </div>

    <!-- ========================================================================= -->
    <!-- DINING ORDER TRAY: FLOATING BAR & VERIFICATION MODAL                      -->
    <!-- ========================================================================= -->

    <!-- Floating Tray Bar -->
    <div id="guest-cart-floating-bar" class="guest-cart-bar" style="display: none;">
        <div class="cart-bar-info">
            <div class="cart-bar-badge">
                <i class="fa-solid fa-bell-concierge"></i>
                <span id="floating-cart-count">0</span>
            </div>
            <div>
                <div style="font-size: 14px; font-weight: 700; color: #FFFFFF; font-family: var(--adm-font-title);">Dining Order Tray</div>
                <div style="font-size: 12px; color: #CBD5E1;">
                    <span id="floating-cart-items-text">0 dishes selected</span> &bull; 
                    <strong style="color: var(--adm-gold); font-size: 13px;" id="floating-cart-total"><?php echo $currency; ?>0.00</strong>
                </div>
            </div>
        </div>
        <div class="cart-bar-actions">
            <button type="button" class="btn-cart-clear" onclick="clearDiningTray()">
                <i class="fa-solid fa-trash-can"></i> Clear
            </button>
            <button type="button" class="btn-cart-review" onclick="openOrderReviewModal()">
                <span>Review &amp; Confirm</span>
                <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
    </div>

    <!-- Review & Confirm Order Modal with Captcha Challenge -->
    <div id="modal-order-review" class="portal-modal-overlay" style="display: none;">
        <div class="portal-modal-card">
            <div class="portal-modal-header">
                <div>
                    <h3 class="portal-modal-title"><i class="fa-solid fa-bell-concierge" style="color: var(--adm-gold); margin-right: 8px;"></i> Review Your Dining Order</h3>
                    <span class="portal-modal-sub">Cottage: <strong><?php echo htmlspecialchars($b_title ?? 'Active Stay'); ?></strong> (Ref: #<?php echo htmlspecialchars($b_ref); ?>)</span>
                </div>
                <button type="button" class="portal-modal-close" onclick="closeOrderReviewModal()" title="Close">&times;</button>
            </div>

            <div class="portal-modal-body">
                <!-- Tray Items Table -->
                <div style="margin-bottom: 18px; max-height: 240px; overflow-y: auto; border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; background: rgba(0,0,0,0.25);">
                    <table class="tray-table">
                        <thead>
                            <tr>
                                <th>Dish</th>
                                <th>Meal Slot</th>
                                <th style="text-align: center;">Qty</th>
                                <th style="text-align: right;">Unit</th>
                                <th style="text-align: right;">Subtotal</th>
                                <th style="text-align: center; width: 40px;"></th>
                            </tr>
                        </thead>
                        <tbody id="tray-modal-table-body">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>

                <!-- Financial Summary Box -->
                <div style="background: rgba(0,0,0,0.4); border: 1px solid rgba(197, 160, 89, 0.25); border-radius: 10px; padding: 14px 18px; margin-bottom: 18px;">
                    <div style="display: flex; justify-content: space-between; font-size: 13px; color: #94A3B8; margin-bottom: 6px;">
                        <span>Items Subtotal:</span>
                        <strong style="color: #FFFFFF;" id="modal-summary-subtotal"><?php echo $currency; ?>0.00</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px; color: #94A3B8; margin-bottom: 8px;">
                        <span>Restaurant GST (5%):</span>
                        <strong style="color: #FFFFFF;" id="modal-summary-tax"><?php echo $currency; ?>0.00</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 14.5px; font-weight: 700; color: #FFFFFF; border-top: 1px dashed rgba(255,255,255,0.15); padding-top: 8px;">
                        <span style="color: var(--adm-gold);">Estimated Order Total:</span>
                        <strong style="color: var(--adm-gold); font-size: 16px;" id="modal-summary-total"><?php echo $currency; ?>0.00</strong>
                    </div>
                    <div style="font-size: 11px; color: #64748B; margin-top: 6px;">
                        * This dining total will be posted to your cottage folio and settled at checkout.
                    </div>
                </div>

                <!-- Security Captcha Verification Section (Mandatory to prevent unintended kitchen orders) -->
                <div style="background: rgba(197, 160, 89, 0.08); border: 1.5px solid rgba(197, 160, 89, 0.35); border-radius: 10px; padding: 14px 18px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                        <i class="fa-solid fa-shield-halved" style="color: var(--adm-gold); font-size: 15px;"></i>
                        <span style="font-size: 13px; font-weight: 700; color: #FFFFFF;">Security Order Verification</span>
                    </div>
                    <p style="font-size: 11.5px; color: #94A3B8; margin: 0 0 12px 0; line-height: 1.4;">
                        Please enter the security verification code below to confirm this dining order and prevent accidental kitchen dispatch:
                    </p>
                    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; background: #000; padding: 4px 8px; border-radius: 8px; border: 1px solid rgba(197,160,89,0.4);">
                            <img id="order-captcha-img" src="api/captcha.php?type=order" alt="Security Code" style="height: 38px; border-radius: 4px; display: block; filter: brightness(1.05);">
                            <button type="button" onclick="refreshOrderCaptcha()" title="Refresh Security Code" style="background: transparent; border: none; color: var(--adm-gold); cursor: pointer; padding: 6px 8px; font-size: 14px;">
                                <i class="fa-solid fa-rotate"></i>
                            </button>
                        </div>
                        <div style="flex-grow: 1; min-width: 140px;">
                            <input type="text" 
                                   id="order-captcha-input" 
                                   maxlength="6" 
                                   placeholder="ENTER CODE" 
                                   autocomplete="off"
                                   style="width: 100%; padding: 10px 14px; background: rgba(0,0,0,0.5); border: 1.5px solid rgba(197,160,89,0.4); border-radius: 8px; color: #FFFFFF; font-size: 15px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; text-align: center; outline: none;">
                        </div>
                    </div>
                    <div id="order-captcha-err" style="display: none; color: #F87171; font-size: 12px; margin-top: 8px; font-weight: 600;"></div>
                </div>
            </div>

            <div class="portal-modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="closeOrderReviewModal()">
                    <i class="fa-solid fa-arrow-left"></i> Keep Browsing
                </button>
                <button type="button" class="btn-cart-review" id="btn-confirm-dispatch-order" onclick="confirmAndDispatchOrder(<?php echo (int)($active_booking['id'] ?? 0); ?>, '<?php echo addslashes($b_ref); ?>')">
                    <i class="fa-solid fa-check-circle"></i>
                    <span>Confirm &amp; Send to Kitchen</span>
                </button>
            </div>
        </div>
    </div>

<?php endif; ?>

<!-- Floating Toast Container -->
<div id="portal-toast-container"></div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT: CONTROLLER, INACTIVITY TIMER, CAPTCHA, AJAX DINING & BILLING  -->
<!-- ========================================================================= -->
<script>
// 1. Mobile Sidebar Drawer Toggle
function toggleGuestSidebar() {
    var sb = document.getElementById('guest-sidebar');
    if (sb) {
        sb.classList.toggle('is-open');
    }
}

// 2. Tab Navigation Switcher (Dashboard, My Villa, Kitchen, Menu, Experiences, Folio)
var viewTitles = {
    'dashboard': { title: 'Stay Dashboard', sub: 'Kanthalloor High Range • 1,600m Elevation' },
    'my_villa': { title: 'My Villa & Amenities', sub: 'Villa Specs, Living Amenities & Stay Bill' },
    'kitchen': { title: 'Our Kitchen Orders', sub: 'Real-time sync with estate chef prep stations' },
    'menu': { title: 'Farm-to-Table Menu', sub: 'Select authentic Kanthalloor farm dishes to order' },
    'experiences': { title: 'Sanctuary Experiences', sub: 'Curated mountain rituals, walks & folklore' },
    'folio': { title: 'Payable Folio & Invoices', sub: 'Consolidated itemized charges & official tax bills' }
};

function switchGuestTab(tabKey) {
    // 1. Update Navigation Links
    document.querySelectorAll('.guest-nav-link').forEach(function(link) {
        if (link.getAttribute('data-view') === tabKey) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });

    // 2. Update View Panes
    document.querySelectorAll('.guest-view-pane').forEach(function(pane) {
        pane.classList.remove('active');
    });
    var targetPane = document.getElementById('view-' + tabKey);
    if (targetPane) {
        targetPane.classList.add('active');
    }

    // 3. Update Topbar Headings
    if (viewTitles[tabKey]) {
        var topTitle = document.getElementById('topbar-view-title');
        var topSub = document.getElementById('topbar-view-sub');
        if (topTitle) topTitle.textContent = viewTitles[tabKey].title;
        if (topSub) topSub.textContent = viewTitles[tabKey].sub;
    }

    // 4. Close Mobile Sidebar if Open
    var sb = document.getElementById('guest-sidebar');
    if (sb && sb.classList.contains('is-open')) {
        sb.classList.remove('is-open');
    }

    // 5. Update URL Hash
    if (window.history && window.history.replaceState) {
        window.history.replaceState(null, '', '#' + tabKey);
    }
}

// Auto-activate tab from URL Hash on page load
window.addEventListener('DOMContentLoaded', function() {
    var hash = window.location.hash.replace('#', '');
    if (hash && document.getElementById('view-' + hash)) {
        switchGuestTab(hash);
    }

    // Auth screen view switcher
    window.showAuthView = function(viewName) {
        clearAlert();
        var mainTabs = document.getElementById('main-auth-tabs');
        if (viewName === 'signin' || viewName === 'guest') {
            if (mainTabs) mainTabs.style.display = 'flex';
            document.querySelectorAll('.auth-tab-btn').forEach(function(b) {
                b.classList.remove('active');
                if (b.getAttribute('data-target') === 'pane-' + viewName) {
                    b.classList.add('active');
                }
            });
        } else {
            if (mainTabs) mainTabs.style.display = 'none';
        }

        document.querySelectorAll('.auth-pane').forEach(function(p) {
            p.classList.remove('active');
        });

        var target = document.getElementById('pane-' + viewName);
        if (target) {
            target.classList.add('active');
        }

        if (viewName === 'forgot') {
            var f1 = document.getElementById('forgot-step-1');
            var f2 = document.getElementById('forgot-step-2');
            if (f1) f1.style.display = 'block';
            if (f2) f2.style.display = 'none';
        }

        refreshGuestCaptcha();
    };

    document.querySelectorAll('.auth-tab-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            if (targetId === 'pane-signin') showAuthView('signin');
            else if (targetId === 'pane-guest') showAuthView('guest');
        });
    });
});

// 3. 5-Minute Inactivity Auto-Lock Countdown Timer
var INACTIVITY_LIMIT_SECONDS = 300; // 5 Minutes
var remainingSeconds = INACTIVITY_LIMIT_SECONDS;
var countdownEl = document.getElementById('inactivity-countdown');

function resetInactivityTimer() {
    remainingSeconds = INACTIVITY_LIMIT_SECONDS;
}

// User activity listener
['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart'].forEach(function(evt) {
    window.addEventListener(evt, resetInactivityTimer, { passive: true });
});

setInterval(function() {
    remainingSeconds--;
    if (remainingSeconds <= 0) {
        portalLogout('timeout');
    } else {
        if (countdownEl) {
            var mins = Math.floor(remainingSeconds / 60);
            var secs = remainingSeconds % 60;
            countdownEl.textContent = (mins < 10 ? '0' : '') + mins + ':' + (secs < 10 ? '0' : '') + secs;
            if (remainingSeconds <= 30) {
                countdownEl.style.color = '#F87171';
            } else {
                countdownEl.style.color = '#34D399';
            }
        }
    }
}, 1000);

// 4. Captcha Refresh Function
function refreshGuestCaptcha() {
    document.querySelectorAll('.guest-captcha-img').forEach(function(img) {
        img.src = 'api/captcha.php?type=guest&v=' + Date.now();
    });
}

// 5. Toast Messenger
function showPortalToast(message, isSuccess = true) {
    var container = document.getElementById('portal-toast-container');
    if (!container) return;

    var toast = document.createElement('div');
    toast.className = 'portal-toast';
    toast.innerHTML = (isSuccess ? '<i class="fa-solid fa-circle-check" style="color: #34D399; font-size: 16px;"></i>' : '<i class="fa-solid fa-circle-exclamation" style="color: #F87171; font-size: 16px;"></i>') +
                      '<span>' + message + '</span>';
    
    container.appendChild(toast);
    setTimeout(function() {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(function() { toast.remove(); }, 300);
    }, 4000);
}

function showAlert(msg, isError) {
    var box = document.getElementById('auth-alert-box');
    if (!box) return;
    box.className = 'portal-alert ' + (isError ? 'error' : 'success');
    box.innerHTML = (isError ? '<i class="fa-solid fa-circle-exclamation"></i> ' : '<i class="fa-solid fa-circle-check"></i> ') + '<span>' + msg + '</span>';
    box.style.display = 'flex';
}

function clearAlert() {
    var box = document.getElementById('auth-alert-box');
    if (box) {
        box.style.display = 'none';
        box.innerHTML = '';
    }
}

// 6. Sign In (Food Forest Account)
async function submitLogin() {
    var email = document.getElementById('login-email').value;
    var password = document.getElementById('login-password').value;
    var captcha = document.getElementById('login-captcha').value;
    var btn = document.getElementById('btn-login-submit');

    clearAlert();
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Authenticating...';

    try {
        var res = await fetch('api/guest_auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'login', email: email, password: password, captcha: captcha })
        });
        var data = await res.json();
        if (data.success) {
            showAlert('Login successful! Loading your Food Forest portal...', false);
            setTimeout(function() { window.location.href = 'guest_portal.php'; }, 800);
        } else {
            showAlert(data.message || 'Authentication failed. Please verify credentials.', true);
            refreshGuestCaptcha();
            btn.disabled = false;
            btn.innerHTML = '<span>Sign In to Food Forest Account</span> <i class="fa-solid fa-arrow-right"></i>';
        }
    } catch (err) {
        showAlert('Network communication error. Please try again.', true);
        refreshGuestCaptcha();
        btn.disabled = false;
        btn.innerHTML = '<span>Sign In to Food Forest Account</span> <i class="fa-solid fa-arrow-right"></i>';
    }
}

// 7. 30-Day Guest Pass Access
async function submitGuestAccess() {
    var ref = document.getElementById('guest-ref').value;
    var passcode = document.getElementById('guest-passcode').value;
    var captcha = document.getElementById('guest-captcha').value;
    var btn = document.getElementById('btn-guest-submit');

    clearAlert();
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verifying Pass...';

    try {
        var res = await fetch('api/guest_auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'guest_access', reference_code: ref, passcode: passcode, captcha: captcha })
        });
        var data = await res.json();
        if (data.success) {
            showAlert('Reservation pass verified! Loading your Food Forest portal...', false);
            setTimeout(function() { window.location.href = 'guest_portal.php'; }, 800);
        } else {
            showAlert(data.message || 'Passcode or Reference Code not recognized.', true);
            refreshGuestCaptcha();
            btn.disabled = false;
            btn.innerHTML = '<span>Access My Reservation</span> <i class="fa-solid fa-arrow-right"></i>';
        }
    } catch (err) {
        showAlert('Network error. Please try again.', true);
        refreshGuestCaptcha();
        btn.disabled = false;
        btn.innerHTML = '<span>Access My Reservation</span> <i class="fa-solid fa-arrow-right"></i>';
    }
}

// 8. New Account Registration & Optional Google Verification Handlers
var regDevOtpValue = '';

async function requestRegOtp() {
    var emailInput = document.getElementById('reg-email');
    var email = emailInput ? emailInput.value.trim() : '';

    if (!email || !email.includes('@')) {
        showAlert('Please enter a valid email address in the Email field before requesting verification.', true);
        if (emailInput) emailInput.focus();
        return;
    }

    clearAlert();
    var btn = document.getElementById('btn-reg-send-otp');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending 5-Digit Code...';
    }

    try {
        var res = await fetch('api/guest_auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'reg_send_otp', email: email })
        });
        var data = await res.json();
        if (data.success) {
            document.getElementById('reg-verify-action-box').style.display = 'none';
            document.getElementById('reg-verify-otp-box').style.display = 'block';
            document.getElementById('reg-verify-target-email').textContent = email;

            var devNotice = document.getElementById('reg-dev-notice');
            if (data.dev_otp && devNotice) {
                regDevOtpValue = data.dev_otp;
                devNotice.style.display = 'block';
                devNotice.innerHTML = '<i class="fa-solid fa-code"></i> Localhost Sandbox Code: <strong>' + data.dev_otp + '</strong> (Click to auto-fill)';
            } else if (devNotice) {
                devNotice.style.display = 'none';
                regDevOtpValue = '';
            }

            var codeInput = document.getElementById('reg-otp-code-input');
            if (codeInput) {
                codeInput.value = '';
                codeInput.focus();
            }
            showAlert(data.message || '5-digit code sent to ' + email, false);
        } else {
            showAlert(data.message || 'Could not send verification code.', true);
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send 5-Digit Verification Code';
            }
        }
    } catch (err) {
        showAlert('Network error requesting verification code.', true);
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send 5-Digit Verification Code';
        }
    }
}

function autofillRegDevOtp() {
    if (regDevOtpValue) {
        var codeInput = document.getElementById('reg-otp-code-input');
        if (codeInput) {
            codeInput.value = regDevOtpValue;
            codeInput.focus();
        }
    }
}

function cancelRegOtp() {
    document.getElementById('reg-verify-otp-box').style.display = 'none';
    var actBox = document.getElementById('reg-verify-action-box');
    if (actBox) actBox.style.display = 'block';
    var btn = document.getElementById('btn-reg-send-otp');
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Send 5-Digit Verification Code';
    }
}

async function verifyRegOtp() {
    var codeInput = document.getElementById('reg-otp-code-input');
    var otp = codeInput ? codeInput.value.trim() : '';

    if (!otp || otp.length !== 5) {
        showAlert('Please enter the 5-digit verification code.', true);
        return;
    }

    clearAlert();
    var btn = document.getElementById('btn-reg-verify-otp');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verifying...';
    }

    try {
        var res = await fetch('api/guest_auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'reg_verify_otp', otp: otp })
        });
        var data = await res.json();
        if (data.success) {
            document.getElementById('reg-verify-otp-box').style.display = 'none';
            document.getElementById('reg-verify-action-box').style.display = 'none';
            var successBox = document.getElementById('reg-verify-success-box');
            if (successBox) successBox.style.display = 'flex';
            var badgeStatus = document.getElementById('reg-verify-badge-status');
            if (badgeStatus) {
                badgeStatus.innerHTML = '<i class="fa-solid fa-circle-check" style="color:#38BDF8;"></i> Verified';
                badgeStatus.style.background = 'rgba(56, 189, 248, 0.15)';
                badgeStatus.style.color = '#38BDF8';
            }
            showAlert(data.message || 'Email verified! Blue Tick badge unlocked for your account.', false);
        } else {
            showAlert(data.message || 'Invalid verification code.', true);
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = 'Verify';
            }
        }
    } catch (err) {
        showAlert('Network error verifying code.', true);
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = 'Verify';
        }
    }
}

async function submitRegister() {
    var name = document.getElementById('reg-name').value;
    var email = document.getElementById('reg-email').value;
    var phone = document.getElementById('reg-phone').value;
    var password = document.getElementById('reg-password').value;
    var captcha = document.getElementById('reg-captcha').value;
    var btn = document.getElementById('btn-reg-submit');

    clearAlert();
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Registering Account...';

    try {
        var res = await fetch('api/guest_auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'register', full_name: name, email: email, phone: phone, password: password, captcha: captcha })
        });
        var data = await res.json();
        if (data.success) {
            showAlert(data.message || 'Account created successfully! Loading portal...', false);
            setTimeout(function() { window.location.href = 'guest_portal.php'; }, 800);
        } else {
            showAlert(data.message || 'Could not register account.', true);
            refreshGuestCaptcha();
            btn.disabled = false;
            btn.innerHTML = '<span>Create Food Forest Account</span> <i class="fa-solid fa-arrow-right"></i>';
        }
    } catch (err) {
        showAlert('Network error. Please try again.', true);
        refreshGuestCaptcha();
        btn.disabled = false;
        btn.innerHTML = '<span>Create Food Forest Account</span> <i class="fa-solid fa-arrow-right"></i>';
    }
}

// 9. Forgot Password Handlers
var currentForgotEmail = '';
var forgotDevOtpValue = '';

async function submitForgotSendOtp(isResend = false) {
    var email = isResend ? currentForgotEmail : (document.getElementById('forgot-email') ? document.getElementById('forgot-email').value.trim() : '');
    var captcha = isResend ? '' : (document.getElementById('forgot-captcha') ? document.getElementById('forgot-captcha').value.trim() : '');
    var btn = isResend ? document.getElementById('btn-forgot-resend') : document.getElementById('btn-forgot-request');

    if (!email) {
        showAlert('Please enter your registered email address.', true);
        return;
    }
    if (!isResend && !captcha) {
        showAlert('Please enter the security captcha characters shown.', true);
        return;
    }

    clearAlert();
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending Code...';
    }

    try {
        var res = await fetch('api/guest_auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'forgot_password_send_otp', email: email, captcha: captcha })
        });
        var data = await res.json();
        if (data.success) {
            currentForgotEmail = email;
            showAlert(data.message, false);

            document.getElementById('forgot-step-1').style.display = 'none';
            document.getElementById('forgot-step-2').style.display = 'block';
            var disp = document.getElementById('forgot-display-email');
            if (disp) disp.textContent = email;

            var devNotice = document.getElementById('forgot-dev-notice');
            if (data.dev_otp && devNotice) {
                forgotDevOtpValue = data.dev_otp;
                devNotice.style.display = 'block';
                devNotice.innerHTML = '<i class="fa-solid fa-code"></i> Localhost Sandbox Code: <strong>' + data.dev_otp + '</strong> (Click to auto-fill)';
            } else if (devNotice) {
                devNotice.style.display = 'none';
                forgotDevOtpValue = '';
            }

            var otpInput = document.getElementById('forgot-otp-input');
            if (otpInput) {
                otpInput.value = '';
                otpInput.focus();
            }
        } else {
            showAlert(data.message || 'Could not send verification code.', true);
            refreshGuestCaptcha();
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = isResend ? 'Resend Code' : '<span>Send 5-Digit Verification Code</span> <i class="fa-solid fa-paper-plane"></i>';
            }
        }
    } catch (err) {
        showAlert('Network error requesting code. Please try again.', true);
        refreshGuestCaptcha();
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = isResend ? 'Resend Code' : '<span>Send 5-Digit Verification Code</span> <i class="fa-solid fa-paper-plane"></i>';
        }
    }
}

function autofillForgotDevOtp() {
    if (forgotDevOtpValue) {
        var inp = document.getElementById('forgot-otp-input');
        if (inp) {
            inp.value = forgotDevOtpValue;
            inp.focus();
        }
    }
}

async function submitForgotReset() {
    var otpInput = document.getElementById('forgot-otp-input');
    var pwdInput = document.getElementById('forgot-new-password');
    var btn = document.getElementById('btn-forgot-reset');

    var otp = otpInput ? otpInput.value.trim() : '';
    var password = pwdInput ? pwdInput.value : '';

    if (!otp || otp.length !== 5) {
        showAlert('Please enter the 5-digit verification code.', true);
        return;
    }
    if (!password || password.length < 6) {
        showAlert('New password must be at least 6 characters.', true);
        return;
    }

    clearAlert();
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Updating Password...';

    try {
        var res = await fetch('api/guest_auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'forgot_password_reset', email: currentForgotEmail, otp: otp, password: password })
        });
        var data = await res.json();
        if (data.success) {
            showAlert(data.message || 'Password reset successful! Loading portal...', false);
            setTimeout(function() { window.location.href = 'guest_portal.php'; }, 800);
        } else {
            showAlert(data.message || 'Could not reset password. Please check your code.', true);
            btn.disabled = false;
            btn.innerHTML = '<span>Reset Password &amp; Sign In</span> <i class="fa-solid fa-arrow-right"></i>';
        }
    } catch (err) {
        showAlert('Network error resetting password. Please try again.', true);
        btn.disabled = false;
        btn.innerHTML = '<span>Reset Password &amp; Sign In</span> <i class="fa-solid fa-arrow-right"></i>';
    }
}

async function portalLogout(reason) {
    try {
        await fetch('api/guest_auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'logout' })
        });
    } catch (e) {}
    if (reason === 'timeout') {
        window.location.href = 'guest_portal.php?timeout=1';
    } else {
        window.location.href = 'guest_portal.php';
    }
}

async function promptUpgradeAccount(refCode) {
    var pwd = prompt("Create a secret password to convert this temporary guest pass into a permanent account:");
    if (!pwd || pwd.length < 4) {
        if (pwd !== null) alert("Password must be at least 4 characters.");
        return;
    }
    try {
        var res = await fetch('api/guest_auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'upgrade', reference_code: refCode, password: pwd })
        });
        var data = await res.json();
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert(data.message || "Failed to upgrade account.");
        }
    } catch (err) {
        alert("Error upgrading account. Please contact concierge.");
    }
}

// 7. Menu Filtering & Searching
function filterGuestMenu(category) {
    var bar = document.getElementById('menu-filter-bar');
    if (bar) {
        bar.querySelectorAll('.menu-filter-pill').forEach(function(pill) {
            pill.classList.remove('active');
        });
    }
    if (event && event.target) {
        event.target.classList.add('active');
    }

    var grid = document.getElementById('guest-menu-grid');
    if (!grid) return;

    grid.querySelectorAll('.menu-dish-card').forEach(function(card) {
        var cat = card.getAttribute('data-category');
        if (category === 'all' || cat === category) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

function searchGuestMenu(query) {
    var q = query.toLowerCase().trim();
    var grid = document.getElementById('guest-menu-grid');
    if (!grid) return;

    grid.querySelectorAll('.menu-dish-card').forEach(function(card) {
        var name = card.getAttribute('data-name');
        if (!q || name.indexOf(q) !== -1) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

function adjustPortalQty(dishId, delta) {
    var box = document.getElementById('qty-val-' + dishId);
    if (!box) return;
    var current = parseInt(box.textContent) || 1;
    var updated = Math.max(1, Math.min(20, current + delta));
    box.textContent = updated;
}

// =========================================================================
// 8. DINING TRAY CART & SECURITY CAPTCHA ORDER DISPATCH
// =========================================================================
var guestDiningTray = [];

function addToDiningTray(dishId, dishName, unitPrice, dietaryType) {
    var qtyBox = document.getElementById('qty-val-' + dishId);
    var mealSelect = document.getElementById('meal-select-' + dishId);
    var btn = document.getElementById('btn-order-' + dishId);

    var qty = qtyBox ? (parseInt(qtyBox.textContent) || 1) : 1;
    var mealTime = mealSelect ? mealSelect.value : 'lunch';

    // Check if dish + meal slot already exists in tray
    var existing = guestDiningTray.find(function(it) {
        return it.dishId === dishId && it.mealSlot === mealTime;
    });

    if (existing) {
        existing.quantity += qty;
    } else {
        guestDiningTray.push({
            dishId: dishId,
            dishName: dishName,
            unitPrice: parseFloat(unitPrice) || 0,
            quantity: qty,
            mealSlot: mealTime,
            dietaryType: dietaryType || 'veg'
        });
    }

    // Button feedback
    if (btn) {
        var origHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Added';
        btn.style.background = '#10B981';
        btn.style.color = '#FFFFFF';
        setTimeout(function() {
            btn.innerHTML = origHtml;
            btn.style.background = '';
            btn.style.color = '';
        }, 900);
    }

    // Reset stepper back to 1
    if (qtyBox) qtyBox.textContent = '1';

    updateTrayUI();
    showPortalToast('✓ Added: ' + qty + 'x ' + dishName + ' (' + mealTime.toUpperCase() + ') to Tray', true);
}

function updateTrayUI() {
    var totalCount = 0;
    var subtotal = 0;

    guestDiningTray.forEach(function(item) {
        totalCount += item.quantity;
        subtotal += (item.quantity * item.unitPrice);
    });

    var cur = '<?php echo $currency; ?>';

    // Update Header Badge
    var headerCount = document.getElementById('tray-header-count');
    if (headerCount) headerCount.textContent = totalCount;

    // Update Floating Bar
    var floatBar = document.getElementById('guest-cart-floating-bar');
    var floatCount = document.getElementById('floating-cart-count');
    var floatText = document.getElementById('floating-cart-items-text');
    var floatTotal = document.getElementById('floating-cart-total');

    if (floatBar) {
        if (totalCount > 0) {
            floatBar.style.display = 'flex';
            if (floatCount) floatCount.textContent = totalCount;
            if (floatText) floatText.textContent = totalCount + (totalCount === 1 ? ' dish in tray' : ' dishes in tray');
            if (floatTotal) floatTotal.textContent = cur + subtotal.toFixed(2);
        } else {
            floatBar.style.display = 'none';
        }
    }
}

function clearDiningTray() {
    if (guestDiningTray.length === 0) return;
    guestDiningTray = [];
    updateTrayUI();
    closeOrderReviewModal();
    showPortalToast('Dining tray emptied.', false);
}

function openOrderReviewModal() {
    if (guestDiningTray.length === 0) {
        showPortalToast('Your order tray is currently empty. Add dishes from the menu first!', false);
        return;
    }
    renderTrayModalTable();
    refreshOrderCaptcha();

    var errBox = document.getElementById('order-captcha-err');
    if (errBox) {
        errBox.textContent = '';
        errBox.style.display = 'none';
    }

    var modal = document.getElementById('modal-order-review');
    if (modal) modal.style.display = 'flex';

    setTimeout(function() {
        var inp = document.getElementById('order-captcha-input');
        if (inp) inp.focus();
    }, 150);
}

function closeOrderReviewModal() {
    var modal = document.getElementById('modal-order-review');
    if (modal) modal.style.display = 'none';
}

function renderTrayModalTable() {
    var tbody = document.getElementById('tray-modal-table-body');
    if (!tbody) return;

    var cur = '<?php echo $currency; ?>';
    var subtotal = 0;

    if (guestDiningTray.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding: 24px; color: #94A3B8;">No dishes in your tray.</td></tr>';
        return;
    }

    var html = '';
    guestDiningTray.forEach(function(item, idx) {
        var lineTotal = item.quantity * item.unitPrice;
        subtotal += lineTotal;

        var dietBadge = '';
        if (item.dietaryType === 'veg') {
            dietBadge = '<span style="font-size: 10px; color: #34D399; margin-left: 5px;">🌱</span>';
        } else if (item.dietaryType === 'non-veg') {
            dietBadge = '<span style="font-size: 10px; color: #F87171; margin-left: 5px;">🍗</span>';
        }

        var slotLabel = item.mealSlot.charAt(0).toUpperCase() + item.mealSlot.slice(1);

        html += '<tr>' +
            '<td><strong style="color: #FFFFFF;">' + item.dishName + '</strong>' + dietBadge + '</td>' +
            '<td><span style="font-size: 11px; background: rgba(255,255,255,0.08); padding: 2px 7px; border-radius: 4px; color: #CBD5E1;">' + slotLabel + '</span></td>' +
            '<td style="text-align: center;">' +
                '<div style="display: inline-flex; align-items: center; background: rgba(0,0,0,0.4); border-radius: 5px; border: 1px solid rgba(255,255,255,0.12);">' +
                    '<button type="button" style="width: 24px; height: 24px; background: transparent; color: #fff; font-weight: 700; border: none; cursor: pointer;" onclick="adjustTrayItemQty(' + idx + ', -1)">−</button>' +
                    '<span style="width: 22px; text-align: center; font-size: 12px; font-weight: 700; color: #FFFFFF;">' + item.quantity + '</span>' +
                    '<button type="button" style="width: 24px; height: 24px; background: transparent; color: #fff; font-weight: 700; border: none; cursor: pointer;" onclick="adjustTrayItemQty(' + idx + ', 1)">+</button>' +
                '</div>' +
            '</td>' +
            '<td style="text-align: right; color: #94A3B8; font-family: monospace;">' + cur + item.unitPrice.toFixed(2) + '</td>' +
            '<td style="text-align: right; font-weight: 700; color: #C5A059; font-family: monospace;">' + cur + lineTotal.toFixed(2) + '</td>' +
            '<td style="text-align: center;">' +
                '<button type="button" onclick="removeTrayItem(' + idx + ')" title="Remove item" style="background: transparent; border: none; color: #94A3B8; cursor: pointer; padding: 4px; border-radius: 4px; transition: 0.2s;" onmouseover="this.style.color=\'#F87171\'" onmouseout="this.style.color=\'#94A3B8\'">' +
                    '<i class="fa-solid fa-xmark"></i>' +
                '</button>' +
            '</td>' +
        '</tr>';
    });

    tbody.innerHTML = html;

    var gst = subtotal * 0.05;
    var grandTotal = subtotal + gst;

    var sSub = document.getElementById('modal-summary-subtotal');
    if (sSub) sSub.textContent = cur + subtotal.toFixed(2);

    var sTax = document.getElementById('modal-summary-tax');
    if (sTax) sTax.textContent = cur + gst.toFixed(2);

    var sTot = document.getElementById('modal-summary-total');
    if (sTot) sTot.textContent = cur + grandTotal.toFixed(2);
}

function adjustTrayItemQty(idx, delta) {
    if (!guestDiningTray[idx]) return;
    var newQty = guestDiningTray[idx].quantity + delta;
    if (newQty <= 0) {
        removeTrayItem(idx);
    } else {
        guestDiningTray[idx].quantity = Math.min(30, newQty);
        renderTrayModalTable();
        updateTrayUI();
    }
}

function removeTrayItem(idx) {
    if (!guestDiningTray[idx]) return;
    var removedName = guestDiningTray[idx].dishName;
    guestDiningTray.splice(idx, 1);
    renderTrayModalTable();
    updateTrayUI();
    if (guestDiningTray.length === 0) {
        closeOrderReviewModal();
        showPortalToast('Tray emptied: removed ' + removedName, false);
    }
}

function refreshOrderCaptcha() {
    var img = document.getElementById('order-captcha-img');
    if (img) img.src = 'api/captcha.php?type=order&v=' + Date.now();
    var inp = document.getElementById('order-captcha-input');
    if (inp) inp.value = '';
    var errBox = document.getElementById('order-captcha-err');
    if (errBox) {
        errBox.textContent = '';
        errBox.style.display = 'none';
    }
}

async function confirmAndDispatchOrder(bookingId, refCode) {
    if (guestDiningTray.length === 0) {
        showPortalToast('Your order tray is empty.', false);
        return;
    }

    var inp = document.getElementById('order-captcha-input');
    var code = inp ? inp.value.trim() : '';
    var errBox = document.getElementById('order-captcha-err');

    if (!code) {
        if (errBox) {
            errBox.textContent = 'Please enter the security verification code.';
            errBox.style.display = 'block';
        }
        if (inp) inp.focus();
        return;
    }

    var btn = document.getElementById('btn-confirm-dispatch-order');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verifying & Dispatching...';
    }

    var payloadItems = guestDiningTray.map(function(item) {
        return {
            dish_id: item.dishId,
            dish_name: item.dishName,
            meal_time: item.mealSlot,
            quantity: item.quantity,
            unit_price: item.unitPrice
        };
    });

    try {
        var res = await fetch('api/order_food.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'batch_order',
                booking_id: bookingId,
                reference_code: refCode,
                captcha: code,
                items: payloadItems
            })
        });

        var data = await res.json();

        if (data.success) {
            showPortalToast('✓ ' + (data.message || 'Dishes successfully dispatched to Estate Kitchen!'), true);

            // Update KPI stats & Badges
            var cur = '<?php echo $currency; ?>';
            var fTotal = parseFloat(data.food_total) || 0;
            var gTotal = parseFloat(data.grand_total) || 0;
            var balDue = parseFloat(data.balance_due) || 0;
            var fCount = data.food_count || 0;

            var kpiFTotal = document.getElementById('kpi-food-total');
            if (kpiFTotal) kpiFTotal.textContent = cur + fTotal.toFixed(2);
            var kpiFCount = document.getElementById('kpi-food-count');
            if (kpiFCount) kpiFCount.textContent = fCount + ' Dishes Ordered';

            var kpiBal = document.getElementById('kpi-bal-val');
            if (kpiBal) {
                kpiBal.textContent = cur + balDue.toFixed(2);
                kpiBal.style.color = (balDue <= 0 ? '#34D399' : '#FBBF24');
            }
            var kpiBalStatus = document.getElementById('kpi-bal-status');
            if (kpiBalStatus) {
                kpiBalStatus.textContent = (balDue <= 0 ? '✓ SETTLED IN FULL' : 'DUE AT CHECKOUT');
                kpiBalStatus.style.color = (balDue <= 0 ? '#34D399' : '#F59E0B');
            }

            // Update Sidebar Badges
            var navFBadge = document.getElementById('nav-food-count-badge');
            if (navFBadge) {
                navFBadge.textContent = fCount;
            } else {
                var kitchenLink = document.querySelector(".guest-nav-link[data-view='kitchen']");
                if (kitchenLink) {
                    var newBadge = document.createElement('span');
                    newBadge.className = 'guest-nav-badge';
                    newBadge.id = 'nav-food-count-badge';
                    newBadge.textContent = fCount;
                    kitchenLink.appendChild(newBadge);
                }
            }

            // Update Kitchen View Badges & Table
            var kDishesCount = document.getElementById('kitchen-dishes-count-badge');
            if (kDishesCount) kDishesCount.textContent = fCount + ' Items';
            var kDishesTotal = document.getElementById('kitchen-dishes-total-badge');
            if (kDishesTotal) kDishesTotal.textContent = cur + fTotal.toFixed(2);

            var kTableWrap = document.getElementById('cottage-kitchen-table-wrap');
            if (kTableWrap && data.food_items) {
                var html = '<table class="g-table"><thead><tr><th>Dish Name</th><th>Meal Slot</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Unit Rate</th><th style="text-align:right;">Subtotal</th><th style="text-align:center;">Kitchen Status</th></tr></thead><tbody>';
                data.food_items.forEach(function(item) {
                    var iName = item.heading || 'Custom Farm Meal';
                    var iSlot = (item.meal_time || item.category || 'Dining');
                    var iQty = parseInt(item.quantity) || 1;
                    var iRate = parseFloat(item.price) || 0;
                    var iSub = parseFloat(item.subtotal) || (iQty * iRate);
                    html += '<tr><td><strong style="color:#FFFFFF;">' + iName + '</strong></td><td><span style="font-size:11px;background:rgba(255,255,255,0.06);padding:3px 8px;border-radius:4px;">' + iSlot.charAt(0).toUpperCase() + iSlot.slice(1) + '</span></td><td style="text-align:center;font-weight:700;color:#FFFFFF;">' + iQty + '</td><td style="text-align:right;color:#94A3B8;">' + cur + iRate.toFixed(2) + '</td><td style="text-align:right;font-weight:700;color:#C5A059;">' + cur + iSub.toFixed(2) + '</td><td style="text-align:center;"><span class="status-badge-kitchen queued">⏳ Queued</span></td></tr>';
                });
                html += '</tbody></table>';
                kTableWrap.innerHTML = html;
            }

            // Update Stay Dashboard Summary Box
            var dashSummaryBox = document.getElementById('dash-ordered-summary-box');
            if (dashSummaryBox && data.food_items) {
                var dHtml = '<table class="g-table"><thead><tr><th>Dish</th><th>Meal Time</th><th style="text-align: center;">Qty</th><th style="text-align: right;">Price</th></tr></thead><tbody>';
                data.food_items.slice(0, 5).forEach(function(sItem) {
                    var sName = sItem.heading || 'Custom Farm Meal';
                    var sSlot = (sItem.meal_time || sItem.category || 'Dining');
                    sSlot = sSlot.charAt(0).toUpperCase() + sSlot.slice(1);
                    var sQty = parseInt(sItem.quantity) || 1;
                    var sPrice = parseFloat(sItem.price) || 0;
                    dHtml += '<tr><td><strong style="color: #FFFFFF;">' + sName + '</strong></td><td><span style="font-size: 11px; color: #94A3B8; background: rgba(255,255,255,0.06); padding: 2px 6px; border-radius: 4px;">' + sSlot + '</span></td><td style="text-align: center;">' + sQty + '</td><td style="text-align: right; color: #C5A059; font-weight: 700;">' + cur + (sQty * sPrice).toFixed(2) + '</td></tr>';
                });
                dHtml += '</tbody></table>';
                if (data.food_items.length > 5) {
                    dHtml += '<div style="text-align: center; margin-top: 12px;"><button type="button" onclick="switchGuestTab(\'kitchen\')" style="background: transparent; color: #C5A059; font-size: 12px; font-weight: 600;">View all ' + data.food_items.length + ' dishes in Kitchen &rarr;</button></div>';
                }
                dashSummaryBox.innerHTML = dHtml;
            }

            // Update Folio View
            var folioFVal = document.getElementById('folio-food-val');
            if (folioFVal) folioFVal.textContent = cur + fTotal.toFixed(2);
            var folioFCount = document.getElementById('folio-food-count');
            if (folioFCount) folioFCount.textContent = fCount;

            var folioCGST = document.getElementById('folio-cgst-val');
            if (folioCGST && data.cgst_amount) folioCGST.textContent = cur + parseFloat(data.cgst_amount).toFixed(2);
            var folioSGST = document.getElementById('folio-sgst-val');
            if (folioSGST && data.sgst_amount) folioSGST.textContent = cur + parseFloat(data.sgst_amount).toFixed(2);

            var folioNet = document.getElementById('folio-net-total-val');
            if (folioNet) folioNet.textContent = cur + gTotal.toFixed(2);

            var folioBal = document.getElementById('folio-balance-val');
            if (folioBal) {
                folioBal.textContent = cur + balDue.toFixed(2);
                folioBal.style.color = (balDue <= 0 ? '#34D399' : '#F59E0B');
            }
            var folioStatusBadge = document.getElementById('folio-balance-status-badge');
            if (folioStatusBadge) {
                folioStatusBadge.textContent = (balDue <= 0 ? '✓ SETTLED IN FULL' : 'PAYMENT DUE AT CHECKOUT');
            }

            // Clear Cart & Close Modal
            guestDiningTray = [];
            updateTrayUI();
            closeOrderReviewModal();

            // Switch to Kitchen tab so guest immediately sees their queued order!
            setTimeout(function() {
                switchGuestTab('kitchen');
            }, 600);

        } else {
            if (errBox) {
                errBox.textContent = data.message || 'Could not place food order.';
                errBox.style.display = 'block';
            }
            showPortalToast(data.message || 'Could not place food order.', false);
            refreshOrderCaptcha();
            if (inp) {
                inp.value = '';
                inp.focus();
            }
        }
    } catch (err) {
        showPortalToast('Network communication error.', false);
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check-circle"></i> <span>Confirm &amp; Send to Kitchen</span>';
        }
    }
}

// Backward Compatibility fallback
function orderDishToCottage(bookingId, refCode, dishId, dishName, unitPrice) {
    addToDiningTray(dishId, dishName, unitPrice, 'veg');
    openOrderReviewModal();
}
</script>

</body>
</html>
