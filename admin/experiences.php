<?php
// Food Forest Sanctuary — Experiences moved to Settings Card 07
header('Location: settings.php?tab=experiences');
exit;
require_once __DIR__ . '/includes/header.php';

$pdo = get_db();
$alert_message = '';
$alert_type = 'success';

// Handle Actions (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $alert_message = 'Security validation failed.';
        $alert_type = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        // Add Experience
        if ($action === 'add_experience') {
            $title = trim($_POST['title'] ?? '');
            $badge = trim($_POST['badge'] ?? 'INCLUDED IN STAY');
            $timing = trim($_POST['timing'] ?? '2 Hours • Morning');
            $description = trim($_POST['description'] ?? '');
            $image_url = trim($_POST['image_url'] ?? '');
            if (!empty($_FILES['image_file']['name'])) {
                $up = handle_image_upload($_FILES['image_file'], 'exp');
                if ($up['success']) {
                    $image_url = $up['path'];
                }
            }
            $display_order = (int)($_POST['display_order'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if (!empty($title) && !empty($image_url)) {
                $ins = $pdo->prepare("INSERT INTO experiences (title, badge, timing, description, image_url, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $ins->execute([$title, $badge, $timing, $description, $image_url, $display_order, $is_active]);
                $alert_message = 'New sanctuary experience added successfully.';
            } else {
                $alert_message = 'Title and Image Path/URL are required.';
                $alert_type = 'error';
            }
        }

        // Update Experience
        if ($action === 'update_experience') {
            $exp_id = (int)$_POST['exp_id'];
            $title = trim($_POST['title'] ?? '');
            $badge = trim($_POST['badge'] ?? 'INCLUDED IN STAY');
            $timing = trim($_POST['timing'] ?? '2 Hours • Morning');
            $description = trim($_POST['description'] ?? '');
            $image_url = trim($_POST['image_url'] ?? '');
            if (!empty($_FILES['image_file']['name'])) {
                $up = handle_image_upload($_FILES['image_file'], 'exp');
                if ($up['success']) {
                    $image_url = $up['path'];
                }
            }
            $display_order = (int)($_POST['display_order'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            $upd = $pdo->prepare("UPDATE experiences SET title = ?, badge = ?, timing = ?, description = ?, image_url = ?, display_order = ?, is_active = ? WHERE id = ?");
            $upd->execute([$title, $badge, $timing, $description, $image_url, $display_order, $is_active, $exp_id]);
            $alert_message = 'Experience updated successfully.';
        }

        // Delete Experience
        if ($action === 'delete_experience') {
            $exp_id = (int)$_POST['exp_id'];
            $del = $pdo->prepare("DELETE FROM experiences WHERE id = ?");
            $del->execute([$exp_id]);
            $alert_message = 'Experience removed from active list.';
        }
    }
}

$experiences = $pdo->query("SELECT * FROM experiences ORDER BY display_order ASC, id ASC")->fetchAll();
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
            <i class="fa-solid fa-compass"></i> SANCTUARY RITUALS & ACTIVITIES (<?php echo count($experiences); ?>)
        </span>
    </div>
    <div class="adm-toolbar-right">
        <button type="button" class="adm-btn-action gold" onclick="openAdmModal('modal-add-exp');">
            <i class="fa-solid fa-plus"></i>
            <span>Add New Experience</span>
        </button>
    </div>
</div>

<!-- Experiences Cards Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <?php foreach ($experiences as $exp): ?>
        <div class="adm-villa-card">
            <div style="position: relative; height: 180px; overflow: hidden; background: #07100B;">
                <img src="../<?php echo e($exp['image_url']); ?>" alt="<?php echo e($exp['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/images/treehouse_exterior.png'">
                <div style="position: absolute; top: 10px; left: 10px;">
                    <span class="adm-badge" style="background: rgba(8, 18, 11, 0.85); color: var(--adm-gold-light); border: 1px solid var(--adm-gold-border);">
                        <?php echo e($exp['badge']); ?>
                    </span>
                </div>
                <div style="position: absolute; top: 10px; right: 10px;">
                    <?php if ($exp['is_active']): ?>
                        <span class="adm-badge confirmed"><i class="fa-solid fa-check"></i> Live</span>
                    <?php else: ?>
                        <span class="adm-badge cancelled">Hidden</span>
                    <?php endif; ?>
                </div>
            </div>

            <div style="padding: 18px; display: flex; flex-direction: column; flex-grow: 1;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px; font-size: 11.5px; color: var(--adm-text-muted);">
                    <i class="fa-regular fa-clock" style="color: var(--adm-gold);"></i>
                    <span><?php echo e($exp['timing']); ?></span>
                </div>

                <h3 style="font-family: var(--adm-font-title); font-size: 15px; font-weight: 700; color: #FFFFFF; margin-bottom: 8px;">
                    <?php echo e($exp['title']); ?>
                </h3>

                <p style="font-size: 12.5px; color: var(--adm-text-secondary); line-height: 1.5; margin-bottom: 14px; flex-grow: 1;">
                    <?php echo e($exp['description']); ?>
                </p>

                <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid rgba(255, 255, 255, 0.06); padding-top: 12px; margin-top: auto;">
                    <span style="font-size: 11px; color: var(--adm-text-muted);">
                        Order: <strong>#<?php echo e($exp['display_order']); ?></strong>
                    </span>

                    <div class="adm-actions-cell">
                        <button type="button" class="adm-btn-icon" title="Edit Experience" onclick='editExp(<?php echo json_encode($exp); ?>);'>
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>

                        <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently remove this experience?');">
                            <input type="hidden" name="action" value="delete_experience">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <input type="hidden" name="exp_id" value="<?php echo $exp['id']; ?>">
                            <button type="submit" class="adm-btn-icon danger" title="Delete Experience">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal 1: Add New Experience -->
<div class="adm-modal-backdrop" id="modal-add-exp">
    <div class="adm-modal-content">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-compass" style="color: var(--adm-gold); margin-right: 8px;"></i> Add Sanctuary Experience</h3>
            <button type="button" class="adm-modal-close" data-close-modal="modal-add-exp">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_experience">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

            <div class="adm-modal-body">
                <div class="adm-form-group">
                    <label class="adm-label">Experience Title *</label>
                    <input type="text" name="title" class="adm-input" placeholder="e.g. Wild Honey Gathering & Tasting" required style="padding-left: 14px;">
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Ritual Badge</label>
                        <input type="text" name="badge" class="adm-input" value="INCLUDED IN STAY" placeholder="e.g. EVENING RITUAL" style="padding-left: 14px;">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Duration & Timing</label>
                        <input type="text" name="timing" class="adm-input" value="2 Hours • Morning" placeholder="e.g. 2 Hours • Morning" style="padding-left: 14px;">
                    </div>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Backdrop Photograph</label>
                    <div class="adm-uploader-card adm-uploader-compact">
                        <div class="adm-uploader-preview-box">
                            <img id="add-exp-preview" src="../assets/images/01 (18).jpeg" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/images/treehouse_exterior.png';">
                        </div>
                        <div class="adm-uploader-controls">
                            <div class="adm-uploader-btn-wrap">
                                <label class="adm-uploader-btn" for="add-exp-file">
                                    <i class="fa-solid fa-arrow-up-from-bracket"></i> Choose Photo from Device
                                </label>
                                <input type="file" name="image_file" id="add-exp-file" class="adm-uploader-input" accept="image/*" onchange="previewUploadImage(this, 'add-exp-preview', 'add-exp-info');">
                                <span id="add-exp-info" class="adm-file-info-badge"></span>
                            </div>
                            <input type="text" name="image_url" id="add-exp-img" class="adm-input" value="assets/images/01 (18).jpeg" style="padding-left: 14px; font-size: 11.5px; margin-top: 6px;" placeholder="Or image path / fallback" oninput="document.getElementById('add-exp-preview').src = admin_img_src(this.value);">
                        </div>
                    </div>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Narrative Description</label>
                    <textarea name="description" class="adm-input" rows="3" placeholder="Describe the atmosphere, guided itinerary, and sensory elements..." style="padding-left: 14px; resize: vertical;"></textarea>
                </div>

                <div class="adm-grid-2" style="align-items: center;">
                    <div class="adm-form-group">
                        <label class="adm-label">Display Order (Sort Index)</label>
                        <input type="number" name="display_order" class="adm-input" value="<?php echo count($experiences) + 1; ?>" min="0" style="padding-left: 14px;">
                    </div>
                    <div class="adm-form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 20px;">
                        <input type="checkbox" name="is_active" id="add-exp-active" value="1" checked style="width: 18px; height: 18px; accent-color: var(--adm-gold);">
                        <label for="add-exp-active" style="cursor: pointer; font-size: 13px; color: #FFFFFF; font-weight: 600;">
                            Display on Live Sanctuary Website
                        </label>
                    </div>
                </div>
            </div>

            <div class="adm-modal-footer">
                <button type="button" class="adm-btn-action" data-close-modal="modal-add-exp">Cancel</button>
                <button type="submit" class="adm-btn-action gold">
                    <i class="fa-solid fa-plus"></i> Save & Publish Experience
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Edit Experience -->
<div class="adm-modal-backdrop" id="modal-edit-exp">
    <div class="adm-modal-content">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-pen-to-square" style="color: var(--adm-gold); margin-right: 8px;"></i> Edit Sanctuary Experience</h3>
            <button type="button" class="adm-modal-close" data-close-modal="modal-edit-exp">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_experience">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="exp_id" id="edit-exp-id">

            <div class="adm-modal-body">
                <div class="adm-form-group">
                    <label class="adm-label">Experience Title *</label>
                    <input type="text" name="title" id="edit-exp-title" class="adm-input" required style="padding-left: 14px;">
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Ritual Badge</label>
                        <input type="text" name="badge" id="edit-exp-badge" class="adm-input" style="padding-left: 14px;">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Duration & Timing</label>
                        <input type="text" name="timing" id="edit-exp-timing" class="adm-input" style="padding-left: 14px;">
                    </div>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Backdrop Photograph</label>
                    <div class="adm-uploader-card adm-uploader-compact">
                        <div class="adm-uploader-preview-box">
                            <img id="edit-exp-preview" src="" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='../assets/images/treehouse_exterior.png';">
                        </div>
                        <div class="adm-uploader-controls">
                            <div class="adm-uploader-btn-wrap">
                                <label class="adm-uploader-btn" for="edit-exp-file">
                                    <i class="fa-solid fa-arrow-up-from-bracket"></i> Choose Photo from Device
                                </label>
                                <input type="file" name="image_file" id="edit-exp-file" class="adm-uploader-input" accept="image/*" onchange="previewUploadImage(this, 'edit-exp-preview', 'edit-exp-info');">
                                <span id="edit-exp-info" class="adm-file-info-badge"></span>
                            </div>
                            <input type="text" name="image_url" id="edit-exp-img" class="adm-input" style="padding-left: 14px; font-size: 11.5px; margin-top: 6px;" placeholder="Or image path / fallback" oninput="document.getElementById('edit-exp-preview').src = admin_img_src(this.value);">
                        </div>
                    </div>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Narrative Description</label>
                    <textarea name="description" id="edit-exp-desc" class="adm-input" rows="3" style="padding-left: 14px; resize: vertical;"></textarea>
                </div>

                <div class="adm-grid-2" style="align-items: center;">
                    <div class="adm-form-group">
                        <label class="adm-label">Display Order (Sort Index)</label>
                        <input type="number" name="display_order" id="edit-exp-order" class="adm-input" min="0" style="padding-left: 14px;">
                    </div>
                    <div class="adm-form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 20px;">
                        <input type="checkbox" name="is_active" id="edit-exp-active" value="1" style="width: 18px; height: 18px; accent-color: var(--adm-gold);">
                        <label for="edit-exp-active" style="cursor: pointer; font-size: 13px; color: #FFFFFF; font-weight: 600;">
                            Display on Live Sanctuary Website
                        </label>
                    </div>
                </div>
            </div>

            <div class="adm-modal-footer">
                <button type="button" class="adm-btn-action" data-close-modal="modal-edit-exp">Cancel</button>
                <button type="submit" class="adm-btn-action gold">
                    <i class="fa-solid fa-floppy-disk"></i> Update Experience
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editExp(exp) {
    document.getElementById('edit-exp-id').value = exp.id;
    document.getElementById('edit-exp-title').value = exp.title;
    document.getElementById('edit-exp-badge').value = exp.badge || '';
    document.getElementById('edit-exp-timing').value = exp.timing || '';
    document.getElementById('edit-exp-desc').value = exp.description || '';
    document.getElementById('edit-exp-img').value = exp.image_url;
    document.getElementById('edit-exp-order').value = exp.display_order;
    document.getElementById('edit-exp-active').checked = exp.is_active == 1;
    
    const preview = document.getElementById('edit-exp-preview');
    preview.src = '../' + exp.image_url;
    preview.style.display = 'block';

    openAdmModal('modal-edit-exp');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
