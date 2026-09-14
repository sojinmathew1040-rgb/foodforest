<?php
// =========================================================================
// Food Forest Sanctuary — Guest Inquiries & Concierge Messages
// =========================================================================
$page_title = 'Guest Inquiries';
$page_subtitle = 'Messages, private retreat requests & concierge conversations';
require_once __DIR__ . '/includes/header.php';

$pdo = get_db();
$alert_message = '';
$alert_type = 'success';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $alert_message = 'Security validation failed.';
        $alert_type = 'error';
    } else {
        $action = $_POST['action'] ?? '';
        $inq_id = (int)($_POST['inquiry_id'] ?? 0);

        if ($action === 'mark_status') {
            $new_status = $_POST['status'];
            if (in_array($new_status, ['unread', 'read', 'replied'])) {
                $stmt = $pdo->prepare("UPDATE inquiries SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $inq_id]);
                $alert_message = 'Inquiry status updated.';
            }
        }

        if ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM inquiries WHERE id = ?");
            $stmt->execute([$inq_id]);
            $alert_message = 'Inquiry message removed.';
        }
    }
}

$inquiries = $pdo->query("SELECT * FROM inquiries ORDER BY created_at DESC")->fetchAll();
?>

<?php if (!empty($alert_message)): ?>
    <div class="adm-alert adm-alert-<?php echo $alert_type; ?>" style="margin-bottom: 20px;">
        <i class="fa-solid <?php echo $alert_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
        <span><?php echo e($alert_message); ?></span>
    </div>
<?php endif; ?>

<div class="adm-table-card">
    <div class="adm-table-header">
        <div>
            <h2 class="adm-table-title">Prospective Guest Inquiries (<?php echo count($inquiries); ?>)</h2>
            <p class="adm-table-subtitle">Direct messages submitted through the website</p>
        </div>
    </div>

    <div class="adm-table-responsive">
        <table class="adm-data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Sender Name</th>
                    <th>Contact</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($inquiries)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: var(--adm-text-muted);">
                            No inquiries recorded.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($inquiries as $inq): ?>
                        <tr>
                            <td><?php echo date('d M Y, h:i A', strtotime($inq['created_at'])); ?></td>
                            <td style="font-weight: 600; color: var(--adm-text-primary);"><?php echo e($inq['name']); ?></td>
                            <td>
                                <div><?php echo e($inq['email']); ?></div>
                                <?php if (!empty($inq['phone'])): ?>
                                    <div style="font-size: 11.5px; color: var(--adm-text-muted);"><?php echo e($inq['phone']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo e($inq['subject']); ?>">
                                <?php echo e($inq['subject']); ?>
                            </td>
                            <td>
                                <?php if ($inq['status'] === 'unread'): ?>
                                    <span class="adm-badge pending"><i class="fa-solid fa-envelope"></i> Unread</span>
                                <?php elseif ($inq['status'] === 'replied'): ?>
                                    <span class="adm-badge confirmed"><i class="fa-solid fa-reply"></i> Replied</span>
                                <?php else: ?>
                                    <span class="adm-badge completed">Read</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="adm-actions-cell">
                                    <button type="button" class="adm-btn-icon" title="Read Message"
                                        onclick='viewInquiry(<?php echo json_encode($inq); ?>)'>
                                        <i class="fa-solid fa-envelope-open"></i>
                                    </button>

                                    <?php if (!empty($inq['phone'])): 
                                        $phone_clean = preg_replace('/\D/', '', $inq['phone']);
                                        if (strlen($phone_clean) === 10) $phone_clean = '91' . $phone_clean;
                                    ?>
                                        <a href="https://wa.me/<?php echo $phone_clean; ?>?text=<?php echo urlencode('Hello ' . $inq['name'] . ', thank you for contacting Food Forest Sanctuary Kanthalloor regarding your enquiry.'); ?>" target="_blank" class="adm-btn-icon whatsapp" title="Reply via WhatsApp">
                                            <i class="fa-brands fa-whatsapp"></i>
                                        </a>
                                    <?php endif; ?>

                                    <a href="mailto:<?php echo urlencode($inq['email']); ?>?subject=<?php echo urlencode('Re: ' . $inq['subject'] . ' — Food Forest Sanctuary'); ?>" class="adm-btn-icon" title="Reply via Email">
                                        <i class="fa-solid fa-reply"></i>
                                    </a>

                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this inquiry?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                        <input type="hidden" name="inquiry_id" value="<?php echo $inq['id']; ?>">
                                        <button type="submit" class="adm-btn-icon danger" title="Delete Inquiry">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: View Inquiry -->
<div class="adm-modal-backdrop" id="modal-view-inquiry">
    <div class="adm-modal-content">
        <div class="adm-modal-header">
            <div>
                <h3 class="adm-modal-title" id="inq-modal-subject">Inquiry</h3>
                <span style="font-size: 12px; color: var(--adm-gold);" id="inq-modal-sender">From Sender</span>
            </div>
            <button type="button" class="adm-modal-close" data-close-modal="modal-view-inquiry">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="mark_status">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="inquiry_id" id="inq-modal-id">

            <div class="adm-modal-body">
                <div style="background: var(--adm-bg-main); border: var(--adm-border-subtle); border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 13px;">
                        <span style="color: var(--adm-text-muted);">Email: <strong style="color: var(--adm-text-primary);" id="inq-modal-email">-</strong></span>
                        <span style="color: var(--adm-text-muted);">Phone: <strong style="color: var(--adm-text-primary);" id="inq-modal-phone">-</strong></span>
                    </div>
                    <hr style="border: none; border-top: var(--adm-border-subtle); margin: 12px 0;">
                    <p id="inq-modal-message" style="color: var(--adm-text-primary); font-size: 14px; line-height: 1.7; white-space: pre-line;"></p>
                </div>

                <div class="adm-form-group">
                    <label class="adm-label">Update Message Status</label>
                    <select name="status" id="inq-modal-status" class="adm-input" style="padding-left: 14px;">
                        <option value="unread">Unread</option>
                        <option value="read">Read</option>
                        <option value="replied">Replied</option>
                    </select>
                </div>
            </div>

            <div class="adm-modal-footer">
                <button type="button" class="adm-btn-action outline" data-close-modal="modal-view-inquiry">Close</button>
                <button type="submit" class="adm-btn-action gold">Update Status</button>
            </div>
        </form>
    </div>
</div>

<script>
function viewInquiry(inq) {
    document.getElementById('inq-modal-id').value = inq.id;
    document.getElementById('inq-modal-subject').innerText = inq.subject;
    document.getElementById('inq-modal-sender').innerText = inq.name + ' (' + inq.created_at + ')';
    document.getElementById('inq-modal-email').innerText = inq.email;
    document.getElementById('inq-modal-phone').innerText = inq.phone || 'N/A';
    document.getElementById('inq-modal-message').innerText = inq.message;
    document.getElementById('inq-modal-status').value = inq.status;
    openAdmModal('modal-view-inquiry');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
