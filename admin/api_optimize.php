<?php
// =========================================================================
// Food Forest Sanctuary — Image Optimization & Sandbox API
// Endpoints for real-time compression testing and batch optimization
// =========================================================================

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/auth.php';
require_admin_auth();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/upload.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? 'get_settings');

// 1. Get Current Settings
if ($action === 'get_settings') {
    echo json_encode([
        'success'  => true,
        'settings' => get_image_optimization_settings(),
        'gd_info'  => extension_loaded('gd') ? gd_info() : null
    ]);
    exit;
}

// 2. Test Compression Sandbox
if ($action === 'test_compress') {
    if (empty($_FILES['test_file']) || $_FILES['test_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Please select an image file to test.']);
        exit;
    }

    $file = $_FILES['test_file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: JPG, PNG, WEBP, GIF, AVIF.']);
        exit;
    }

    $scratch_dir = __DIR__ . '/../scratch';
    if (!is_dir($scratch_dir)) {
        @mkdir($scratch_dir, 0777, true);
    }

    $temp_filename = 'test_opt_' . time() . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;
    $temp_path = $scratch_dir . '/' . $temp_filename;

    if (!@move_uploaded_file($file['tmp_name'], $temp_path)) {
        echo json_encode(['success' => false, 'error' => 'Could not save temporary test file.']);
        exit;
    }

    $max_dim = isset($_POST['max_dimension']) ? (int)$_POST['max_dimension'] : null;
    $quality = isset($_POST['quality']) ? (int)$_POST['quality'] : null;
    $convert_webp = isset($_POST['convert_webp']) ? ($_POST['convert_webp'] === '1' || $_POST['convert_webp'] === 'true') : null;

    $options = [
        'auto_compress' => true,
        'max_dimension' => $max_dim,
        'quality'       => $quality,
        'convert_webp'  => $convert_webp
    ];

    $res = compress_and_resize_image_file($temp_path, $options);

    if ($res['success']) {
        $final_path = $res['path'];
        $data_uri = '';
        if (file_exists($final_path) && filesize($final_path) < 3 * 1024 * 1024) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $final_path);
            finfo_close($finfo);
            $data_uri = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($final_path));
        }

        // Clean up temporary file
        @unlink($final_path);

        echo json_encode([
            'success'        => true,
            'original_size'  => $res['original_size'],
            'new_size'       => $res['new_size'],
            'savings_bytes'  => $res['savings_bytes'],
            'savings_pct'    => $res['savings_pct'],
            'original_dims'  => $res['original_dims'],
            'new_dims'       => $res['new_dims'],
            'is_resized'     => $res['is_resized'],
            'converted_webp' => $res['converted_webp'],
            'preview_data'   => $data_uri
        ]);
        exit;
    } else {
        @unlink($temp_path);
        echo json_encode(['success' => false, 'error' => $res['error'] ?? 'Optimization failed.']);
        exit;
    }
}

// 3. Batch Optimize Existing Uploads
if ($action === 'batch_optimize') {
    ensure_upload_dir_exists();
    $dir = SANCTUARY_UPLOAD_DIR;
    $files = scandir($dir);

    $processed = 0;
    $optimized = 0;
    $total_saved_bytes = 0;
    $details = [];

    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $settings = get_image_optimization_settings();

    foreach ($files as $f) {
        if ($f === '.' || $f === '..' || $f === 'index.html' || $f === '.htaccess') continue;
        $path = $dir . $f;
        if (!is_file($path)) continue;

        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) continue;

        $processed++;
        $before_size = filesize($path);

        // Only compress if over 250KB or dimensions exceed max_dimension
        $info = @getimagesize($path);
        $needs_opt = false;
        if ($before_size > 250 * 1024) {
            $needs_opt = true;
        } elseif ($info && ($info[0] > $settings['max_dimension'] || $info[1] > $settings['max_dimension'])) {
            $needs_opt = true;
        }

        if ($needs_opt) {
            $res = compress_and_resize_image_file($path, ['auto_compress' => true]);
            if ($res['success'] && !empty($res['savings_bytes']) && $res['savings_bytes'] > 0) {
                $optimized++;
                $total_saved_bytes += $res['savings_bytes'];
                $details[] = [
                    'file'       => $f,
                    'before'     => $res['original_size'],
                    'after'      => $res['new_size'],
                    'saved_pct'  => $res['savings_pct'],
                    'new_dims'   => $res['new_dims']
                ];
            }
        }
    }

    echo json_encode([
        'success'           => true,
        'files_scanned'     => $processed,
        'files_optimized'   => $optimized,
        'total_saved_bytes' => $total_saved_bytes,
        'total_saved_mb'    => round($total_saved_bytes / (1024 * 1024), 2),
        'details'           => array_slice($details, 0, 20)
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action.']);
exit;
