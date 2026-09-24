<?php
// =========================================================================
// Food Forest Sanctuary — Master System User Manual & Step-by-Step Guide
// Comprehensive onboarding & operations guide with Dummy Data Reset Tool
// =========================================================================

$page_title = 'User Manual & Setup Guide';
$page_subtitle = 'Step-by-step master operational guide for estate configuration & live management';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db();
ensure_billing_columns($pdo);
ensure_food_menu_table_exists($pdo);

$alert_message = '';
$alert_type = 'success';

// Handle Dummy Data Reset Action
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear_dummy_data') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $alert_message = 'Security validation failed. Please try again.';
        $alert_type = 'error';
    } else {
        $confirm_text = strtoupper(trim($_POST['confirm_phrase'] ?? ''));
        if ($confirm_text !== 'RESET') {
            $alert_message = 'Please type "RESET" in the confirmation box to execute data clearing.';
            $alert_type = 'error';
        } else {
            $cleared_items = [];
            
            // 1. Clear Bookings if selected
            if (!empty($_POST['clear_bookings'])) {
                $count_b = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
                $pdo->exec("TRUNCATE TABLE `bookings`");
                $cleared_items[] = "{$count_b} Test Bookings & Guest Folios";
            }

            // 2. Clear Inquiries if selected
            if (!empty($_POST['clear_inquiries'])) {
                $count_i = (int)$pdo->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();
                $pdo->exec("TRUNCATE TABLE `inquiries`");
                $cleared_items[] = "{$count_i} Test Inquiries";
            }

            // 3. Reset Food Menu to defaults if selected
            if (!empty($_POST['reset_menu'])) {
                $pdo->exec("DROP TABLE IF EXISTS `food_menu`");
                ensure_food_menu_table_exists($pdo);
                $cleared_items[] = "Food Menu restored to fresh estate catalog";
            }

            // 4. Reset Gallery to defaults if selected
            if (!empty($_POST['reset_gallery'])) {
                $pdo->exec("TRUNCATE TABLE `gallery`");
                $gallery_items = [
                    ['title' => 'High Canopy Treehouse in Mist', 'caption' => 'Perched 30 feet above the forest floor among silver oaks, overlooking rolling valley clouds.', 'tag' => 'CANOPY DWELLING · 1,640M', 'category' => 'Villas & Stays', 'image_url' => 'assets/images/treehouse_exterior.png', 'display_order' => 1],
                    ['title' => 'Hand-Sculpted Cob Mudhouse', 'caption' => 'Naturally insulated clay and sand architecture with private herbal garden courtyards.', 'tag' => 'EARTHEN ARCHITECTURE', 'category' => 'Handcrafted Living', 'image_url' => 'assets/images/mudhouse_exterior.png', 'display_order' => 2],
                    ['title' => 'The Western Ghats Vista', 'caption' => 'High-altitude horizon cloaked in shifting clouds and untouched shola wilderness.', 'tag' => 'ALPINE HORIZON', 'category' => 'Landscape', 'image_url' => 'assets/images/01 (25).jpeg', 'display_order' => 3],
                    ['title' => 'Woodfire Claypot Lunch', 'caption' => 'Pure farm-to-table cooking over slow embers using hand-ground spices and organic produce.', 'tag' => 'EARTHEN GASTRONOMY', 'category' => 'Gastronomy', 'image_url' => 'assets/images/01 (3).jpeg', 'display_order' => 4],
                    ['title' => 'Organic Winter Apple Orchards', 'caption' => 'Ancient heirloom trees yielding sweet, pesticide-free mountain apples each winter.', 'tag' => 'ESTATE HARVEST', 'category' => 'Orchards', 'image_url' => 'assets/images/01 (1).jpeg', 'display_order' => 5],
                    ['title' => 'Stargazing by the Cob Hearth', 'caption' => 'Night skies at 1,600m altitude illuminated only by campfire crackle and constellations.', 'tag' => 'NIGHT SKY SANCTUARY', 'category' => 'Nightscape', 'image_url' => 'assets/images/01 (20).jpeg', 'display_order' => 6],
                    ['title' => 'Morning Dew on Passion Fruit Vines', 'caption' => 'Wild pollinators and lush flora flourishing in our certified chemical-free sanctuary.', 'tag' => 'BOTANICAL HARMONY', 'category' => 'Flora', 'image_url' => 'assets/images/01 (15).jpeg', 'display_order' => 7],
                    ['title' => 'Living Mud Courtyard Veranda', 'caption' => 'Unpaved, breathable courtyards connecting guest quarters directly with the soil.', 'tag' => 'BIOPHILIC SPACES', 'category' => 'Architecture', 'image_url' => 'assets/images/01 (7).jpeg', 'display_order' => 8]
                ];
                $ins = $pdo->prepare("INSERT INTO gallery (title, caption, tag, category, image_url, display_order) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($gallery_items as $item) {
                    $ins->execute([$item['title'], $item['caption'], $item['tag'], $item['category'], $item['image_url'], $item['display_order']]);
                }
                $cleared_items[] = "Gallery restored to original 8 photos";
            }

            if (!empty($cleared_items)) {
                $alert_message = "Successfully cleared: " . implode(', ', $cleared_items) . ". Your estate is now clean and ready for real guest bookings!";
                $alert_type = 'success';
            } else {
                $alert_message = "No items were selected to clear.";
                $alert_type = 'error';
            }
        }
    }
}

