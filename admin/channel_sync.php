<?php
// =========================================================================
// Food Forest Sanctuary — OTA Channel Manager & iCal Sync Center
// File: admin/channel_sync.php
// Connects MakeMyTrip (InGoMMT), Airbnb, Booking.com, Agoda with Food Forest.
// =========================================================================
$page_title = 'OTA Channel Sync';
$page_subtitle = '2-Way iCalendar synchronization with MakeMyTrip, Airbnb & Booking.com';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/ical_sync.php';

$pdo = get_db();
ensure_ical_and_channel_schema($pdo);

// Determine base URL for export links
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . $host . rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/\\');

$alert_message = '';
$alert_type = 'success';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $alert_message = 'Security validation failed.';
        $alert_type = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        // Add new external feed
        if ($action === 'add_feed') {
            $room_slug = trim($_POST['room_slug'] ?? 'treehouse');
            $channel_name = trim($_POST['channel_name'] ?? 'MakeMyTrip');
            $feed_url = trim($_POST['feed_url'] ?? '');

            if (empty($feed_url)) {
                $alert_message = 'Please provide a valid iCalendar (.ics) feed URL.';
                $alert_type = 'error';
            } else {
                $ins = $pdo->prepare("INSERT INTO room_ical_feeds (room_slug, channel_name, feed_url, sync_status) VALUES (?, ?, ?, 'pending')");
                $ins->execute([$room_slug, $channel_name, $feed_url]);
                $new_id = $pdo->lastInsertId();

                // Trigger immediate first sync
                $sync_res = sync_single_feed($pdo, $new_id);
                $alert_message = "Channel feed for {$channel_name} connected! " . ($sync_res['message'] ?? '');
                $alert_type = $sync_res['success'] ? 'success' : 'warning';
            }
        }

        // Sync a specific feed
        elseif ($action === 'sync_feed') {
            $feed_id = (int)$_POST['feed_id'];
            $sync_res = sync_single_feed($pdo, $feed_id);
            $alert_message = $sync_res['message'];
            $alert_type = $sync_res['success'] ? 'success' : 'error';
        }

        // Sync all feeds
        elseif ($action === 'sync_all') {
            $sync_res = sync_all_ical_feeds($pdo);
            $alert_message = "All Channels Synced: {$sync_res['successful_feeds']} / {$sync_res['total_feeds']} feeds active. Total {$sync_res['total_events_synced']} reservations updated.";
            $alert_type = 'success';
        }

        // Delete feed
        elseif ($action === 'delete_feed') {
            $feed_id = (int)$_POST['feed_id'];
            $pdo->prepare("DELETE FROM room_ical_feeds WHERE id = ?")->execute([$feed_id]);
            $alert_message = "Channel feed deleted.";
            $alert_type = 'success';
        }

        // Toggle feed active status
        elseif ($action === 'toggle_feed') {
            $feed_id = (int)$_POST['feed_id'];
            $pdo->prepare("UPDATE room_ical_feeds SET is_active = IF(is_active=1, 0, 1) WHERE id = ?")->execute([$feed_id]);
            $alert_message = "Feed status updated.";
            $alert_type = 'success';
        }
    }
}

