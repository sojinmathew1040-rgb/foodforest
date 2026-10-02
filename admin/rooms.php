<?php
// =========================================================================
// Food Forest Sanctuary — Villas, Stays & Pricing CMS Management
// =========================================================================
require_once __DIR__ . '/includes/header.php';

$pdo = get_db();
ensure_rooms_pricing_columns($pdo);

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
        $single_room_rate = !empty($_POST['single_room_rate']) ? (float)$_POST['single_room_rate'] : $rate_per_night;
        $extra_guest_rate = (float)($_POST['extra_guest_rate'] ?? 1500.00);
        $extra_child_rate = (float)($_POST['extra_child_rate'] ?? 800.00);
        $min_guests = (int)($_POST['min_guests'] ?? 2);
        $base_guests = (int)($_POST['base_guests'] ?? 2);
        $max_guests = (int)$_POST['max_guests'];
        $elevation = trim($_POST['elevation']);
        $stay_type = trim($_POST['stay_type'] ?? 'treehouse');
        $structure_type = trim($_POST['structure_type'] ?? 'single_hut');
        $description = trim($_POST['description']);
        $amenities = trim($_POST['amenities']);
        $inventory_checklist = trim($_POST['inventory_checklist'] ?? '');
        $image_url = trim($_POST['image_url'] ?? '');
        
        $file_key = 'image_file_' . $villa_id;
        if (!empty($_FILES[$file_key]['name'])) {
            $up = handle_image_upload($_FILES[$file_key], 'villa');
            if ($up['success']) {
                $image_url = $up['path'];
            }
        }
        $is_available = isset($_POST['is_available']) ? 1 : 0;

        $stmt = $pdo->prepare("UPDATE rooms SET 
            title = ?, 
            rate_per_night = ?, 
            single_room_rate = ?, 
            extra_guest_rate = ?, 
            extra_child_rate = ?, 
            min_guests = ?, 
            base_guests = ?, 
            max_guests = ?, 
            elevation = ?, 
            stay_type = ?, 
            structure_type = ?, 
            description = ?, 
            amenities = ?, 
            inventory_checklist = ?, 
            image_url = ?, 
            is_available = ? 
            WHERE id = ?");
            
        $stmt->execute([
            $title, 
            $rate_per_night, 
            $single_room_rate, 
            $extra_guest_rate, 
            $extra_child_rate, 
            $min_guests, 
            $base_guests, 
            $max_guests, 
            $elevation, 
            $stay_type, 
            $structure_type, 
            $description, 
            $amenities, 
            $inventory_checklist, 
            $image_url, 
            $is_available, 
            $villa_id
        ]);

        $alert_message = 'Villa configuration, tariffs & room asset checklist saved successfully.';
    }
}

$rooms = $pdo->query("SELECT * FROM rooms ORDER BY id ASC")->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 14px;">
    <div>
        <h1 style="font-size: 1.8rem; color: #FFFFFF; font-family: var(--adm-font-serif); margin: 0 0 4px;">
            <i class="fa-solid fa-tree" style="color: var(--adm-gold); margin-right: 8px;"></i> Sanctuary Chalets, Villas &amp; Tariffs
        </h1>
        <p style="font-size: 13.5px; color: var(--adm-text-muted); margin: 0;">
            Manage chalet base rates, extra guest/child pricing, occupancy thresholds, and MakeMyTrip verified amenities.
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="edit_section.php?section=rooms" class="adm-btn-action" style="background: rgba(197, 160, 89, 0.2); color: var(--adm-gold); border: 1px solid var(--adm-gold); text-decoration: none;">
            <i class="fa-solid fa-layer-group"></i> Manage Stay Categories
        </a>
        <a href="../booking.php" target="_blank" class="adm-btn-action" style="background: rgba(197, 160, 89, 0.15); color: var(--adm-gold); border: 1px solid rgba(197, 160, 89, 0.4); text-decoration: none;">
            <i class="fa-solid fa-arrow-up-right-from-square"></i> View Live Booking Page
        </a>
    </div>
</div>

<?php if (!empty($alert_message)): ?>
    <div class="adm-alert adm-alert-<?php echo $alert_type; ?>" style="margin-bottom: 24px;">
        <i class="fa-solid <?php echo $alert_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
        <span><?php echo e($alert_message); ?></span>
    </div>
<?php endif; ?>