// Current Live Database Metrics
$current_bookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$current_inquiries = (int)$pdo->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();
$current_rooms = (int)$pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$current_menu = (int)$pdo->query("SELECT COUNT(*) FROM food_menu")->fetchColumn();
$current_exp = (int)$pdo->query("SELECT COUNT(*) FROM experiences")->fetchColumn();
$current_gallery = (int)$pdo->query("SELECT COUNT(*) FROM gallery")->fetchColumn();
?>

<div class="adm-content-wrapper">

    <!-- Alert Message -->
    <?php if (!empty($alert_message)): ?>
        <div class="adm-alert adm-alert-<?php echo $alert_type; ?>" style="margin-bottom: 24px;">
            <i class="fa-solid <?php echo ($alert_type === 'success') ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i>
            <span><?php echo htmlspecialchars($alert_message); ?></span>
        </div>
    <?php endif; ?>

    <!-- Master Header Banner -->
    <div class="adm-card" style="margin-bottom: 28px; background: linear-gradient(135deg, #101F15 0%, #173223 100%); border: 1.5px solid rgba(197, 160, 89, 0.4); padding: 28px 32px; position: relative; overflow: hidden;">
        <div style="position: absolute; right: -20px; top: -20px; font-size: 150px; color: rgba(197, 160, 89, 0.04); pointer-events: none;">
            <i class="fa-solid fa-book-open"></i>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; position: relative; z-index: 1;">
            <div style="max-width: 750px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                    <span class="adm-badge" style="background: rgba(197, 160, 89, 0.2); color: #DFC289; border: 1px solid rgba(197, 160, 89, 0.4); font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 6px;">
                        <i class="fa-solid fa-graduation-cap"></i> SYSTEM ONBOARDING & SETUP GUIDE
                    </span>
                    <span style="font-size: 12px; color: var(--adm-text-muted);">Ver 2.5 Live Concierge Engine</span>
                </div>
                <h2 style="font-family: var(--adm-font-title); font-size: 24px; color: #FFFFFF; letter-spacing: 1px; margin: 0 0 10px;">
                    Food Forest Sanctuary — User Manual & Data Entry Workflow
                </h2>
                <p style="font-size: 13.5px; color: #E2E8F0; line-height: 1.6; margin: 0;">
                    പുതിയൊരു എസ്റ്റേറ്റ് ആരംഭിക്കുമ്പോൾ എവിടെ നിന്നാണ് ഡാറ്റകൾ എന്റർ ചെയ്തു തുടങ്ങേണ്ടതെന്നും, അടുത്ത സ്റ്റെപ്പുകൾ എന്തൊക്കെയാണെന്നും താഴെ കൊടുത്തിട്ടുള്ള <strong>7-സ്റ്റെപ്പ് ഗൈഡിലൂടെ</strong> വളരെ ലളിതമായി മനസ്സിലാക്കാം.
                </p>
            </div>

            <div style="display: flex; flex-direction: column; gap: 10px; align-items: flex-end;">
                <a href="#section-clear-data" class="adm-btn-action" style="background: rgba(239, 68, 68, 0.2); color: #FCA5A5; border: 1px solid rgba(239, 68, 68, 0.4); padding: 10px 18px; font-size: 12.5px; text-decoration: none;">
                    <i class="fa-solid fa-trash-can"></i>
                    <span>Jump to Clear Demo Data Tool</span>
                </a>
                <a href="../index.php" target="_blank" class="adm-btn-action gold" style="padding: 10px 18px; font-size: 12.5px; text-decoration: none;">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>View Public Live Website</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Stat Pill Bar -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 30px;">
        <div style="background: rgba(16, 31, 21, 0.6); border: 1px solid var(--adm-border); border-radius: 8px; padding: 14px 18px; text-align: center;">
            <span style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-muted); letter-spacing: 0.5px; display: block;">Active Villas</span>
            <strong style="font-size: 20px; color: #FFF; font-family: var(--adm-font-title);"><?php echo $current_rooms; ?></strong>
        </div>
        <div style="background: rgba(16, 31, 21, 0.6); border: 1px solid var(--adm-border); border-radius: 8px; padding: 14px 18px; text-align: center;">
            <span style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-muted); letter-spacing: 0.5px; display: block;">Gastronomy Dishes</span>
            <strong style="font-size: 20px; color: #FFF; font-family: var(--adm-font-title);"><?php echo $current_menu; ?></strong>
        </div>
        <div style="background: rgba(16, 31, 21, 0.6); border: 1px solid var(--adm-border); border-radius: 8px; padding: 14px 18px; text-align: center;">
            <span style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-muted); letter-spacing: 0.5px; display: block;">Curated Rituals</span>
            <strong style="font-size: 20px; color: #FFF; font-family: var(--adm-font-title);"><?php echo $current_exp; ?></strong>
        </div>
        <div style="background: rgba(16, 31, 21, 0.6); border: 1px solid var(--adm-border); border-radius: 8px; padding: 14px 18px; text-align: center;">
            <span style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-muted); letter-spacing: 0.5px; display: block;">Visual Gallery</span>
            <strong style="font-size: 20px; color: #FFF; font-family: var(--adm-font-title);"><?php echo $current_gallery; ?></strong>
        </div>
        <div style="background: rgba(16, 31, 21, 0.6); border: 1px solid var(--adm-border); border-radius: 8px; padding: 14px 18px; text-align: center;">
            <span style="font-size: 11px; text-transform: uppercase; color: #F87171; letter-spacing: 0.5px; display: block;">Demo Bookings</span>
            <strong style="font-size: 20px; color: #FCA5A5; font-family: var(--adm-font-title);"><?php echo $current_bookings; ?></strong>
        </div>
        <div style="background: rgba(16, 31, 21, 0.6); border: 1px solid var(--adm-border); border-radius: 8px; padding: 14px 18px; text-align: center;">
            <span style="font-size: 11px; text-transform: uppercase; color: var(--adm-text-muted); letter-spacing: 0.5px; display: block;">Inquiries</span>
            <strong style="font-size: 20px; color: #FFF; font-family: var(--adm-font-title);"><?php echo $current_inquiries; ?></strong>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- 7-STEP DATA ENTRY CHRONOLOGICAL ROADMAP                       -->
    <!-- ============================================================= -->

    <!-- STEP 1: Estate Identity, Contact & Bank / UPI QR Code -->
    <div class="adm-card" style="margin-bottom: 24px; padding: 26px; border-left: 4px solid var(--adm-gold);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 42px; height: 42px; border-radius: 50%; background: var(--adm-gold); color: #101F15; font-weight: 800; font-size: 18px; display: flex; align-items: center; justify-content: center; font-family: var(--adm-font-title);">
                    1
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: var(--adm-gold); letter-spacing: 0.8px; text-transform: uppercase;">START HERE • FIRST CONFIGURATION</span>
                    <h3 style="font-family: var(--adm-font-title); font-size: 18px; color: #FFFFFF; margin: 2px 0 0;">
                        Estate Identity, WhatsApp Concierge & Bank / UPI QR Code
                    </h3>
                </div>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="edit_section.php?section=estate" class="adm-btn-action outline" style="padding: 7px 14px; font-size: 12px;">
                    <i class="fa-solid fa-leaf"></i> <span>Estate Info (Card 13)</span>
                </a>
                <a href="edit_section.php?section=whatsapp" class="adm-btn-action outline" style="padding: 7px 14px; font-size: 12px;">
                    <i class="fa-brands fa-whatsapp"></i> <span>WhatsApp (Card 12)</span>
                </a>
                <a href="edit_section.php?section=bank" class="adm-btn-action gold" style="padding: 7px 14px; font-size: 12px;">
                    <i class="fa-solid fa-building-columns"></i> <span>Bank & UPI QR (Card 17)</span>
                </a>
            </div>
        </div>

        <div style="background: rgba(16, 31, 21, 0.4); border: 1px solid var(--adm-border); border-radius: 8px; padding: 18px; line-height: 1.6; font-size: 13.5px; color: var(--adm-text-secondary);">
            <p style="margin: 0 0 10px; color: #FFFFFF; font-weight: 600;">
                <i class="fa-solid fa-circle-arrow-right" style="color: var(--adm-gold);"></i> ആദ്യം എന്റർ ചെയ്യേണ്ട വിവരങ്ങൾ:
            </p>
            <ul style="padding-left: 20px; margin: 0 0 12px;">
                <li><strong>Estate Title & Check-In/Out Hours</strong>: എസ്റ്റേറ്റിന്റെ പേര്, ടാഗ್‌ലൈൻ, ചെക്ക്-ഇൻ (2:00 PM), ചെക്ക്-ഔട്ട് (11:00 AM) സമയം എന്നിവ <a href="edit_section.php?section=estate" style="color:var(--adm-gold);">Card 13</a>-ൽ നൽകുക.</li>
                <li><strong>WhatsApp Concierge Number & Phone</strong>: കസ്റ്റമേഴ്‌സിന് മെസ്സേജ് അയക്കാനും കോൺസിയർജ് ഹോട്ട്ലൈനിനുമായി ഫോൺ നമ്പറും വിലാസവും <a href="edit_section.php?section=whatsapp" style="color:var(--adm-gold);">Card 12</a>-ൽ നൽകുക.</li>
                <li><strong>Bank Details & Payment QR Code</strong>: എസ്റ്റേറ്റിന്റെ ഔദ്യോഗിക ബാങ്ക് അക്കൗണ്ട് നമ്പർ, IFSC കോഡ്, അക്കൗണ്ട് ഉടമയുടെ പേര്, ബ്രാഞ്ച്, UPI VPA ID (GPay/PhonePe), പേയ്മെന്റ് QR കോഡ് ഇമേജ് എന്നിവ <a href="edit_section.php?section=bank" style="color:var(--adm-gold);">Card 17</a>-ൽ അപ്‌ലോഡ് ചെയ്തു നൽകുക. ഇതോടെ ബില്ലുകളിൽ ബാങ്ക് വിവരങ്ങളും ക്യുആർ കോഡും ഓട്ടോമാറ്റിക്കായി പ്രിന്റ് ആകും.</li>
            </ul>
        </div>
    </div>

    <!-- STEP 2: Villas, Cottages & Tariffs -->
    <div class="adm-card" style="margin-bottom: 24px; padding: 26px; border-left: 4px solid #34D399;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 42px; height: 42px; border-radius: 50%; background: #10B981; color: #101F15; font-weight: 800; font-size: 18px; display: flex; align-items: center; justify-content: center; font-family: var(--adm-font-title);">
                    2
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #34D399; letter-spacing: 0.8px; text-transform: uppercase;">STEP 02 • INVENTORY & PRICING</span>
                    <h3 style="font-family: var(--adm-font-title); font-size: 18px; color: #FFFFFF; margin: 2px 0 0;">
                        Villas, Cottages, Nightly Tariffs & 360° Virtual Tours
                    </h3>
                </div>
            </div>
            <div>
                <a href="edit_section.php?section=rooms" class="adm-btn-action gold" style="padding: 7px 14px; font-size: 12px;">
                    <i class="fa-solid fa-house-chimney"></i> <span>Manage Villas & Rates (Card 04)</span>
                </a>
            </div>
        </div>

        <div style="background: rgba(16, 31, 21, 0.4); border: 1px solid var(--adm-border); border-radius: 8px; padding: 18px; line-height: 1.6; font-size: 13.5px; color: var(--adm-text-secondary);">
            <p style="margin: 0 0 10px; color: #FFFFFF; font-weight: 600;">
                <i class="fa-solid fa-circle-arrow-right" style="color: #34D399;"></i> വില്ല വിവരങ്ങളും റൂം വാടകയും ക്രമീകരിക്കൽ:
            </p>
            <ul style="padding-left: 20px; margin: 0 0 12px;">
                <li><strong>Canopy Treehouse & Earthen Mudhouse</strong>: വില്ലകളുടെ ഒരു രാത്രിയിലെ വാടക (Base Rate per Night), പരമാവധി താമസിക്കാവുന്ന ആളുകളുടെ എണ്ണം (Base Occupancy), കൂടുതൽ വരുന്ന ആളുകൾക്കുള്ള എക്സ്ട്രാ നിരക്കുകൾ (Extra Adult Rate / Extra Child Rate) എന്നിവ സെറ്റ് ചെയ്യുക.</li>
                <li><strong>360° Panoramic Virtual Tours</strong>: അതിഥികൾക്ക് വെബ്‌സൈറ്റിൽ വില്ലയുടെ ഉൾവശം 360 ഡിഗ്രിയിൽ കാണാനുള്ള പനോരമ ഇമേജുകൾ അപ്‌ലോഡ് ചെയ്യാം.</li>
                <li><strong>Amenities & Description</strong>: ബാത്ത്‌റൂം, ബെഡിംഗ്, ഹെർബൽ ടീ, ടെറസ് തുടങ്ങിയ ആമേനിറ്റീസുകൾ ലിസ്റ്റ് ചെയ്യാം.</li>
            </ul>
        </div>
    </div>

    <!-- STEP 3: Food Menu Hub & Living Gastronomy -->
    <div class="adm-card" style="margin-bottom: 24px; padding: 26px; border-left: 4px solid #F59E0B;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 42px; height: 42px; border-radius: 50%; background: #F59E0B; color: #101F15; font-weight: 800; font-size: 18px; display: flex; align-items: center; justify-content: center; font-family: var(--adm-font-title);">
                    3
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #F59E0B; letter-spacing: 0.8px; text-transform: uppercase;">STEP 03 • LIVING GASTRONOMY</span>
                    <h3 style="font-family: var(--adm-font-title); font-size: 18px; color: #FFFFFF; margin: 2px 0 0;">
                        Food Menu Hub & Dining Meal Sets
                    </h3>
                </div>
            </div>
            <div>
                <a href="edit_section.php?section=menu" class="adm-btn-action gold" style="padding: 7px 14px; font-size: 12px;">
                    <i class="fa-solid fa-utensils"></i> <span>Open Food Menu Hub (Card 06)</span>
                </a>
            </div>
        </div>

        <div style="background: rgba(16, 31, 21, 0.4); border: 1px solid var(--adm-border); border-radius: 8px; padding: 18px; line-height: 1.6; font-size: 13.5px; color: var(--adm-text-secondary);">
            <p style="margin: 0 0 10px; color: #FFFFFF; font-weight: 600;">
                <i class="fa-solid fa-circle-arrow-right" style="color: #F59E0B;"></i> ഫുഡ് മെനു ക്രമീകരിക്കൽ:
            </p>
            <ul style="padding-left: 20px; margin: 0 0 12px;">
                <li><strong>Meal Categories</strong>: Breakfast, Lunch (Organic Kerala Sadya), High-Range Evening Snacks, Dinner (Woodfire Earthen Claypot Sets) എന്നീ കാറ്റഗറികളിൽ വിഭവങ്ങൾ ആഡ് ചെയ്യാം.</li>
                <li><strong>Inclusions & Rates</strong>: ഓരോ ഫുഡ് സെറ്റിലും ഉൾപ്പെടുന്ന ഐറ്റങ്ങൾ (ഉദാ: Appam, Stew, Nadan Kozhi Curry), ഒരു പ്ലേറ്റിന്റെ വില, ഫോട്ടോ എന്നിവ എഡിറ്റ് ചെയ്യാം.</li>
                <li><strong>Billing Integration</strong>: ഗസ്റ്റ് ചെക്ക്-ഔട്ട് ചെയ്യുമ്പോൾ അവർ കഴിച്ച ഫുഡ് സെറ്റുകൾ 1-ക്ലിക്കിൽ ഇൻവോയ്‌സിൽ ഐറ്റമൈസ് ചെയ്തു ചേർക്കാൻ ഇത് സഹായിക്കുന്നു.</li>
            </ul>
        </div>
    </div>

    <!-- STEP 4: Curated Experiences & Visual Photo Gallery -->
    <div class="adm-card" style="margin-bottom: 24px; padding: 26px; border-left: 4px solid #8B5CF6;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 42px; height: 42px; border-radius: 50%; background: #8B5CF6; color: #FFF; font-weight: 800; font-size: 18px; display: flex; align-items: center; justify-content: center; font-family: var(--adm-font-title);">
                    4
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #A78BFA; letter-spacing: 0.8px; text-transform: uppercase;">STEP 04 • EXPERIENCES & MEDIA</span>
                    <h3 style="font-family: var(--adm-font-title); font-size: 18px; color: #FFFFFF; margin: 2px 0 0;">
                        Curated Sanctuary Experiences & Visual Photo Chronicle
                    </h3>
                </div>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="edit_section.php?section=experiences" class="adm-btn-action outline" style="padding: 7px 14px; font-size: 12px;">
                    <i class="fa-solid fa-person-hiking"></i> <span>Experiences (Card 05)</span>
                </a>
                <a href="edit_section.php?section=gallery" class="adm-btn-action gold" style="padding: 7px 14px; font-size: 12px;">
                    <i class="fa-solid fa-camera-retro"></i> <span>Visual Diary (Card 10)</span>
                </a>
            </div>
        </div>

        <div style="background: rgba(16, 31, 21, 0.4); border: 1px solid var(--adm-border); border-radius: 8px; padding: 18px; line-height: 1.6; font-size: 13.5px; color: var(--adm-text-secondary);">
            <p style="margin: 0 0 10px; color: #FFFFFF; font-weight: 600;">
                <i class="fa-solid fa-circle-arrow-right" style="color: #A78BFA;"></i> എക്സ്പീരിയൻസുകളും ഫോട്ടോകളും നൽകൽ:
            </p>
            <ul style="padding-left: 20px; margin: 0 0 12px;">
                <li><strong>Sanctuary Rituals</strong>: Orchard Harvest Walk, Vernacular Mud Workshop, Forest Sunset Trail, Starlight Campfire എന്നിവയുടെ സമയവും ഫോട്ടോകളും <a href="edit_section.php?section=experiences" style="color:var(--adm-gold);">Card 05</a>-ൽ എഡിറ്റ് ചെയ്യാം.</li>
                <li><strong>8-Photo Visual Diary</strong>: ഫുഡ് ഫോറസ്റ്റിന്റെ മനോഹരമായ ഹൈ-റെസലൂഷൻ ഫോട്ടോകൾ, ടാഗുകൾ, ലൊക്കേഷൻ ഫ്രെയിമുകൾ എന്നിവ <a href="edit_section.php?section=gallery" style="color:var(--adm-gold);">Card 10</a>-ൽ അപ്‌ലോഡ് ചെയ്യാം.</li>
            </ul>
        </div>
    </div>

    <!-- STEP 5: Calendar, Reservations & OTA Channel Sync -->
    <div class="adm-card" style="margin-bottom: 24px; padding: 26px; border-left: 4px solid #3B82F6;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 42px; height: 42px; border-radius: 50%; background: #3B82F6; color: #FFF; font-weight: 800; font-size: 18px; display: flex; align-items: center; justify-content: center; font-family: var(--adm-font-title);">
                    5
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #60A5FA; letter-spacing: 0.8px; text-transform: uppercase;">STEP 05 • OPERATIONS & CALENDAR</span>
                    <h3 style="font-family: var(--adm-font-title); font-size: 18px; color: #FFFFFF; margin: 2px 0 0;">
                        Reservations, Interactive Calendar & OTA Channel Sync (iCal)
                    </h3>
                </div>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="bookings.php" class="adm-btn-action outline" style="padding: 7px 14px; font-size: 12px;">
                    <i class="fa-solid fa-calendar-check"></i> <span>Reservations</span>
                </a>
                <a href="calendar.php" class="adm-btn-action outline" style="padding: 7px 14px; font-size: 12px;">
                    <i class="fa-solid fa-calendar-days"></i> <span>Calendar</span>
                </a>
                <a href="channel_sync.php" class="adm-btn-action gold" style="padding: 7px 14px; font-size: 12px;">
                    <i class="fa-solid fa-arrows-rotate"></i> <span>OTA Channel Sync</span>
                </a>
            </div>
        </div>

        <div style="background: rgba(16, 31, 21, 0.4); border: 1px solid var(--adm-border); border-radius: 8px; padding: 18px; line-height: 1.6; font-size: 13.5px; color: var(--adm-text-secondary);">
            <p style="margin: 0 0 10px; color: #FFFFFF; font-weight: 600;">
                <i class="fa-solid fa-circle-arrow-right" style="color: #60A5FA;"></i> ബുക്കിംഗുകളും കലണ്ടറും കൈകാര്യം ചെയ്യൽ:
            </p>
            <ul style="padding-left: 20px; margin: 0 0 12px;">
                <li><strong>Manage Reservations</strong>: വരുന്ന പുതിയ ബുക്കിംഗുകൾ കൺഫേം ചെയ്യുക, ഇൻ-ഹൗസ് (In-House) ഗസ്റ്റുകളാക്കുക, ചെക്ക്-ഔട്ട് പൂർത്തിയാക്കുക.</li>
                <li><strong>Interactive Calendar</strong>: ഓരോ ദിവസവും ഏതൊക്കെ റൂമുകൾ ബ്ലോക്ക്ഡ് ആണെന്നും വേക്കന്റ് ആണെന്നും ഒറ്റനോട്ടത്തിൽ കാണാം.</li>
                <li><strong>Airbnb & Booking.com Sync</strong>: <code>api/ical_sync.php</code> വഴി ഒടിഎ ചാനലുകളുമായി തീയതികൾ 2-വേ ഓട്ടോമാറ്റിക് ആയി സിങ്ക് ചെയ്യാം.</li>
            </ul>
        </div>
    </div>

    <!-- STEP 6: Billing & Invoicing, Live Customization & Luxury A4 Printing -->
    <div class="adm-card" style="margin-bottom: 24px; padding: 26px; border-left: 4px solid #EC4899;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 42px; height: 42px; border-radius: 50%; background: #EC4899; color: #FFF; font-weight: 800; font-size: 18px; display: flex; align-items: center; justify-content: center; font-family: var(--adm-font-title);">
                    6
                </div>
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #F472B6; letter-spacing: 0.8px; text-transform: uppercase;">STEP 06 • BILLING & SETTLEMENT</span>
                    <h3 style="font-family: var(--adm-font-title); font-size: 18px; color: #FFFFFF; margin: 2px 0 0;">
                        Guest Folios, Itemized Settlement, WhatsApp Share & A4 Print
                    </h3>
                </div>
            </div>
            <div>
                <a href="billing.php" class="adm-btn-action gold" style="padding: 7px 14px; font-size: 12px;">
                    <i class="fa-solid fa-receipt"></i> <span>Open Billing & Invoices Hub</span>
                </a>
            </div>
        </div>

        <div style="background: rgba(16, 31, 21, 0.4); border: 1px solid var(--adm-border); border-radius: 8px; padding: 18px; line-height: 1.6; font-size: 13.5px; color: var(--adm-text-secondary);">
            <p style="margin: 0 0 10px; color: #FFFFFF; font-weight: 600;">
                <i class="fa-solid fa-circle-arrow-right" style="color: #F472B6;"></i> ബില്ലിംഗ് എങ്ങനെ ചെയ്യാം:
            </p>
            <ul style="padding-left: 20px; margin: 0 0 12px;">
                <li><strong>Customize & Settle</strong>: ഗസ്റ്റ് റൂം വാടക, അവർ കഴിച്ച മീലുകൾ, ചെയ്ത ആക്ടിവിറ്റികൾ, എക്സ്ട്രാ സർവീസുകൾ, നൽകിയ ഡിസ്കൗണ്ട് എന്നിവ ക്രമീകരിക്കാം.</li>
                <li><strong>Print Folio (A4)</strong>: ബാങ്ക് വിവരങ്ങളും ക്യുആർ കോഡും ഉൾപ്പെടുത്തി ഉയർന്ന നിലവാരമുള്ള ഒഫീഷ്യൽ ടാക്സ് ഇൻവോയ്സ് പിഡിഎഫ് ആയി ഡൗൺലോഡ് ചെയ്യാം അല്ലെങ്കിൽ പ്രിന്റ് എടുക്കാം.</li>
                <li><strong>Share on WhatsApp</strong>: 1-ക്ലിക്കിൽ ഗസ്റ്റിന്റെ വാട്സ്ആപ്പിലേക്ക് കൃത്യമായ ബിൽ വിവരങ്ങൾ അയച്ചുകൊടുക്കാം.</li>
            </ul>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- STEP 7: CLEAR DUMMY / DEMO DATA UTILITY (SAFE RESET TOOL)     -->
    <!-- ============================================================= -->
    <div class="adm-card" id="section-clear-data" style="padding: 28px; border: 2px solid rgba(239, 68, 68, 0.4); background: rgba(16, 31, 21, 0.7); margin-bottom: 30px;">
        
        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px;">
            <div style="width: 44px; height: 44px; border-radius: 50%; background: #EF4444; color: #FFF; font-weight: 800; font-size: 18px; display: flex; align-items: center; justify-content: center; font-family: var(--adm-font-title);">
                7
            </div>
            <div>
                <span class="adm-badge" style="background: rgba(239, 68, 68, 0.2); color: #FCA5A5; border: 1px solid rgba(239, 68, 68, 0.4); font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 4px;">
                    SAFE MAINTENANCE UTILITY
                </span>
                <h3 style="font-family: var(--adm-font-title); font-size: 20px; color: #FFFFFF; margin: 4px 0 0;">
                    Clear Dummy / Demo Data Tool (ഡമ്മി ഡാറ്റ ക്ലിയർ ചെയ്യൽ)
                </h3>
            </div>
        </div>

        <div style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: 8px; padding: 18px; margin-bottom: 24px; line-height: 1.6; font-size: 13.5px; color: #E2E8F0;">
            <p style="margin: 0 0 8px;">
                <strong><i class="fa-solid fa-shield-halved" style="color: #EF4444;"></i> എസ്റ്റേറ്റ് ലൈവ് ആക്കാൻ തയ്യാറാകുമ്പോൾ:</strong>
            </p>
            <p style="margin: 0 0 10px; color: var(--adm-text-secondary);">
                സിസ്റ്റം ടെസ്റ്റ് ചെയ്യാനായി മുൻപ് ആഡ് ചെയ്തിരുന്ന ഡമ്മി ബുക്കിംഗുകളും ഇൻക്വയറികളും താഴെ കാണുന്ന ടൂൾ ഉപയോഗിച്ച് സുരക്ഷിതമായി ഡിലീറ്റ് ചെയ്യാം. 
            </p>
            <div style="display: flex; gap: 14px; flex-wrap: wrap; font-size: 12.5px; color: #86EFAC;">
                <span><i class="fa-solid fa-check"></i> നിങ്ങളുടെ Admin Username & Password സുരക്ഷിതമായിരിക്കും.</span>
                <span><i class="fa-solid fa-check"></i> വില്ല വിവരങ്ങളും ബാങ്ക് സെറ്റിങ്സുകളും മാറ്റമില്ലാതെ നിലനിൽക്കും.</span>
            </div>
        </div>

        <!-- Reset Form -->
        <form method="POST" action="user_manual.php#section-clear-data" onsubmit="return confirmClearData(this);">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="action" value="clear_dummy_data">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; margin-bottom: 24px;">
                
                <div style="background: rgba(16, 31, 21, 0.8); border: 1px solid var(--adm-border); border-radius: 8px; padding: 16px;">
                    <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                        <input type="checkbox" name="clear_bookings" value="1" checked style="width: 18px; height: 18px; margin-top: 2px; accent-color: #EF4444;">
                        <div>
                            <span style="font-size: 14px; font-weight: 700; color: #FFFFFF; display: block;">Clear Test Bookings (<?php echo $current_bookings; ?> Records)</span>
                            <span style="font-size: 12px; color: var(--adm-text-muted); display: block; margin-top: 2px;">Wipes test reservations, guest folios & billing ledger.</span>
                        </div>
                    </label>
                </div>

                <div style="background: rgba(16, 31, 21, 0.8); border: 1px solid var(--adm-border); border-radius: 8px; padding: 16px;">
                    <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                        <input type="checkbox" name="clear_inquiries" value="1" checked style="width: 18px; height: 18px; margin-top: 2px; accent-color: #EF4444;">
                        <div>
                            <span style="font-size: 14px; font-weight: 700; color: #FFFFFF; display: block;">Clear Test Inquiries (<?php echo $current_inquiries; ?> Messages)</span>
                            <span style="font-size: 12px; color: var(--adm-text-muted); display: block; margin-top: 2px;">Wipes demo guest messages from inquiry inbox.</span>
                        </div>
                    </label>
                </div>

                <div style="background: rgba(16, 31, 21, 0.8); border: 1px solid var(--adm-border); border-radius: 8px; padding: 16px;">
                    <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                        <input type="checkbox" name="reset_menu" value="1" style="width: 18px; height: 18px; margin-top: 2px; accent-color: #F59E0B;">
                        <div>
                            <span style="font-size: 14px; font-weight: 700; color: #FFFFFF; display: block;">Reset Food Menu Catalog</span>
                            <span style="font-size: 12px; color: var(--adm-text-muted); display: block; margin-top: 2px;">Optional: Restores default Breakfast, Sadya & Claypot items.</span>
                        </div>
                    </label>
                </div>

                <div style="background: rgba(16, 31, 21, 0.8); border: 1px solid var(--adm-border); border-radius: 8px; padding: 16px;">
                    <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                        <input type="checkbox" name="reset_gallery" value="1" style="width: 18px; height: 18px; margin-top: 2px; accent-color: #F59E0B;">
                        <div>
                            <span style="font-size: 14px; font-weight: 700; color: #FFFFFF; display: block;">Reset Photo Gallery</span>
                            <span style="font-size: 12px; color: var(--adm-text-muted); display: block; margin-top: 2px;">Optional: Restores original 8 curated photographs.</span>
                        </div>
                    </label>
                </div>

            </div>

            <!-- Confirmation Phrase Input -->
            <div style="background: rgba(8, 18, 11, 0.9); border: 1px dashed rgba(239, 68, 68, 0.4); border-radius: 8px; padding: 18px; margin-bottom: 20px;">
                <label style="font-size: 13px; font-weight: 700; color: #FFFFFF; display: block; margin-bottom: 6px;">
                    Type <code style="background: rgba(239, 68, 68, 0.2); color: #FCA5A5; padding: 2px 6px; border-radius: 4px; font-family: monospace;">RESET</code> to confirm deletion:
                </label>
                <div style="display: flex; gap: 12px; max-width: 450px;">
                    <input type="text" name="confirm_phrase" id="confirm_phrase_input" class="adm-form-control" placeholder="Type RESET" required style="font-family: monospace; letter-spacing: 2px; text-transform: uppercase;">
                    <button type="submit" class="adm-btn-action" style="background: #DC2626; color: #FFF; font-weight: 700; padding: 10px 22px; white-space: nowrap; border: none; border-radius: 6px; cursor: pointer;">
                        <i class="fa-solid fa-trash-can"></i> CLEAR SELECTED DATA
                    </button>
                </div>
            </div>

        </form>

    </div>

</div>

<script>
function confirmClearData(form) {
    var phrase = document.getElementById('confirm_phrase_input')?.value.trim().toUpperCase();
    if (phrase !== 'RESET') {
        alert('Please type "RESET" in capital letters to proceed with clearing demo data.');
        return false;
    }
    return confirm('Are you sure you want to delete the selected test data? This action cannot be undone.');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
