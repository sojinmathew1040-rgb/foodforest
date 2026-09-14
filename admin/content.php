<?php
// Food Forest Sanctuary — Content & Media moved to Settings Cards
header('Location: settings.php?tab=hero');
exit;
require_once __DIR__ . '/includes/header.php';

$pdo = get_db();
$alert_message = '';
$alert_type = 'success';

// Handle Form Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $alert_message = 'Security validation failed.';
        $alert_type = 'error';
    } else {
        $fields = [
            'hero_eyebrow', 'hero_title', 'hero_desc', 'hero_bg_image',
            'top_bar_location', 'top_bar_accolade',
            'welcome_badge', 'welcome_title', 'welcome_paragraph', 'welcome_image',
            'why_badge', 'why_title', 'why_desc', 'why_image',
            'experiences_badge', 'experiences_title', 'experiences_desc',
            'seasons_badge', 'seasons_title', 'seasons_desc'
        ];

        $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $stmt->execute([$field, trim($_POST[$field])]);
            }
        }
        $alert_message = 'Site content and imagery saved successfully. Live pages updated.';
    }
}

// Fetch all content settings
$all_settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$content = [];
while ($row = $all_settings_stmt->fetch()) {
    $content[$row['setting_key']] = $row['setting_value'];
}
?>

