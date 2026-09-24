<?php
// Food Forest Sanctuary — Testimonials Manager
header('Location: edit_section.php?section=testimonials');
exit;
require_once __DIR__ . '/includes/header.php';

$pdo = get_db();
$alert_message = '';
$alert_type = 'success';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $alert_message = 'Security validation failed.';
        $alert_type = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        // Add Testimonial
        if ($action === 'add_testimonial') {
            $name = trim($_POST['guest_name'] ?? '');
            $location = trim($_POST['guest_location'] ?? '');
            $stay_badge = trim($_POST['stay_badge'] ?? 'CANOPY TREEHOUSE');
            $stars = (int)($_POST['stars'] ?? 5);
            $quote = trim($_POST['quote'] ?? '');
            $initials = trim($_POST['initials'] ?? '');
            $order = (int)($_POST['display_order'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if (empty($initials) && !empty($name)) {
                $parts = explode(' ', $name);
                $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
            }

            if (!empty($name) && !empty($quote)) {
                $ins = $pdo->prepare("INSERT INTO testimonials (guest_name, guest_location, stay_badge, stars, quote, initials, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $ins->execute([$name, $location, $stay_badge, $stars, $quote, $initials, $order, $is_active]);
                $alert_message = 'New guest story added successfully.';
            } else {
                $alert_message = 'Guest Name and Testimonial Quote are required.';
                $alert_type = 'error';
            }
        }

        // Edit Testimonial
        if ($action === 'update_testimonial') {
            $t_id = (int)$_POST['testimonial_id'];
            $name = trim($_POST['guest_name'] ?? '');
            $location = trim($_POST['guest_location'] ?? '');
            $stay_badge = trim($_POST['stay_badge'] ?? 'CANOPY TREEHOUSE');
            $stars = (int)($_POST['stars'] ?? 5);
            $quote = trim($_POST['quote'] ?? '');
            $initials = trim($_POST['initials'] ?? '');
            $order = (int)($_POST['display_order'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            $upd = $pdo->prepare("UPDATE testimonials SET guest_name = ?, guest_location = ?, stay_badge = ?, stars = ?, quote = ?, initials = ?, display_order = ?, is_active = ? WHERE id = ?");
            $upd->execute([$name, $location, $stay_badge, $stars, $quote, $initials, $order, $is_active, $t_id]);
            $alert_message = 'Guest story updated.';
        }

        // Delete Testimonial
        if ($action === 'delete_testimonial') {
            $t_id = (int)$_POST['testimonial_id'];
            $del = $pdo->prepare("DELETE FROM testimonials WHERE id = ?");
            $del->execute([$t_id]);
            $alert_message = 'Guest story removed.';
        }
    }
}

$testimonials = $pdo->query("SELECT * FROM testimonials ORDER BY display_order ASC, id ASC")->fetchAll();
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
            <i class="fa-solid fa-quote-left"></i> GUEST TESTIMONIALS (<?php echo count($testimonials); ?>)
        </span>
    </div>
    <div class="adm-toolbar-right">
        <button type="button" class="adm-btn-action gold" onclick="openAdmModal('modal-add-testimonial');">
            <i class="fa-solid fa-plus"></i>
            <span>Add Guest Story</span>
        </button>
    </div>
</div>

<!-- Testimonials Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 22px; margin-bottom: 30px;">
    <?php foreach ($testimonials as $t): ?>
        <div class="adm-villa-card">
            <div style="padding: 18px 20px; background: rgba(14, 28, 19, 0.7); border-bottom: 1px solid rgba(255, 255, 255, 0.06); display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="color: var(--adm-gold-light); font-size: 12px; margin-bottom: 4px;">
                        <?php for ($i = 0; $i < $t['stars']; $i++): ?>
                            <i class="fa-solid fa-star"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="adm-badge" style="background: rgba(197, 160, 89, 0.12); color: var(--adm-gold-light); border: 1px solid var(--adm-gold-border);">
                        <?php echo e($t['stay_badge']); ?>
                    </span>
                </div>
                <div>
                    <?php if ($t['is_active']): ?>
                        <span class="adm-badge confirmed"><i class="fa-solid fa-check"></i> Published</span>
                    <?php else: ?>
                        <span class="adm-badge cancelled">Hidden</span>
                    <?php endif; ?>
                </div>
            </div>

            <div style="padding: 20px; display: flex; flex-direction: column; flex-grow: 1;">
                <p style="font-family: var(--adm-font-serif); font-size: 15px; font-style: italic; color: #FFFFFF; line-height: 1.6; margin-bottom: 18px; flex-grow: 1;">
                    "<?php echo e($t['quote']); ?>"
                </p>

                <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid rgba(255, 255, 255, 0.06); padding-top: 14px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div class="adm-user-avatar" style="width: 34px; height: 34px; font-size: 12px;">
                            <?php echo e($t['initials'] ?: 'FF'); ?>
                        </div>
                        <div>
                            <div style="font-weight: 700; color: #FFFFFF; font-size: 13px;"><?php echo e($t['guest_name']); ?></div>
                            <div style="font-size: 11px; color: var(--adm-text-muted);"><?php echo e($t['guest_location']); ?></div>
                        </div>
                    </div>

                    <div class="adm-actions-cell">
                        <button type="button" class="adm-btn-icon" title="Edit Story" onclick='editTestimonial(<?php echo json_encode($t); ?>);'>
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>

                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this guest story?');">
                            <input type="hidden" name="action" value="delete_testimonial">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <input type="hidden" name="testimonial_id" value="<?php echo $t['id']; ?>">
                            <button type="submit" class="adm-btn-icon danger" title="Delete">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal 1: Add New Testimonial -->
<div class="adm-modal-backdrop" id="modal-add-testimonial">
    <div class="adm-modal-content">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-feather" style="color: var(--adm-gold); margin-right: 8px;"></i> Add Guest Chronicle</h3>
            <button type="button" class="adm-modal-close" data-close-modal="modal-add-testimonial">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="add_testimonial">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

            <div class="adm-modal-body">
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Guest Full Name *</label>
                        <input type="text" name="guest_name" class="adm-input" placeholder="e.g. Ananya & Siddharth Nair" required style="padding-left: 14px;">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Location & Stay Date</label>
                        <input type="text" name="guest_location" class="adm-input" placeholder="e.g. Kochi, India · Stayed Nov 2025" style="padding-left: 14px;">
                    </div>
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Stay Badge</label>
                        <select name="stay_badge" class="adm-input" style="padding-left: 14px;">
                            <option value="CANOPY TREEHOUSE">CANOPY TREEHOUSE</option>
                            <option value="EARTHEN MUDHOUSE">EARTHEN MUDHOUSE</option>
                            <option value="SOLO RETREAT">SOLO RETREAT</option>
                            <option value="ORCHARD HARVEST STAY">ORCHARD HARVEST STAY</option>
                            <option value="WELLNESS SANCTUARY">WELLNESS SANCTUARY</option>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Star Rating</label>
                        <select name="stars" class="adm-input" style="padding-left: 14px;">
                            <option value="5" selected>★★★★★ (5 Stars)</option>
                            <option value="4">★★★★☆ (4 Stars)</option>
                        </select>
                    </div>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Guest Quote & Impression *</label>
                    <textarea name="quote" class="adm-input" rows="4" placeholder="Enter guest feedback, feelings, or memories..." required style="padding-left: 14px; resize: vertical;"></textarea>
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Display Order</label>
                        <input type="number" name="display_order" class="adm-input" value="10" style="padding-left: 14px;">
                    </div>
                    <div class="adm-form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 24px;">
                        <input type="checkbox" name="is_active" id="add-t-active" value="1" checked style="width: 18px; height: 18px; accent-color: var(--adm-gold);">
                        <label for="add-t-active" style="cursor: pointer; color: #FFFFFF; font-weight: 600;">Display in Public Carousel</label>
                    </div>
                </div>
            </div>

            <div class="adm-modal-footer">
                <button type="button" class="adm-btn-action outline" data-close-modal="modal-add-testimonial">Cancel</button>
                <button type="submit" class="adm-btn-action gold">Publish Story</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Edit Testimonial -->
<div class="adm-modal-backdrop" id="modal-edit-testimonial">
    <div class="adm-modal-content">
        <div class="adm-modal-header">
            <h3 class="adm-modal-title"><i class="fa-solid fa-pen" style="color: var(--adm-gold); margin-right: 8px;"></i> Edit Guest Story</h3>
            <button type="button" class="adm-modal-close" data-close-modal="modal-edit-testimonial">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="update_testimonial">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="testimonial_id" id="edit-t-id">

            <div class="adm-modal-body">
                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Guest Full Name *</label>
                        <input type="text" name="guest_name" id="edit-t-name" class="adm-input" required style="padding-left: 14px;">
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Location & Stay Date</label>
                        <input type="text" name="guest_location" id="edit-t-location" class="adm-input" style="padding-left: 14px;">
                    </div>
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Stay Badge</label>
                        <select name="stay_badge" id="edit-t-badge" class="adm-input" style="padding-left: 14px;">
                            <option value="CANOPY TREEHOUSE">CANOPY TREEHOUSE</option>
                            <option value="EARTHEN MUDHOUSE">EARTHEN MUDHOUSE</option>
                            <option value="SOLO RETREAT">SOLO RETREAT</option>
                            <option value="ORCHARD HARVEST STAY">ORCHARD HARVEST STAY</option>
                            <option value="WELLNESS SANCTUARY">WELLNESS SANCTUARY</option>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label class="adm-label">Star Rating</label>
                        <select name="stars" id="edit-t-stars" class="adm-input" style="padding-left: 14px;">
                            <option value="5">★★★★★ (5 Stars)</option>
                            <option value="4">★★★★☆ (4 Stars)</option>
                        </select>
                    </div>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Guest Quote & Impression *</label>
                    <textarea name="quote" id="edit-t-quote" class="adm-input" rows="4" required style="padding-left: 14px; resize: vertical;"></textarea>
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label class="adm-label">Display Order</label>
                        <input type="number" name="display_order" id="edit-t-order" class="adm-input" style="padding-left: 14px;">
                    </div>
                    <div class="adm-form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 24px;">
                        <input type="checkbox" name="is_active" id="edit-t-active" value="1" style="width: 18px; height: 18px; accent-color: var(--adm-gold);">
                        <label for="edit-t-active" style="cursor: pointer; color: #FFFFFF; font-weight: 600;">Display in Public Carousel</label>
                    </div>
                </div>
            </div>

            <div class="adm-modal-footer">
                <button type="button" class="adm-btn-action outline" data-close-modal="modal-edit-testimonial">Cancel</button>
                <button type="submit" class="adm-btn-action gold">Update Story</button>
            </div>
        </form>
    </div>
</div>

<script>
function editTestimonial(t) {
    document.getElementById('edit-t-id').value = t.id;
    document.getElementById('edit-t-name').value = t.guest_name;
    document.getElementById('edit-t-location').value = t.guest_location || '';
    document.getElementById('edit-t-badge').value = t.stay_badge;
    document.getElementById('edit-t-stars').value = t.stars;
    document.getElementById('edit-t-quote').value = t.quote;
    document.getElementById('edit-t-order').value = t.display_order;
    document.getElementById('edit-t-active').checked = (t.is_active == 1);
    openAdmModal('modal-edit-testimonial');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
