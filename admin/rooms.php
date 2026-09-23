<?php
// Food Forest Sanctuary — Villas moved to Settings Card 04
header('Location: edit_section.php?section=rooms');
exit;
require_once __DIR__ . '/includes/header.php';

$pdo = get_db();
$alert_message = '';
$alert_type = 'success';

// Handle Villa Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $alert_message = 'Security validation failed. Please try again.';
        $alert_type = 'error';
    } else {
        $villa_id = (int)$_POST['villa_id'];
        $title = trim($_POST['title']);
        $rate_per_night = (float)$_POST['rate_per_night'];
        $max_guests = (int)$_POST['max_guests'];
        $elevation = trim($_POST['elevation']);
        $description = trim($_POST['description']);
        $amenities = trim($_POST['amenities']);
        $image_url = trim($_POST['image_url'] ?? '');
        $file_key = 'image_file_' . $villa_id;
        if (!empty($_FILES[$file_key]['name'])) {
            $up = handle_image_upload($_FILES[$file_key], 'villa');
            if ($up['success']) {
                $image_url = $up['path'];
            }
        }
        $is_available = isset($_POST['is_available']) ? 1 : 0;

        $stmt = $pdo->prepare("UPDATE rooms SET title = ?, rate_per_night = ?, max_guests = ?, elevation = ?, description = ?, amenities = ?, image_url = ?, is_available = ? WHERE id = ?");
        $stmt->execute([$title, $rate_per_night, $max_guests, $elevation, $description, $amenities, $image_url, $is_available, $villa_id]);

        $alert_message = 'Villa configuration saved successfully. Front-end pages updated.';
    }
}

$rooms = $pdo->query("SELECT * FROM rooms ORDER BY id ASC")->fetchAll();
?>

<?php if (!empty($alert_message)): ?>
    <div class="adm-alert adm-alert-<?php echo $alert_type; ?>" style="margin-bottom: 24px;">
        <i class="fa-solid <?php echo $alert_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
        <span><?php echo e($alert_message); ?></span>
    </div>
<?php endif; ?>

<div class="adm-villa-grid">
    <?php foreach ($rooms as $room): 
        $is_treehouse = ($room['slug'] === 'treehouse');
    ?>
        <div class="adm-villa-card" id="villa-<?php echo e($room['slug']); ?>">
            <div class="adm-villa-header">
                <div>
                    <h2 class="adm-villa-title">
                        <i class="fa-solid <?php echo $is_treehouse ? 'fa-tree' : 'fa-house-chimney'; ?>" style="color: var(--adm-gold); margin-right: 8px;"></i>
                        <?php echo e($room['title']); ?>
                    </h2>
                    <span class="adm-villa-elevation"><?php echo e($room['elevation']); ?></span>
                </div>
                <div>
                    <?php if ($room['is_available']): ?>
                        <span class="adm-badge confirmed"><i class="fa-solid fa-circle-check"></i> Bookable</span>
                    <?php else: ?>
                        <span class="adm-badge cancelled"><i class="fa-solid fa-ban"></i> Blocked</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Villa Image Banner -->
            <div style="height: 160px; overflow: hidden; position: relative; background: #07100B;">
                <img id="preview-img-<?php echo $room['id']; ?>" src="../<?php echo e(($room['image_url'] ?? '') ?: ($is_treehouse ? 'assets/images/treehouse_exterior.png' : 'assets/images/mudhouse_exterior.png')); ?>" alt="<?php echo e($room['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
            </div>

            <form method="POST" class="adm-villa-body" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="villa_id" value="<?php echo $room['id']; ?>">

                <div class="adm-villa-rate-row">
                    <span style="font-size: 14px; color: var(--adm-text-muted);">Current Tariff:</span>
                    <span class="adm-villa-rate">₹<?php echo number_format($room['rate_per_night'], 0, '.', ','); ?></span>
                    <span class="adm-villa-period">/ night (Taxes & Organic Meals Incl.)</span>
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Villa Display Title *</label>
                        <input type="text" name="title" class="adm-input" value="<?php echo e($room['title']); ?>" required style="padding-left: 14px;">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Nightly Rate (₹) *</label>
                        <input type="number" name="rate_per_night" class="adm-input" value="<?php echo e($room['rate_per_night']); ?>" step="100" required style="padding-left: 14px;">
                    </div>
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Elevation / Concept Badge</label>
                        <input type="text" name="elevation" class="adm-input" value="<?php echo e($room['elevation']); ?>" required style="padding-left: 14px;">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Max Guest Capacity</label>
                        <input type="number" name="max_guests" class="adm-input" value="<?php echo e($room['max_guests']); ?>" min="1" max="10" required style="padding-left: 14px;">
                    </div>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Villa Photograph</label>
                    <div class="adm-uploader-card adm-uploader-compact">
                        <div class="adm-uploader-controls">
                            <div class="adm-uploader-btn-wrap">
                                <label class="adm-uploader-btn" for="image_file_<?php echo $room['id']; ?>">
                                    <i class="fa-solid fa-arrow-up-from-bracket"></i> Choose Photo from Device
                                </label>
                                <input type="file" name="image_file_<?php echo $room['id']; ?>" id="image_file_<?php echo $room['id']; ?>" class="adm-uploader-input" accept="image/*" onchange="previewUploadImage(this, 'preview-img-<?php echo $room['id']; ?>', 'info_<?php echo $room['id']; ?>');">
                                <span id="info_<?php echo $room['id']; ?>" class="adm-file-info-badge"></span>
                            </div>
                            <input type="text" name="image_url" class="adm-input" value="<?php echo e($room['image_url'] ?? ''); ?>" style="padding-left: 14px; font-size: 11.5px; margin-top: 6px;" placeholder="Or image path / fallback" oninput="document.getElementById('preview-img-<?php echo $room['id']; ?>').src = admin_img_src(this.value);">
                        </div>
                    </div>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Architectural Narrative</label>
                    <textarea name="description" class="adm-input" rows="3" style="padding-left: 14px; resize: vertical;"><?php echo e($room['description']); ?></textarea>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Included Amenities (Comma-separated)</label>
                    <textarea name="amenities" class="adm-input" rows="3" style="padding-left: 14px; resize: vertical;"><?php echo e($room['amenities']); ?></textarea>
                </div>

                <div class="adm-form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 14px; margin-bottom: 22px;">
                    <input type="checkbox" name="is_available" id="avail-<?php echo $room['id']; ?>" value="1" <?php echo $room['is_available'] ? 'checked' : ''; ?> style="width: 18px; height: 18px; accent-color: var(--adm-gold);">
                    <label for="avail-<?php echo $room['id']; ?>" style="cursor: pointer; font-size: 13px; color: #FFFFFF; font-weight: 600;">
                        Open for Online Reservations (Active)
                    </label>
                </div>

                <button type="submit" class="adm-btn-action gold" style="width: 100%; justify-content: center; padding: 12px;">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Save Villa Configuration</span>
                </button>
            </form>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