<?php if (!empty($alert_message)): ?>
    <div class="adm-alert adm-alert-<?php echo $alert_type; ?>" style="margin-bottom: 24px;">
        <i class="fa-solid <?php echo $alert_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
        <span><?php echo e($alert_message); ?></span>
    </div>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

    <!-- 1. Hero Section Editorial & Backdrop -->
    <div class="adm-table-card">
        <div class="adm-table-header">
            <div>
                <h2 class="adm-table-title"><i class="fa-solid fa-mountain-sun" style="color: var(--adm-gold); margin-right: 8px;"></i> Hero Section & Atmosphere</h2>
                <p class="adm-table-subtitle">Primary landing view, marquee title, introductory narrative and background image</p>
            </div>
            <button type="submit" class="adm-btn-action gold">
                <i class="fa-solid fa-floppy-disk"></i> Save All Content
            </button>
        </div>

        <div style="padding: 24px;">
            <div class="adm-grid-2">
                <div class="adm-form-group">
                    <label class="adm-label">Top Eyebrow Badge Pill</label>
                    <input type="text" name="hero_eyebrow" class="adm-input" value="<?php echo e($content['hero_eyebrow'] ?? 'KANTHALLOOR, KERALA • PRIVATE ECO-SANCTUARY'); ?>" style="padding-left: 14px;">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Top Bar Elevation Air & Temperature</label>
                    <input type="text" name="top_bar_location" class="adm-input" value="<?php echo e($content['top_bar_location'] ?? 'Kanthalloor High Range • 1,600m Elevation • 18°C Misty Mountain Air'); ?>" style="padding-left: 14px;">
                </div>
            </div>

            <div class="adm-grid-2">
                <div class="adm-form-group">
                    <label class="adm-label">Main Marquee Headline *</label>
                    <input type="text" name="hero_title" class="adm-input" value="<?php echo e($content['hero_title'] ?? 'Where Earth Breathes & Time Stands Still.'); ?>" required style="padding-left: 14px; font-weight: 600;">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Top Bar Accolade Pill</label>
                    <input type="text" name="top_bar_accolade" class="adm-input" value="<?php echo e($content['top_bar_accolade'] ?? 'Rated 4.98 / 5 • Top Sustainable Sanctuary 2026'); ?>" style="padding-left: 14px;">
                </div>
            </div>

            <div class="adm-form-group">
                <label class="adm-label">Hero Poetic Description</label>
                <textarea name="hero_desc" class="adm-input" rows="3" style="padding-left: 14px; resize: vertical;"><?php echo e($content['hero_desc'] ?? ''); ?></textarea>
            </div>

            <div class="adm-grid-2" style="align-items: center; margin-top: 10px;">
                <div class="adm-form-group">
                    <label class="adm-label">Hero Background Visual Path or URL *</label>
                    <input type="text" name="hero_bg_image" id="hero_bg_image_input" class="adm-input" value="<?php echo e($content['hero_bg_image'] ?? 'assets/images/01 (25).jpeg'); ?>" required style="padding-left: 14px;" oninput="document.getElementById('hero-bg-preview').src = '../' + this.value;">
                    <span style="font-size: 11px; color: var(--adm-text-muted); margin-top: 4px; display: block;">
                        Change the hero full-screen backdrop photograph.
                    </span>
                </div>
                <div>
                    <span class="adm-label">Backdrop Visual Preview</span>
                    <div style="height: 90px; border-radius: 9px; overflow: hidden; border: 1px solid var(--adm-gold-border); background: #07100B;">
                        <img id="hero-bg-preview" src="../<?php echo e($content['hero_bg_image'] ?? 'assets/images/01 (25).jpeg'); ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/images/treehouse_exterior.png'">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Sanctuary Philosophy / Welcome Section -->
    <div class="adm-table-card">
        <div class="adm-table-header">
            <div>
                <h2 class="adm-table-title"><i class="fa-solid fa-leaf" style="color: var(--adm-gold); margin-right: 8px;"></i> Philosophy & Welcome Story</h2>
                <p class="adm-table-subtitle">Editorial story, ecological ethos and featured mudhouse portrait</p>
            </div>
        </div>

        <div style="padding: 24px;">
            <div class="adm-grid-2">
                <div class="adm-form-group">
                    <label class="adm-label">Philosophy Section Label</label>
                    <input type="text" name="welcome_badge" class="adm-input" value="<?php echo e($content['welcome_badge'] ?? 'THE SANCTUARY PHILOSOPHY'); ?>" style="padding-left: 14px;">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Philosophy Heading *</label>
                    <input type="text" name="welcome_title" class="adm-input" value="<?php echo e($content['welcome_title'] ?? 'Rooted in Earth, Reverence & Time'); ?>" required style="padding-left: 14px; font-weight: 600;">
                </div>
            </div>

            <div class="adm-form-group">
                <label class="adm-label">Philosophy Narrative Paragraph</label>
                <textarea name="welcome_paragraph" class="adm-input" rows="4" style="padding-left: 14px; resize: vertical;"><?php echo e($content['welcome_paragraph'] ?? ''); ?></textarea>
            </div>

            <div class="adm-grid-2" style="align-items: center; margin-top: 10px;">
                <div class="adm-form-group">
                    <label class="adm-label">Philosophy Featured Photograph *</label>
                    <input type="text" name="welcome_image" id="welcome_image_input" class="adm-input" value="<?php echo e($content['welcome_image'] ?? 'assets/images/01 (7).jpeg'); ?>" required style="padding-left: 14px;" oninput="document.getElementById('welcome-img-preview').src = '../' + this.value;">
                    <span style="font-size: 11px; color: var(--adm-text-muted); margin-top: 4px; display: block;">
                        Change the featured portrait photograph displayed alongside the philosophy.
                    </span>
                </div>
                <div>
                    <span class="adm-label">Featured Portrait Preview</span>
                    <div style="height: 90px; border-radius: 9px; overflow: hidden; border: 1px solid var(--adm-gold-border); background: #07100B;">
                        <img id="welcome-img-preview" src="../<?php echo e($content['welcome_image'] ?? 'assets/images/01 (7).jpeg'); ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/images/mudhouse_exterior.png'">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Why Food Forest / Farmstay Story -->
    <div class="adm-table-card" style="margin-top: 28px;">
        <div class="adm-table-header">
            <div>
                <h2 class="adm-table-title"><i class="fa-solid fa-leaf" style="color: var(--adm-gold); margin-right: 8px;"></i> Why Food Forest? (Farmstay Feature)</h2>
                <p class="adm-table-subtitle">Curate the signature farmstay introduction, ethos, and featured photographic display</p>
            </div>
            <button type="submit" class="adm-btn-action gold">
                <i class="fa-solid fa-floppy-disk"></i> Save All Content
            </button>
        </div>

        <div style="padding: 24px;">
            <div class="adm-grid-2">
                <div class="adm-form-group">
                    <label class="adm-label">Section Eyebrow Badge</label>
                    <input type="text" name="why_badge" class="adm-input" value="<?php echo e($content['why_badge'] ?? 'WHY FOOD FOREST?'); ?>" style="padding-left: 14px;">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Main Heading Headline *</label>
                    <input type="text" name="why_title" class="adm-input" value="<?php echo e($content['why_title'] ?? 'An Authentic Farmstay Sanctuary'); ?>" required style="padding-left: 14px; font-weight: 600;">
                </div>
            </div>

            <div class="adm-form-group">
                <label class="adm-label">Farmstay Narrative Description</label>
                <textarea name="why_desc" class="adm-input" rows="4" style="padding-left: 14px; resize: vertical;"><?php echo e($content['why_desc'] ?? ''); ?></textarea>
            </div>

            <div class="adm-grid-2" style="align-items: center; margin-top: 10px;">
                <div class="adm-form-group">
                    <label class="adm-label">Farmstay Feature Photograph *</label>
                    <input type="text" name="why_image" id="why_image_input" class="adm-input" value="<?php echo e($content['why_image'] ?? 'assets/images/01 (26).jpeg'); ?>" required style="padding-left: 14px;" oninput="document.getElementById('why-img-preview').src = '../' + this.value;">
                    <span style="font-size: 11px; color: var(--adm-text-muted); margin-top: 4px; display: block;">
                        Photograph displayed in the left parallax frame of the Farmstay section.
                    </span>
                </div>
                <div>
                    <span class="adm-label">Featured Photo Preview</span>
                    <div style="height: 90px; border-radius: 9px; overflow: hidden; border: 1px solid var(--adm-gold-border); background: #07100B;">
                        <img id="why-img-preview" src="../<?php echo e($content['why_image'] ?? 'assets/images/01 (26).jpeg'); ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/images/mudhouse_exterior.png'">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Section Editorial Banners & Introductions -->
    <div class="adm-table-card" style="margin-top: 28px;">
        <div class="adm-table-header">
            <div>
                <h2 class="adm-table-title"><i class="fa-solid fa-align-left" style="color: var(--adm-gold); margin-right: 8px;"></i> Section Banners & Editorial Introductions</h2>
                <p class="adm-table-subtitle">Configure headlines and intro paragraphs for Experiences & Seasons</p>
            </div>
            <button type="submit" class="adm-btn-action gold">
                <i class="fa-solid fa-floppy-disk"></i> Save All Content
            </button>
        </div>

        <div style="padding: 24px;">
            <h4 style="font-family: var(--adm-font-title); font-size: 13px; color: var(--adm-gold); letter-spacing: 1px; text-transform: uppercase; margin-bottom: 14px;">
                A. Curated Experiences (Rituals of the High Range)
            </h4>
            <div class="adm-grid-2">
                <div class="adm-form-group">
                    <label class="adm-label">Experiences Eyebrow Badge</label>
                    <input type="text" name="experiences_badge" class="adm-input" value="<?php echo e($content['experiences_badge'] ?? 'CURATED JOURNEYS'); ?>" style="padding-left: 14px;">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Experiences Heading Headline</label>
                    <input type="text" name="experiences_title" class="adm-input" value="<?php echo e($content['experiences_title'] ?? 'Rituals of the High Range'); ?>" style="padding-left: 14px;">
                </div>
            </div>
            <div class="adm-form-group">
                <label class="adm-label">Experiences Introduction Subtitle</label>
                <textarea name="experiences_desc" class="adm-input" rows="2" style="padding-left: 14px; resize: vertical;"><?php echo e($content['experiences_desc'] ?? ''); ?></textarea>
            </div>

            <hr style="border: none; border-top: 1px solid var(--adm-border); margin: 24px 0;">

            <h4 style="font-family: var(--adm-font-title); font-size: 13px; color: var(--adm-gold); letter-spacing: 1px; text-transform: uppercase; margin-bottom: 14px;">
                B. Seasons of Kanthalloor (Nature's Changing Canvas)
            </h4>
            <div class="adm-grid-2">
                <div class="adm-form-group">
                    <label class="adm-label">Seasons Eyebrow Badge</label>
                    <input type="text" name="seasons_badge" class="adm-input" value="<?php echo e($content['seasons_badge'] ?? 'EVERY SEASON HAS A STORY'); ?>" style="padding-left: 14px;">
                </div>
                <div class="adm-form-group">
                    <label class="adm-label">Seasons Heading Headline</label>
                    <input type="text" name="seasons_title" class="adm-input" value="<?php echo e($content['seasons_title'] ?? "Nature's Changing Canvas"); ?>" style="padding-left: 14px;">
                </div>
            </div>
            <div class="adm-form-group">
                <label class="adm-label">Seasons Introduction Subtitle</label>
                <textarea name="seasons_desc" class="adm-input" rows="2" style="padding-left: 14px; resize: vertical;"><?php echo e($content['seasons_desc'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="adm-btn-action gold" style="width: 100%; justify-content: center; margin-top: 24px; padding: 13px;">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Publish All Changes to Sanctuary Website</span>
            </button>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