<div class="adm-villa-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(440px, 1fr)); gap: 24px;">
    <?php foreach ($rooms as $room): 
        $is_treehouse = ($room['slug'] === 'treehouse' || ($room['stay_type'] ?? '') === 'treehouse');
        $is_duplex = (($room['structure_type'] ?? '') === 'duplex_hut');
    ?>
        <div class="adm-villa-card" id="villa-<?php echo e($room['slug']); ?>" style="background: #0E2016; border: 1px solid rgba(197, 160, 89, 0.3); border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
            <div class="adm-villa-header" style="display: flex; justify-content: space-between; align-items: center; padding: 18px 20px; background: rgba(0,0,0,0.25); border-bottom: 1px solid rgba(197, 160, 89, 0.2);">
                <div>
                    <h2 class="adm-villa-title" style="font-size: 1.35rem; color: #FFFFFF; font-family: var(--adm-font-serif); margin: 0 0 2px;">
                        <i class="fa-solid <?php echo $is_duplex ? 'fa-layer-group' : ($is_treehouse ? 'fa-tree' : 'fa-house-chimney'); ?>" style="color: var(--adm-gold); margin-right: 8px;"></i>
                        <?php echo e($room['title']); ?>
                    </h2>
                    <span class="adm-villa-elevation" style="font-size: 11.5px; color: var(--adm-gold); font-weight: 700; text-transform: uppercase; letter-spacing: 1px;"><?php echo e($room['elevation']); ?></span>
                </div>
                <div>
                    <?php if ($room['is_available']): ?>
                        <span class="adm-badge confirmed" style="background: rgba(34, 197, 94, 0.2); color: #4ADE80; border: 1px solid rgba(34, 197, 94, 0.4); padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;"><i class="fa-solid fa-circle-check"></i> Bookable</span>
                    <?php else: ?>
                        <span class="adm-badge cancelled" style="background: rgba(239, 68, 68, 0.2); color: #F87171; border: 1px solid rgba(239, 68, 68, 0.4); padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;"><i class="fa-solid fa-ban"></i> Blocked</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Villa Image Banner -->
            <div style="height: 180px; overflow: hidden; position: relative; background: #07100B;">
                <img id="preview-img-<?php echo $room['id']; ?>" src="../<?php echo e(($room['image_url'] ?? '') ?: ($is_treehouse ? 'assets/images/treehouse_exterior.png' : 'assets/images/mudhouse_exterior.png')); ?>" alt="<?php echo e($room['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <?php 
            $rm_photos = [];
            if (!empty($room['photos'])) {
                $dec = json_decode($room['photos'], true);
                if (is_array($dec)) $rm_photos = array_filter($dec);
            }
            if (!empty($rm_photos)): 
            ?>
            <div style="display: flex; gap: 6px; padding: 8px 14px; background: rgba(0,0,0,0.35); overflow-x: auto; border-bottom: 1px solid rgba(197, 160, 89, 0.2); align-items: center;">
                <span style="font-size: 10.5px; color: var(--adm-gold); font-weight: 700; text-transform: uppercase; white-space: nowrap; margin-right: 4px;">
                    <i class="fa-solid fa-images"></i> Photos (<?php echo count($rm_photos); ?>):
                </span>
                <?php foreach ($rm_photos as $p_idx => $p_url): ?>
                    <img src="../<?php echo e($p_url); ?>" alt="Photo <?php echo $p_idx + 1; ?>" style="width: 52px; height: 36px; object-fit: cover; border-radius: 4px; border: 1.5px solid <?php echo ($p_url === $room['image_url']) ? 'var(--adm-gold)' : 'rgba(255,255,255,0.2)'; ?>; cursor: pointer; flex-shrink: 0;" onclick="document.getElementById('preview-img-<?php echo $room['id']; ?>').src = this.src;" title="Click to preview">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="adm-villa-body" enctype="multipart/form-data" style="padding: 20px;">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" name="villa_id" value="<?php echo $room['id']; ?>">

                <div class="adm-villa-rate-row" style="background: rgba(197, 160, 89, 0.1); border: 1px solid rgba(197, 160, 89, 0.3); border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 13px; color: #D3E0D8;">Nightly Base Tariff:</span>
                    <div>
                        <span class="adm-villa-rate" style="font-size: 1.4rem; color: var(--adm-gold); font-weight: 700; font-family: var(--adm-font-serif);">₹<?php echo number_format($room['rate_per_night'], 0, '.', ','); ?></span>
                        <span class="adm-villa-period" style="font-size: 11px; color: #94A3B8;">/ nt (Base <?php echo (int)($room['base_guests'] ?? 2); ?> Guests)</span>
                    </div>
                </div>

                <div class="adm-grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div class="adm-form-group">
                        <label class="adm-label" style="font-size: 11.5px; color: #E2E8F0; font-weight: 700; display: block; margin-bottom: 4px;">Villa Display Title *</label>
                        <input type="text" name="title" class="adm-input" value="<?php echo e($room['title']); ?>" required style="padding: 9px 12px; font-size: 13px; width: 100%; background: #07150E; border: 1px solid rgba(197, 160, 89, 0.3); color: #FFFFFF; border-radius: 6px;">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label" style="font-size: 11.5px; color: #E2E8F0; font-weight: 700; display: block; margin-bottom: 4px;">Base Nightly Rate (₹) *</label>
                        <input type="number" name="rate_per_night" class="adm-input" value="<?php echo e($room['rate_per_night']); ?>" step="100" required style="padding: 9px 12px; font-size: 13px; width: 100%; background: #07150E; border: 1px solid rgba(197, 160, 89, 0.3); color: #FFFFFF; border-radius: 6px;">
                    </div>
                </div>

                <!-- Extra Guest & Child Pricing Row -->
                <div class="adm-grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; background: rgba(0,0,0,0.2); padding: 10px; border-radius: 8px; border: 1px dashed rgba(197, 160, 89, 0.25);">
                    <div class="adm-form-group">
                        <label class="adm-label" style="font-size: 11px; color: var(--adm-gold); font-weight: 700; display: block; margin-bottom: 4px;">
                            <i class="fa-solid fa-user-plus"></i> Extra Adult Rate (₹/nt)
                        </label>
                        <input type="number" name="extra_guest_rate" class="adm-input" value="<?php echo e($room['extra_guest_rate'] ?? 1500); ?>" step="100" required style="padding: 8px 12px; font-size: 13px; width: 100%; background: #07150E; border: 1px solid rgba(197, 160, 89, 0.3); color: #FFFFFF; border-radius: 6px;">
                        <small style="font-size: 10px; color: #94A3B8; display: block; margin-top: 2px;">Includes extra bedding & all 4 meals</small>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label" style="font-size: 11px; color: var(--adm-gold); font-weight: 700; display: block; margin-bottom: 4px;">
                            <i class="fa-solid fa-child"></i> Extra Child Rate (5–11y)
                        </label>
                        <input type="number" name="extra_child_rate" class="adm-input" value="<?php echo e($room['extra_child_rate'] ?? 800); ?>" step="50" required style="padding: 8px 12px; font-size: 13px; width: 100%; background: #07150E; border: 1px solid rgba(197, 160, 89, 0.3); color: #FFFFFF; border-radius: 6px;">
                        <small style="font-size: 10px; color: #94A3B8; display: block; margin-top: 2px;">Infants (under 5) are always free</small>
                    </div>
                </div>

                <!-- Guests Capacity Row -->
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 12px;">
                    <div class="adm-form-group">
                        <label class="adm-label" style="font-size: 11px; color: #E2E8F0; display: block; margin-bottom: 4px;">Min Guests</label>
                        <input type="number" name="min_guests" class="adm-input" value="<?php echo (int)($room['min_guests'] ?? 2); ?>" min="1" max="10" required style="padding: 8px; font-size: 12.5px; width: 100%; background: #07150E; border: 1px solid rgba(197, 160, 89, 0.3); color: #FFFFFF; border-radius: 6px;">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label" style="font-size: 11px; color: #E2E8F0; display: block; margin-bottom: 4px;">Base Guests Included</label>
                        <input type="number" name="base_guests" class="adm-input" value="<?php echo (int)($room['base_guests'] ?? 2); ?>" min="1" max="10" required style="padding: 8px; font-size: 12.5px; width: 100%; background: #07150E; border: 1px solid rgba(197, 160, 89, 0.3); color: #FFFFFF; border-radius: 6px;">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label" style="font-size: 11px; color: #E2E8F0; display: block; margin-bottom: 4px;">Max Guests Cap</label>
                        <input type="number" name="max_guests" class="adm-input" value="<?php echo (int)$room['max_guests']; ?>" min="1" max="10" required style="padding: 8px; font-size: 12.5px; width: 100%; background: #07150E; border: 1px solid rgba(197, 160, 89, 0.3); color: #FFFFFF; border-radius: 6px;">
                    </div>
                </div>

                <!-- Stay & Structure Type -->
                <div class="adm-grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div class="adm-form-group">
                        <label class="adm-label" style="font-size: 11.5px; color: #E2E8F0; font-weight: 700; display: block; margin-bottom: 4px;">Structure Type</label>
                        <select name="structure_type" class="adm-input" style="padding: 8px 12px; font-size: 12.5px; width: 100%; background: #07150E; border: 1px solid rgba(197, 160, 89, 0.3); color: #FFFFFF; border-radius: 6px;">
                            <option value="single_hut" <?php echo (($room['structure_type'] ?? '') === 'single_hut') ? 'selected' : ''; ?>>Single Cottage / Chalet</option>
                            <option value="duplex_hut" <?php echo (($room['structure_type'] ?? '') === 'duplex_hut') ? 'selected' : ''; ?>>Duplex (2 Adjoining Suites)</option>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label" style="font-size: 11.5px; color: #E2E8F0; font-weight: 700; display: block; margin-bottom: 4px;">Single Room Rate (if Duplex)</label>
                        <input type="number" name="single_room_rate" class="adm-input" value="<?php echo e($room['single_room_rate'] ?? $room['rate_per_night']); ?>" step="100" style="padding: 8px 12px; font-size: 12.5px; width: 100%; background: #07150E; border: 1px solid rgba(197, 160, 89, 0.3); color: #FFFFFF; border-radius: 6px;">
                    </div>
                </div>

                <div class="adm-grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div class="adm-form-group">
                        <label class="adm-label" style="font-size: 11.5px; color: #E2E8F0; font-weight: 700; display: block; margin-bottom: 4px;">Elevation / Concept Badge</label>
                        <input type="text" name="elevation" class="adm-input" value="<?php echo e($room['elevation']); ?>" required style="padding: 8px 12px; font-size: 12.5px; width: 100%; background: #07150E; border: 1px solid rgba(197, 160, 89, 0.3); color: #FFFFFF; border-radius: 6px;">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label" style="font-size: 11.5px; color: #E2E8F0; font-weight: 700; display: block; margin-bottom: 4px;">Stay Category</label>
                        <select name="stay_type" class="adm-input" style="padding: 8px 12px; font-size: 12.5px; width: 100%; background: #07150E; border: 1px solid rgba(197, 160, 89, 0.3); color: #FFFFFF; border-radius: 6px;">
                            <?php echo render_stay_category_options($room['stay_type'] ?? 'woodhouse', false); ?>
                        </select>
                    </div>
                </div>

                <div class="adm-form-group" style="margin-bottom: 12px;">
                    <label class="adm-label" style="font-size: 11.5px; color: #E2E8F0; font-weight: 700; display: block; margin-bottom: 4px;">Villa Photograph</label>
                    <div class="adm-uploader-card adm-uploader-compact" style="background: rgba(0,0,0,0.2); padding: 10px; border-radius: 6px; border: 1px dashed rgba(197, 160, 89, 0.3);">
                        <div class="adm-uploader-controls">
                            <div class="adm-uploader-btn-wrap" style="margin-bottom: 6px;">
                                <label class="adm-uploader-btn" for="image_file_<?php echo $room['id']; ?>" style="display: inline-block; background: rgba(197, 160, 89, 0.2); color: var(--adm-gold); padding: 6px 12px; border-radius: 4px; font-size: 11.5px; cursor: pointer;">
                                    <i class="fa-solid fa-arrow-up-from-bracket"></i> Choose Photo from Device
                                </label>
                                <input type="file" name="image_file_<?php echo $room['id']; ?>" id="image_file_<?php echo $room['id']; ?>" class="adm-uploader-input" accept="image/*" style="display: none;" onchange="previewUploadImage(this, 'preview-img-<?php echo $room['id']; ?>', 'info_<?php echo $room['id']; ?>');">
                                <span id="info_<?php echo $room['id']; ?>" class="adm-file-info-badge" style="font-size: 11px; color: #4ADE80; margin-left: 8px;"></span>
                            </div>
                            <input type="text" name="image_url" class="adm-input" value="<?php echo e($room['image_url'] ?? ''); ?>" style="padding: 6px 10px; font-size: 11.5px; width: 100%; background: #07150E; border: 1px solid rgba(197, 160, 89, 0.3); color: #FFFFFF; border-radius: 4px;" placeholder="Or image path / fallback">
                        </div>
                    </div>
                </div>

                <div class="adm-form-group" style="margin-bottom: 12px;">
                    <label class="adm-label" style="font-size: 11.5px; color: #E2E8F0; font-weight: 700; display: block; margin-bottom: 4px;">Architectural Narrative</label>
                    <textarea name="description" class="adm-input" rows="2" style="padding: 8px 10px; font-size: 12px; width: 100%; background: #07150E; border: 1px solid rgba(197, 160, 89, 0.3); color: #FFFFFF; border-radius: 6px; resize: vertical;"><?php echo e($room['description']); ?></textarea>
                </div>

                <div class="adm-form-group" style="margin-bottom: 12px;">
                    <label class="adm-label" style="font-size: 11.5px; color: #E2E8F0; font-weight: 700; display: block; margin-bottom: 4px;">
                        MakeMyTrip Categorized Amenities (Comma-separated)
                    </label>
                    <textarea name="amenities" class="adm-input" rows="2" style="padding: 8px 10px; font-size: 12px; width: 100%; background: #07150E; border: 1px solid rgba(197, 160, 89, 0.3); color: #FFFFFF; border-radius: 6px; resize: vertical;" placeholder="Wi-Fi, Free Parking, Power Backup, Concierge, Doctor on Call, Yoga Deck, Balcony, Teak Furniture, 4 Farm Meals, Solar Hot Water..."><?php echo e($room['amenities']); ?></textarea>
                </div>

                <?php 
                $default_inv_text = "Physical Room Key & Brass Keychain\nTV Unit & Set-Top Box\nTV Remote & Set-Top Remote\nElectric Water Kettle & Ceramic Tray\nArtisan Coffee Mugs & Spoons (2 Sets)\nGlass Water Pitcher / Spring Bottles\nHairdryer (Grooming Kit)\nEmergency High-Beam LED Torch\nHeavy-Duty Walking Umbrellas (2 Nos)\nMosquito Vaporizer Unit\nOrganic Cotton Bath & Hand Towels (4 Nos)\nHeavy Duck-Down Quilts & Blankets (2 Sets)\nBalcony Cane Chairs & Teak Table";
                $inv_val = !empty($room['inventory_checklist']) ? $room['inventory_checklist'] : $default_inv_text;
                ?>
                <div class="adm-form-group" style="margin-bottom: 12px; background: rgba(0,0,0,0.25); border: 1px dashed rgba(197, 160, 89, 0.35); border-radius: 8px; padding: 10px 12px;">
                    <label class="adm-label" style="font-size: 11.5px; color: var(--adm-gold); font-weight: 700; display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <span><i class="fa-solid fa-boxes-stacked"></i> Physical Room Asset &amp; Inventory Checklist (Admin Inspection)</span>
                        <span style="font-size: 10px; color: #94A3B8; font-weight: normal;">1 item per line</span>
                    </label>
                    <textarea name="inventory_checklist" class="adm-input" rows="4" style="padding: 8px 10px; font-size: 11.5px; width: 100%; background: #07150E; border: 1px solid rgba(197, 160, 89, 0.3); color: #FFFFFF; border-radius: 6px; font-family: monospace; line-height: 1.4; resize: vertical;" placeholder="One asset item per line..."><?php echo e($inv_val); ?></textarea>
                    <small style="font-size: 10.5px; color: #94A3B8; display: block; margin-top: 3px;">
                        Used by staff during check-out inspection to cross-check that all provided room assets are intact before guest departure.
                    </small>
                </div>

                <div class="adm-form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 10px; margin-bottom: 18px;">
                    <input type="checkbox" name="is_available" id="avail-<?php echo $room['id']; ?>" value="1" <?php echo $room['is_available'] ? 'checked' : ''; ?> style="width: 18px; height: 18px; accent-color: var(--adm-gold); cursor: pointer;">
                    <label for="avail-<?php echo $room['id']; ?>" style="cursor: pointer; font-size: 12.5px; color: #FFFFFF; font-weight: 600;">
                        Open for Online Reservations (Active on Booking System)
                    </label>
                </div>

                <button type="submit" class="adm-btn-action gold" style="width: 100%; justify-content: center; padding: 11px; background: linear-gradient(135deg, #C5A059 0%, #A68037 100%); color: #0B1810; font-weight: 700; border: none; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Save Villa Configuration &amp; Rates</span>
                </button>
            </form>
        </div>
    <?php endforeach; ?>
</div>

<script>
function previewUploadImage(input, previewId, infoId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(previewId).src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
        if (infoId) {
            document.getElementById(infoId).innerText = input.files[0].name + ' (' + Math.round(input.files[0].size/1024) + ' KB)';
        }
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