// Fetch all rooms and connected feeds
$rooms = $pdo->query("SELECT id, slug, title, stay_type FROM rooms ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$feeds = $pdo->query("SELECT f.*, r.title AS room_title FROM room_ical_feeds f LEFT JOIN rooms r ON f.room_slug = r.slug ORDER BY f.id DESC")->fetchAll(PDO::FETCH_ASSOC);
$synced_ota_bookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE booking_source != 'direct_website' AND status NOT IN ('cancelled', 'rejected')")->fetchColumn();
?>

<div class="adm-content-container">

    <?php if (!empty($alert_message)): ?>
        <div class="adm-alert adm-alert-<?php echo $alert_type; ?>" style="margin-bottom: 20px;">
            <i class="fa-solid fa-<?php echo $alert_type === 'success' ? 'circle-check' : 'circle-exclamation'; ?>"></i>
            <span><?php echo htmlspecialchars($alert_message); ?></span>
        </div>
    <?php endif; ?>

    <!-- KPI Summary Row -->
    <div class="adm-kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px;">
        <div class="adm-kpi-card">
            <div class="adm-kpi-label">Connected OTA Feeds</div>
            <div class="adm-kpi-number"><?php echo count($feeds); ?></div>
            <span class="adm-kpi-trend">MakeMyTrip, Airbnb, Booking.com</span>
        </div>
        <div class="adm-kpi-card" style="border-left: 3px solid #e74c3c;">
            <div class="adm-kpi-label">Imported OTA Reservations</div>
            <div class="adm-kpi-number"><?php echo (int)$synced_ota_bookings; ?></div>
            <span class="adm-kpi-trend">Live Locked Dates</span>
        </div>
        <div class="adm-kpi-card" style="border-left: 3px solid var(--adm-gold);">
            <div class="adm-kpi-label">Sync Automation</div>
            <div class="adm-kpi-number">2-Way iCal</div>
            <span class="adm-kpi-trend">Universal Compatibility (Zero Fees)</span>
        </div>
    </div>

    <!-- 1. EXPORT SECTION: Export Food Forest Calendars to OTAs -->
    <div class="adm-card" style="margin-bottom: 24px;">
        <div class="adm-card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(197,160,89,0.25); padding-bottom: 12px; margin-bottom: 16px;">
            <div>
                <h3 style="font-family: var(--adm-font-title); font-size: 18px; color: #FFFFFF; margin: 0;">
                    <i class="fa-solid fa-cloud-arrow-up" style="color: var(--adm-gold);"></i> Step 1: Export Food Forest Calendars to OTAs
                </h3>
                <p style="font-size: 12.5px; color: var(--adm-text-muted); margin: 4px 0 0;">
                    Copy these URLs and paste them into your <strong>MakeMyTrip InGoMMT</strong>, <strong>Airbnb</strong>, and <strong>Booking.com</strong> Extranets to automatically block direct bookings across all platforms.
                </p>
            </div>
            <form method="POST" action="channel_sync.php" style="margin: 0;">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="action" value="sync_all">
                <button type="submit" class="adm-btn-action gold" style="display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-arrows-rotate"></i> Sync All Channels Now
                </button>
            </form>
        </div>

        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($rooms as $r): 
                $feed_export_url = $base_url . '/api/ical_export.php?room=' . urlencode($r['slug']);
            ?>
                <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; background: #0c1c13; border: 1px solid rgba(197,160,89,0.25); border-radius: 8px; padding: 12px 16px; gap: 12px;">
                    <div style="min-width: 200px;">
                        <span style="font-weight: 700; color: #FFFFFF; font-size: 13.5px;"><?php echo htmlspecialchars($r['title']); ?></span>
                        <span style="display: block; font-size: 11px; color: var(--adm-gold); font-family: monospace;">Slug: <?php echo htmlspecialchars($r['slug']); ?></span>
                    </div>

                    <div style="flex: 1; min-width: 260px; max-width: 600px;">
                        <input type="text" class="adm-input" readonly value="<?php echo htmlspecialchars($feed_export_url); ?>" id="feed-url-<?php echo $r['id']; ?>" style="font-family: monospace; font-size: 11.5px; background: #06110a; color: var(--adm-gold);" onclick="this.select()">
                    </div>

                    <div>
                        <button type="button" class="adm-btn-action outline" onclick="copyFeedUrl('feed-url-<?php echo $r['id']; ?>', this)" style="padding: 7px 14px; font-size: 12px;">
                            <i class="fa-solid fa-copy"></i> Copy iCal URL
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Instructions Box -->
        <div style="margin-top: 18px; padding: 14px 18px; background: rgba(197,160,89,0.08); border-left: 3px solid var(--adm-gold); border-radius: 4px; font-size: 12px; color: #EAEFED; line-height: 1.6;">
            <strong style="color: var(--adm-gold); font-size: 13px;"><i class="fa-solid fa-lightbulb"></i> Where to paste in MakeMyTrip (InGoMMT):</strong><br>
            1. Log in to your <a href="https://www.ingommt.com/" target="_blank" style="color: var(--adm-gold); text-decoration: underline;">MakeMyTrip InGoMMT Partner Extranet</a>.<br>
            2. Go to <strong>Rates & Inventory &rarr; Calendar Sync / Channel Manager</strong>.<br>
            3. Click <strong>"Import Calendar"</strong> &rarr; Select the Room (e.g. <em>Woodhouse Duplex</em>) &rarr; Paste the Food Forest Export URL copied above.<br>
            4. MakeMyTrip will now automatically block dates whenever someone books on your website!
        </div>
    </div>

    <!-- 2. IMPORT SECTION: Connect Inbound Feeds from MakeMyTrip / Airbnb -->
    <div class="adm-card" style="margin-bottom: 24px;">
        <div class="adm-card-header" style="border-bottom: 1px solid rgba(197,160,89,0.25); padding-bottom: 12px; margin-bottom: 16px;">
            <h3 style="font-family: var(--adm-font-title); font-size: 18px; color: #FFFFFF; margin: 0;">
                <i class="fa-solid fa-cloud-arrow-down" style="color: var(--adm-gold);"></i> Step 2: Import OTA Feeds (MakeMyTrip / Airbnb &rarr; Food Forest)
            </h3>
            <p style="font-size: 12.5px; color: var(--adm-text-muted); margin: 4px 0 0;">
                Paste the export link provided by MakeMyTrip, Airbnb, or Booking.com below so your direct website blocks those dates instantly.
            </p>
        </div>

        <!-- Add Feed Form -->
        <form method="POST" action="channel_sync.php" style="background: #0c1c13; border: 1px solid rgba(197,160,89,0.25); border-radius: 8px; padding: 16px 20px; margin-bottom: 20px;">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="add_feed">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; align-items: end;">
                <div>
                    <label class="adm-form-label">Chalet / Room *</label>
                    <select name="room_slug" class="adm-input" required>
                        <?php foreach ($rooms as $r): ?>
                            <option value="<?php echo htmlspecialchars($r['slug']); ?>">
                                <?php echo htmlspecialchars($r['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="adm-form-label">OTA Channel *</label>
                    <select name="channel_name" class="adm-input" required>
                        <option value="MakeMyTrip">MakeMyTrip (InGoMMT)</option>
                        <option value="Airbnb">Airbnb</option>
                        <option value="Booking.com">Booking.com</option>
                        <option value="Agoda">Agoda</option>
                        <option value="Goibibo">Goibibo</option>
                        <option value="Google Calendar">Google Calendar</option>
                    </select>
                </div>

                <div style="grid-column: span 2;">
                    <label class="adm-form-label">OTA Calendar Export URL (.ics) *</label>
                    <input type="url" name="feed_url" class="adm-input" placeholder="https://www.ingommt.com/calendar/export/... or https://www.airbnb.com/calendar/ical/..." required>
                </div>

                <div>
                    <button type="submit" class="adm-btn-action gold" style="width: 100%; justify-content: center; padding: 10px 16px;">
                        <i class="fa-solid fa-link"></i> Connect Channel
                    </button>
                </div>
            </div>
        </form>

        <!-- Connected Feeds Table -->
        <?php if (empty($feeds)): ?>
            <div style="text-align: center; padding: 30px 20px; background: rgba(0,0,0,0.15); border-radius: 8px; border: 1px dashed rgba(197,160,89,0.3);">
                <i class="fa-solid fa-arrows-split-up-and-left" style="font-size: 32px; color: var(--adm-gold); margin-bottom: 10px;"></i>
                <h4 style="color: #FFFFFF; font-size: 15px; margin: 0 0 6px;">No External OTA Feeds Connected Yet</h4>
                <p style="font-size: 12.5px; color: var(--adm-text-muted); margin: 0;">Connect your MakeMyTrip or Airbnb calendar export link using the form above to enable 2-way sync.</p>
            </div>
        <?php else: ?>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Channel</th>
                            <th>Chalet</th>
                            <th>Last Synced</th>
                            <th>Events</th>
                            <th>Status</th>
                            <th>Active</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feeds as $f): 
                            $st = $f['sync_status'];
                            $badge_class = ($st === 'success') ? 'confirmed' : (($st === 'error') ? 'rejected' : 'pending');
                        ?>
                            <tr>
                                <td>
                                    <strong style="color: #FFFFFF;"><?php echo htmlspecialchars($f['channel_name']); ?></strong>
                                    <div style="font-size: 10.5px; color: var(--adm-text-muted); max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($f['feed_url']); ?>">
                                        <?php echo htmlspecialchars($f['feed_url']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span style="color: var(--adm-gold); font-weight: 600;"><?php echo htmlspecialchars($f['room_title'] ?: $f['room_slug']); ?></span>
                                </td>
                                <td>
                                    <?php echo !empty($f['last_synced_at']) ? date('d M Y, h:i A', strtotime($f['last_synced_at'])) : '<span style="color: var(--adm-text-muted);">Never</span>'; ?>
                                </td>
                                <td>
                                    <span style="font-weight: 700; color: #FFFFFF;"><?php echo (int)$f['sync_events_count']; ?> Bookings</span>
                                </td>
                                <td>
                                    <span class="adm-status-badge <?php echo $badge_class; ?>">
                                        <?php echo strtoupper($st); ?>
                                    </span>
                                    <?php if (!empty($f['sync_error'])): ?>
                                        <div style="font-size: 10px; color: #e74c3c; margin-top: 2px;" title="<?php echo htmlspecialchars($f['sync_error']); ?>">
                                            <?php echo htmlspecialchars(substr($f['sync_error'], 0, 30)) . '...'; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" action="channel_sync.php" style="margin:0;">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <input type="hidden" name="action" value="toggle_feed">
                                        <input type="hidden" name="feed_id" value="<?php echo $f['id']; ?>">
                                        <button type="submit" class="adm-btn-action" style="padding: 4px 8px; font-size: 11px; background: <?php echo $f['is_active'] ? 'rgba(39, 174, 96, 0.2)' : 'rgba(231, 76, 60, 0.2)'; ?>; border: 1px solid <?php echo $f['is_active'] ? '#27ae60' : '#e74c3c'; ?>; color: <?php echo $f['is_active'] ? '#27ae60' : '#e74c3c'; ?>;">
                                            <?php echo $f['is_active'] ? 'Active' : 'Paused'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <form method="POST" action="channel_sync.php" style="margin:0;">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="action" value="sync_feed">
                                            <input type="hidden" name="feed_id" value="<?php echo $f['id']; ?>">
                                            <button type="submit" class="adm-btn-icon view" title="Sync This Feed Now">
                                                <i class="fa-solid fa-arrows-rotate"></i>
                                            </button>
                                        </form>

                                        <form method="POST" action="channel_sync.php" style="margin:0;" onsubmit="return confirm('Delete this channel connection?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                            <input type="hidden" name="action" value="delete_feed">
                                            <input type="hidden" name="feed_id" value="<?php echo $f['id']; ?>">
                                            <button type="submit" class="adm-btn-icon delete" title="Delete Channel Connection">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- 3. COMPREHENSIVE STEP-BY-STEP PROCEDURE GUIDE -->
    <div class="adm-card" style="margin-bottom: 24px;">
        <div class="adm-card-header" style="border-bottom: 1px solid rgba(197,160,89,0.25); padding-bottom: 14px; margin-bottom: 20px;">
            <h3 style="font-family: var(--adm-font-title); font-size: 19px; color: #FFFFFF; margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-book-open-reader" style="color: var(--adm-gold);"></i> Detailed Step-by-Step Integration Procedure
            </h3>
            <p style="font-size: 13px; color: var(--adm-text-muted); margin: 6px 0 0;">
                Follow these exact steps for each external platform to establish seamless two-way calendar sync.
            </p>
        </div>

        <!-- Platform Tabs Bar -->
        <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid rgba(197,160,89,0.15); padding-bottom: 12px;">
            <button type="button" class="adm-btn-action guide-tab-btn active" data-tab="guide-mmt" onclick="switchGuideTab('guide-mmt', this)" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px;">
                <i class="fa-solid fa-plane-arrival" style="color: #e74c3c;"></i> MakeMyTrip (InGoMMT)
            </button>
            <button type="button" class="adm-btn-action guide-tab-btn outline" data-tab="guide-airbnb" onclick="switchGuideTab('guide-airbnb', this)" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px;">
                <i class="fa-brands fa-airbnb" style="color: #FF5A5F;"></i> Airbnb
            </button>
            <button type="button" class="adm-btn-action guide-tab-btn outline" data-tab="guide-booking" onclick="switchGuideTab('guide-booking', this)" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px;">
                <i class="fa-solid fa-b" style="color: #38bdf8;"></i> Booking.com
            </button>
            <button type="button" class="adm-btn-action guide-tab-btn outline" data-tab="guide-agoda" onclick="switchGuideTab('guide-agoda', this)" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px;">
                <i class="fa-solid fa-hotel" style="color: #2ecc71;"></i> Agoda (YCS)
            </button>
            <button type="button" class="adm-btn-action guide-tab-btn outline" data-tab="guide-cron" onclick="switchGuideTab('guide-cron', this)" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px;">
                <i class="fa-solid fa-clock-rotate-left" style="color: var(--adm-gold);"></i> Automated 24/7 Cron Setup
            </button>
        </div>

        <!-- 1. MakeMyTrip Guide Content -->
        <div class="guide-tab-content" id="guide-mmt" style="display: block;">
            <div style="background: rgba(231, 76, 60, 0.08); border-left: 3px solid #e74c3c; border-radius: 6px; padding: 14px 18px; margin-bottom: 20px;">
                <h4 style="color: #FFFFFF; font-size: 15px; margin: 0 0 6px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-plane-arrival" style="color: #e74c3c;"></i> MakeMyTrip / Goibibo (InGoMMT Partner Extranet)
                </h4>
                <p style="font-size: 12.5px; color: #EAEFED; margin: 0;">
                    MakeMyTrip and Goibibo share the unified <strong>InGoMMT Partner Extranet</strong>. Connecting your calendar here automatically syncs both MakeMyTrip and Goibibo listings simultaneously.
                </p>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                
                <div style="display: flex; gap: 14px; align-items: flex-start;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #e74c3c; color: #FFFFFF; font-weight: 800; font-size: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">1</div>
                    <div style="flex: 1;">
                        <strong style="color: #FFFFFF; font-size: 14px;">Log in to InGoMMT Extranet</strong>
                        <p style="font-size: 12.5px; color: var(--adm-text-muted); margin: 4px 0 6px;">
                            Open <a href="https://www.ingommt.com/" target="_blank" style="color: var(--adm-gold); text-decoration: underline; font-weight: 600;">ingommt.com &rarr;</a> and log in with your Food Forest registered mobile number or email.
                        </p>
                    </div>
                </div>

                <div style="display: flex; gap: 14px; align-items: flex-start;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #e74c3c; color: #FFFFFF; font-weight: 800; font-size: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">2</div>
                    <div style="flex: 1;">
                        <strong style="color: #FFFFFF; font-size: 14px;">Navigate to Rates & Inventory &rarr; Calendar Management / Sync</strong>
                        <p style="font-size: 12.5px; color: var(--adm-text-muted); margin: 4px 0 6px;">
                            In the left main navigation menu, click on <strong>"Rates & Inventory"</strong>, then select <strong>"Calendar Sync"</strong> or <strong>"Channel Manager"</strong>.
                        </p>
                    </div>
                </div>

                <div style="display: flex; gap: 14px; align-items: flex-start;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #e74c3c; color: #FFFFFF; font-weight: 800; font-size: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">3</div>
                    <div style="flex: 1;">
                        <strong style="color: #FFFFFF; font-size: 14px;">Import Food Forest Calendar URL into MakeMyTrip</strong>
                        <p style="font-size: 12.5px; color: var(--adm-text-muted); margin: 4px 0 6px;">
                            • Select your Chalet (e.g. <em>Alpine Woodhouse</em> / <em>Canopy Treehouse</em>).<br>
                            • Click <strong>"Import Calendar / Add iCal Feed"</strong>.<br>
                            • In the <strong>Calendar Name</strong> field, type: <code style="color: var(--adm-gold); background: #0c1c13; padding: 2px 6px; border-radius: 4px;">Food Forest Direct Website</code>.<br>
                            • In the <strong>Calendar Link (URL)</strong> field, paste the corresponding Food Forest iCal URL copied from <strong>Step 1</strong> on this page.<br>
                            • Click <strong>"Save / Connect"</strong>.
                        </p>
                    </div>
                </div>

                <div style="display: flex; gap: 14px; align-items: flex-start;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #e74c3c; color: #FFFFFF; font-weight: 800; font-size: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">4</div>
                    <div style="flex: 1;">
                        <strong style="color: #FFFFFF; font-size: 14px;">Export MakeMyTrip Calendar Link to Food Forest</strong>
                        <p style="font-size: 12.5px; color: var(--adm-text-muted); margin: 4px 0 6px;">
                            • On the same InGoMMT screen, click <strong>"Export Calendar"</strong>.<br>
                            • Copy the unique `.ics` link provided by MakeMyTrip.<br>
                            • Return to this <strong>OTA Channel Sync</strong> page &rarr; go to <strong>Step 2 (Import OTA Feeds)</strong> above.<br>
                            • Select the matching Chalet, select <strong>MakeMyTrip</strong>, paste the link, and click <strong>"Connect Channel"</strong>.
                        </p>
                    </div>
                </div>

                <div style="display: flex; gap: 14px; align-items: flex-start;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #27ae60; color: #FFFFFF; font-weight: 800; font-size: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><i class="fa-solid fa-check"></i></div>
                    <div style="flex: 1;">
                        <strong style="color: #27ae60; font-size: 14px;">Verification Complete!</strong>
                        <p style="font-size: 12.5px; color: var(--adm-text-muted); margin: 4px 0 0;">
                            Click the <strong>"Sync All Channels Now"</strong> button above. All current and future MakeMyTrip reservations will instantly appear in your <strong><a href="calendar.php" style="color: var(--adm-gold); text-decoration: underline;">Booking Calendar</a></strong> and prevent direct guests from booking the same dates!
                        </p>
                    </div>
                </div>

            </div>
        </div>

        <!-- 2. Airbnb Guide Content -->
        <div class="guide-tab-content" id="guide-airbnb" style="display: none;">
            <div style="background: rgba(255, 90, 95, 0.08); border-left: 3px solid #FF5A5F; border-radius: 6px; padding: 14px 18px; margin-bottom: 20px;">
                <h4 style="color: #FFFFFF; font-size: 15px; margin: 0 0 6px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-brands fa-airbnb" style="color: #FF5A5F;"></i> Airbnb Host Calendar Synchronization
                </h4>
                <p style="font-size: 12.5px; color: #EAEFED; margin: 0;">
                    Airbnb automatically fetches external iCal feeds every 15–30 minutes and blocks reserved dates seamlessly.
                </p>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                
                <div style="display: flex; gap: 14px; align-items: flex-start;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #FF5A5F; color: #FFFFFF; font-weight: 800; font-size: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">1</div>
                    <div style="flex: 1;">
                        <strong style="color: #FFFFFF; font-size: 14px;">Log in to Airbnb & Open Your Listing</strong>
                        <p style="font-size: 12.5px; color: var(--adm-text-muted); margin: 4px 0 6px;">
                            Go to <a href="https://www.airbnb.com/hosting/listings" target="_blank" style="color: var(--adm-gold); text-decoration: underline; font-weight: 600;">airbnb.com/hosting/listings &rarr;</a> and select your chalet listing.
                        </p>
                    </div>
                </div>

                <div style="display: flex; gap: 14px; align-items: flex-start;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #FF5A5F; color: #FFFFFF; font-weight: 800; font-size: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">2</div>
                    <div style="flex: 1;">
                        <strong style="color: #FFFFFF; font-size: 14px;">Go to Pricing and availability &rarr; Calendar sync</strong>
                        <p style="font-size: 12.5px; color: var(--adm-text-muted); margin: 4px 0 6px;">
                            In the listing editor, click on <strong>"Pricing and availability"</strong> &rarr; scroll down to the <strong>"Calendar sync"</strong> section.
                        </p>
                    </div>
                </div>

                <div style="display: flex; gap: 14px; align-items: flex-start;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #FF5A5F; color: #FFFFFF; font-weight: 800; font-size: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">3</div>
                    <div style="flex: 1;">
                        <strong style="color: #FFFFFF; font-size: 14px;">Import Food Forest Calendar into Airbnb</strong>
                        <p style="font-size: 12.5px; color: var(--adm-text-muted); margin: 4px 0 6px;">
                            • Click <strong>"Import calendar"</strong>.<br>
                            • In the <strong>Calendar address (URL)</strong> field, paste your Food Forest Chalet iCal link copied from Step 1.<br>
                            • In <strong>Name your calendar</strong>, enter: <code style="color: var(--adm-gold); background: #0c1c13; padding: 2px 6px; border-radius: 4px;">Food Forest Direct Website</code>.<br>
                            • Click <strong>"Import calendar"</strong>.
                        </p>
                    </div>
                </div>

                <div style="display: flex; gap: 14px; align-items: flex-start;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #FF5A5F; color: #FFFFFF; font-weight: 800; font-size: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">4</div>
                    <div style="flex: 1;">
                        <strong style="color: #FFFFFF; font-size: 14px;">Export Airbnb Calendar to Food Forest</strong>
                        <p style="font-size: 12.5px; color: var(--adm-text-muted); margin: 4px 0 6px;">
                            • Click <strong>"Export calendar"</strong> on the Airbnb screen &rarr; copy the `.ics` link.<br>
                            • Return to this page &rarr; paste the link in <strong>Step 2 (Import OTA Feeds)</strong> &rarr; select <strong>Airbnb</strong> &rarr; click <strong>"Connect Channel"</strong>.
                        </p>
                    </div>
                </div>

            </div>
        </div>

        <!-- 3. Booking.com Guide Content -->
        <div class="guide-tab-content" id="guide-booking" style="display: none;">
            <div style="background: rgba(0, 53, 128, 0.15); border-left: 3px solid #38bdf8; border-radius: 6px; padding: 14px 18px; margin-bottom: 20px;">
                <h4 style="color: #FFFFFF; font-size: 15px; margin: 0 0 6px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-b" style="color: #38bdf8;"></i> Booking.com Extranet Calendar Sync
                </h4>
                <p style="font-size: 12.5px; color: #EAEFED; margin: 0;">
                    Booking.com supports 2-way iCal synchronization for independent villas, chalets, and boutique properties.
                </p>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                
                <div style="display: flex; gap: 14px; align-items: flex-start;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #003580; color: #FFFFFF; font-weight: 800; font-size: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">1</div>
                    <div style="flex: 1;">
                        <strong style="color: #FFFFFF; font-size: 14px;">Log in to Booking.com Extranet</strong>
                        <p style="font-size: 12.5px; color: var(--adm-text-muted); margin: 4px 0 6px;">
                            Open <a href="https://admin.booking.com/" target="_blank" style="color: var(--adm-gold); text-decoration: underline; font-weight: 600;">admin.booking.com &rarr;</a> and log in to your property dashboard.
                        </p>
                    </div>
                </div>

                <div style="display: flex; gap: 14px; align-items: flex-start;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #003580; color: #FFFFFF; font-weight: 800; font-size: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">2</div>
                    <div style="flex: 1;">
                        <strong style="color: #FFFFFF; font-size: 14px;">Click Rates & Availability &rarr; Sync calendars</strong>
                        <p style="font-size: 12.5px; color: var(--adm-text-muted); margin: 4px 0 6px;">
                            Select the tab <strong>"Rates & Availability"</strong> &rarr; click on <strong>"Sync calendars"</strong>.
                        </p>
                    </div>
                </div>

                <div style="display: flex; gap: 14px; align-items: flex-start;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #003580; color: #FFFFFF; font-weight: 800; font-size: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">3</div>
                    <div style="flex: 1;">
                        <strong style="color: #FFFFFF; font-size: 14px;">Add Calendar Connection (2-Way Exchange)</strong>
                        <p style="font-size: 12.5px; color: var(--adm-text-muted); margin: 4px 0 6px;">
                            • Click <strong>"Add calendar connection"</strong>.<br>
                            • Paste the Food Forest iCal export link from Step 1.<br>
                            • Copy Booking.com's export calendar link and paste into <strong>Step 2</strong> on this page.
                        </p>
                    </div>
                </div>

            </div>
        </div>

        <!-- 4. Agoda Guide Content -->
        <div class="guide-tab-content" id="guide-agoda" style="display: none;">
            <div style="background: rgba(46, 204, 113, 0.08); border-left: 3px solid #2ecc71; border-radius: 6px; padding: 14px 18px; margin-bottom: 20px;">
                <h4 style="color: #FFFFFF; font-size: 15px; margin: 0 0 6px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-hotel" style="color: #2ecc71;"></i> Agoda (YCS Partner Extranet)
                </h4>
                <p style="font-size: 12.5px; color: #EAEFED; margin: 0;">
                    Agoda YCS provides calendar import/export under property settings.
                </p>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div style="display: flex; gap: 14px; align-items: flex-start;">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #2ecc71; color: #FFFFFF; font-weight: 800; font-size: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">1</div>
                    <div style="flex: 1;">
                        <strong style="color: #FFFFFF; font-size: 14px;">Log in to Agoda YCS &rarr; Calendar Sync</strong>
                        <p style="font-size: 12.5px; color: var(--adm-text-muted); margin: 4px 0 6px;">
                            Log in to <a href="https://ycs.agoda.com/" target="_blank" style="color: var(--adm-gold); text-decoration: underline; font-weight: 600;">ycs.agoda.com &rarr;</a> &rarr; go to <strong>Settings</strong> &rarr; <strong>Calendar Sync</strong> &rarr; paste the Food Forest export URL and link back the Agoda export feed.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Cron Automation Guide Content -->
        <div class="guide-tab-content" id="guide-cron" style="display: none;">
            <div style="background: rgba(197, 160, 89, 0.08); border-left: 3px solid var(--adm-gold); border-radius: 6px; padding: 14px 18px; margin-bottom: 20px;">
                <h4 style="color: #FFFFFF; font-size: 15px; margin: 0 0 6px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-clock-rotate-left" style="color: var(--adm-gold);"></i> 24/7 Fully Automated Background Synchronization (Cron Job)
                </h4>
                <p style="font-size: 12.5px; color: #EAEFED; margin: 0;">
                    You can set up a scheduled background cron job so your server automatically syncs all connected MakeMyTrip, Airbnb, and Booking.com feeds every 15 minutes without any manual button clicking!
                </p>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div style="background: #08130d; border: 1px solid rgba(197,160,89,0.3); border-radius: 8px; padding: 16px 20px;">
                    <strong style="color: var(--adm-gold); font-size: 13.5px; display: block; margin-bottom: 8px;">
                        cPanel / Linux Server Cron Command (Run every 15 minutes):
                    </strong>
                    <div style="display: flex; align-items: center; justify-content: space-between; background: #000000; border: 1px solid rgba(255,255,255,0.15); border-radius: 6px; padding: 10px 14px; gap: 10px;">
                        <code id="cron-cmd-text" style="color: #22d3ee; font-family: monospace; font-size: 12px; word-break: break-all;">
                            */15 * * * * curl -s "<?php echo $base_url; ?>/api/ical_sync.php" > /dev/null 2>&1
                        </code>
                        <button type="button" class="adm-btn-action gold" onclick="navigator.clipboard.writeText(document.getElementById('cron-cmd-text').innerText.trim()); alert('Cron command copied to clipboard!');" style="padding: 5px 10px; font-size: 11px; flex-shrink: 0;">
                            <i class="fa-solid fa-copy"></i> Copy Cron
                        </button>
                    </div>
                    <p style="font-size: 12px; color: var(--adm-text-muted); margin: 10px 0 0;">
                        In your web hosting control panel (cPanel &rarr; Cron Jobs), set the interval to <code>*/15</code> (every 15 minutes) and paste the command above.
                    </p>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
function switchGuideTab(tabId, btn) {
    document.querySelectorAll('.guide-tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.guide-tab-btn').forEach(b => {
        b.classList.remove('active');
        b.classList.add('outline');
    });

    const target = document.getElementById(tabId);
    if (target) {
        target.style.display = 'block';
        btn.classList.add('active');
        btn.classList.remove('outline');
    }
}

function copyFeedUrl(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;

    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(() => {
        const origText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
        btn.style.borderColor = '#27ae60';
        btn.style.color = '#27ae60';
        setTimeout(() => {
            btn.innerHTML = origText;
            btn.style.borderColor = '';
            btn.style.color = '';
        }, 2000);
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
