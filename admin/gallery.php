<?php
// Food Forest Sanctuary — Gallery moved to Settings Card 10
header('Location: settings.php?tab=gallery');
exit;
require_once __DIR__ . '/includes/header.php';

$pdo = get_db();
$alert_message = '';
$alert_type = 'success';

// Handle Actions (Add, Edit, Delete, Toggle)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $alert_message = 'Security validation failed.';
        $alert_type = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        // Add Photo
        if ($action === 'add_photo') {
            $title = trim($_POST['title'] ?? '');
            $caption = trim($_POST['caption'] ?? '');
            $tag = trim($_POST['tag'] ?? '');
            $category = trim($_POST['category'] ?? 'Landscape');
            $image_url = trim($_POST['image_url'] ?? '');
            $display_order = (int)($_POST['display_order'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if (!empty($title) && !empty($image_url)) {
                $ins = $pdo->prepare("INSERT INTO gallery (title, caption, tag, category, image_url, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $ins->execute([$title, $caption, $tag, $category, $image_url, $display_order, $is_active]);
                $alert_message = 'New photograph added to the sanctuary gallery.';
            } else {
                $alert_message = 'Title and Image Path/URL are required.';
                $alert_type = 'error';
            }
        }

        // Update Photo
        if ($action === 'update_photo') {
            $photo_id = (int)$_POST['photo_id'];
            $title = trim($_POST['title'] ?? '');
            $caption = trim($_POST['caption'] ?? '');
            $tag = trim($_POST['tag'] ?? '');
            $category = trim($_POST['category'] ?? 'Landscape');
            $image_url = trim($_POST['image_url'] ?? '');
            $display_order = (int)($_POST['display_order'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            $upd = $pdo->prepare("UPDATE gallery SET title = ?, caption = ?, tag = ?, category = ?, image_url = ?, display_order = ?, is_active = ? WHERE id = ?");
            $upd->execute([$title, $caption, $tag, $category, $image_url, $display_order, $is_active, $photo_id]);
            $alert_message = 'Photograph updated successfully.';
        }

        // Delete Photo
        if ($action === 'delete_photo') {
            $photo_id = (int)$_POST['photo_id'];
            $del = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
            $del->execute([$photo_id]);
            $alert_message = 'Photograph removed from gallery.';
        }
    }
}

$photos = $pdo->query("SELECT * FROM gallery ORDER BY display_order ASC, id ASC")->fetchAll();
?>

<?php if (!empty($alert_message)): ?>
    <div class="adm-alert adm-alert-<?php echo $alert_type; ?>" style="margin-bottom: 22px;">
        <i class="fa-solid <?php echo $alert_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
        <span><?php echo e($alert_message); ?></span>
    </div>
<?php endif; ?>

<!-- Action Toolbar -->
<div class="adm-toolbar-card">
    <div class="adm-toolbar-left">
        <span style="font-family: var(--adm-font-title); font-weight: 700; color: var(--adm-text-gold); letter-spacing: 1px;">
            <i class="fa-solid fa-camera-retro"></i> CURATED PHOTOGRAPHY (<?php echo count($photos); ?>)
        </span>
    </div>
    <div class="adm-toolbar-right">
        <button type="button" class="adm-btn-action gold" onclick="openAdmModal('modal-add-photo');">
            <i class="fa-solid fa-plus"></i>
            <span>Add Sanctuary Photo</span>
        </button>
    </div>
</div>

<!-- Photos Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <?php foreach ($photos as $photo): ?>
        <div class="adm-villa-card">
            <div style="position: relative; height: 180px; overflow: hidden; background: #07100B;">
                <img src="../<?php echo e($photo['image_url']); ?>" alt="<?php echo e($photo['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/images/treehouse_exterior.png'">
                <div style="position: absolute; top: 10px; left: 10px;">
                    <span class="adm-badge" style="background: rgba(8, 18, 11, 0.85); color: var(--adm-gold-light); border: 1px solid var(--adm-gold-border);">
                        <?php echo e($photo['category']); ?>
                    </span>
                </div>
                <div style="position: absolute; top: 10px; right: 10px;">
                    <?php if ($photo['is_active']): ?>
                        <span class="adm-badge confirmed"><i class="fa-solid fa-check"></i> Live</span>
                    <?php else: ?>
                        <span class="adm-badge cancelled">Hidden</span>
                    <?php endif; ?>
                </div>
            </div>

            <div style="padding: 18px; display: flex; flex-direction: column; flex-grow: 1;">
                <h3 style="font-family: var(--adm-font-title); font-size: 15px; font-weight: 700; color: #FFFFFF; margin-bottom: 4px;">
                    <?php echo e($photo['title']); ?>
                </h3>
                <span style="font-size: 11px; text-transform: uppercase; color: var(--adm-gold); letter-spacing: 1px; margin-bottom: 8px;">
                    <?php echo e($photo['tag'] ?: 'SANCTUARY ARCHIVE'); ?>
                </span>
                <p style="font-size: 12.5px; color: var(--adm-text-secondary); line-height: 1.5; margin-bottom: 14px; flex-grow: 1;">
                    <?php echo e($photo['caption']); ?>
                </p>

                <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid rgba(255, 255, 255, 0.06); padding-top: 12px; margin-top: auto;">
                    <span style="font-size: 11px; color: var(--adm-text-muted);">
                        Order: <strong>#<?php echo e($photo['display_order']); ?></strong>
                    </span>

                    <div class="adm-actions-cell">
                        <button type="button" class="adm-btn-icon" title="Edit Photo" onclick='editPhoto(<?php echo json_encode($photo); ?>);'>
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>

                        <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently remove this photograph?');">
                            <input type="hidden" name="action" value="delete_photo">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <input type="hidden" name="photo_id" value="<?php echo $photo['id']; ?>">
                            <button type="submit" class="adm-btn-icon danger" title="Delete Photo">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal 1: Add New Photo -->
<div class="adm-modal-backdrop" id="modal-add-photo">
    <div class="adm-modal-content">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-camera" style="color: var(--adm-gold); margin-right: 8px;"></i> Add Sanctuary Photo</h3>
            <button type="button" class="adm-modal-close" data-close-modal="modal-add-photo">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="add_photo">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

            <div class="adm-modal-body">
                <div class="adm-form-group">
                    <label class="adm-label">Photo Title *</label>
                    <input type="text" name="title" class="adm-input" placeholder="e.g. Misty Mountain Sunrise" required style="padding-left: 14px;">
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Category</label>
                        <select name="category" class="adm-input" style="padding-left: 14px;">
                            <option value="Villas & Stays">Villas & Stays</option>
                            <option value="Handcrafted Living">Handcrafted Living</option>
                            <option value="Landscape">Landscape & Horizons</option>
                            <option value="Gastronomy">Farm Gastronomy</option>
                            <option value="Orchards">Organic Orchards</option>
                            <option value="Flora">Flora & Pollinators</option>
                            <option value="Architecture">Biophilic Architecture</option>
                            <option value="Nightscape">Night Sky & Stars</option>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Display Badge / Tag</label>
                        <input type="text" name="tag" class="adm-input" placeholder="e.g. ALPINE HORIZON" style="padding-left: 14px;">
                    </div>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Image Path or URL *</label>
                    <input type="text" name="image_url" class="adm-input" placeholder="e.g. assets/images/01 (25).jpeg" required style="padding-left: 14px;">
                    <span style="font-size: 11px; color: var(--adm-text-muted); margin-top: 4px; display: block;">
                        Relative to site root (e.g. assets/images/...) or external HTTPS image URL.
                    </span>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Poetic Caption / Narrative</label>
                    <textarea name="caption" class="adm-input" rows="3" placeholder="Brief story or reflection for this photograph..." style="padding-left: 14px; resize: vertical;"></textarea>
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Display Sequence</label>
                        <input type="number" name="display_order" class="adm-input" value="10" style="padding-left: 14px;">
                    </div>
                    <div class="adm-form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 24px;">
                        <input type="checkbox" name="is_active" id="add-active" value="1" checked style="width: 18px; height: 18px; accent-color: var(--adm-gold);">
                        <label for="add-active" style="cursor: pointer; color: #FFFFFF; font-weight: 600;">Visible in Live Gallery</label>
                    </div>
                </div>
            </div>

            <div class="adm-modal-footer">
                <button type="button" class="adm-btn-action outline" data-close-modal="modal-add-photo">Cancel</button>
                <button type="submit" class="adm-btn-action gold">Save Photograph</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Edit Photo -->
<div class="adm-modal-backdrop" id="modal-edit-photo">
    <div class="adm-modal-content">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-pen" style="color: var(--adm-gold); margin-right: 8px;"></i> Edit Photograph</h3>
            <button type="button" class="adm-modal-close" data-close-modal="modal-edit-photo">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="update_photo">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="photo_id" id="edit-photo-id">

            <div class="adm-modal-body">
                <div class="adm-form-group">
                    <label class="adm-label">Photo Title *</label>
                    <input type="text" name="title" id="edit-photo-title" class="adm-input" required style="padding-left: 14px;">
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Category</label>
                        <select name="category" id="edit-photo-category" class="adm-input" style="padding-left: 14px;">
                            <option value="Villas & Stays">Villas & Stays</option>
                            <option value="Handcrafted Living">Handcrafted Living</option>
                            <option value="Landscape">Landscape & Horizons</option>
                            <option value="Gastronomy">Farm Gastronomy</option>
                            <option value="Orchards">Organic Orchards</option>
                            <option value="Flora">Flora & Pollinators</option>
                            <option value="Architecture">Biophilic Architecture</option>
                            <option value="Nightscape">Night Sky & Stars</option>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Badge / Tag</label>
                        <input type="text" name="tag" id="edit-photo-tag" class="adm-input" style="padding-left: 14px;">
                    </div>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Image Path or URL *</label>
                    <input type="text" name="image_url" id="edit-photo-url" class="adm-input" required style="padding-left: 14px;">
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Caption / Narrative</label>
                    <textarea name="caption" id="edit-photo-caption" class="adm-input" rows="3" style="padding-left: 14px; resize: vertical;"></textarea>
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Display Order</label>
                        <input type="number" name="display_order" id="edit-photo-order" class="adm-input" style="padding-left: 14px;">
                    </div>
                    <div class="adm-form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 24px;">
                        <input type="checkbox" name="is_active" id="edit-photo-active" value="1" style="width: 18px; height: 18px; accent-color: var(--adm-gold);">
                        <label for="edit-photo-active" style="cursor: pointer; color: #FFFFFF; font-weight: 600;">Visible in Live Gallery</label>
                    </div>
                </div>
            </div>

            <div class="adm-modal-footer">
                <button type="button" class="adm-btn-action outline" data-close-modal="modal-edit-photo">Cancel</button>
                <button type="submit" class="adm-btn-action gold">Update Photo</button>
            </div>
        </form>
    </div>
</div>

<script>
function editPhoto(p) {
    document.getElementById('edit-photo-id').value = p.id;
    document.getElementById('edit-photo-title').value = p.title;
    document.getElementById('edit-photo-category').value = p.category;
    document.getElementById('edit-photo-tag').value = p.tag || '';
    document.getElementById('edit-photo-url').value = p.image_url;
    document.getElementById('edit-photo-caption').value = p.caption || '';
    document.getElementById('edit-photo-order').value = p.display_order;
    document.getElementById('edit-photo-active').checked = (p.is_active == 1);
    openAdmModal('modal-edit-photo');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
