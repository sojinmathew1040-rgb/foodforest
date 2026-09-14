<?php
// =========================================================================
// Food Forest Sanctuary — Master Media & Image Upload Handler
// Handles secure file storage, validation, directory creation & formatting
// =========================================================================

defined('SANCTUARY_ROOT') or define('SANCTUARY_ROOT', dirname(__DIR__, 2));
defined('SANCTUARY_UPLOAD_DIR') or define('SANCTUARY_UPLOAD_DIR', SANCTUARY_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);
defined('SANCTUARY_UPLOAD_REL') or define('SANCTUARY_UPLOAD_REL', 'assets/uploads/');

/**
 * Ensure the uploads directory exists and is protected against directory indexing
 */
function ensure_upload_dir_exists() {
    if (!is_dir(SANCTUARY_UPLOAD_DIR)) {
        @mkdir(SANCTUARY_UPLOAD_DIR, 0755, true);
    }
    $index_file = SANCTUARY_UPLOAD_DIR . 'index.html';
    if (!file_exists($index_file)) {
        @file_put_contents($index_file, '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Directory Access Forbidden</h1></body></html>');
    }
}

/**
 * Handle a single file upload from $_FILES
 * 
 * @param array $file Single file subarray from $_FILES (e.g. $_FILES['hero_bg_image_file'])
 * @param string $prefix Filename prefix for organization (e.g. 'hero', 'room', 'gallery')
 * @return array ['success' => bool, 'path' => string, 'error' => string, 'no_file' => bool]
 */
function handle_image_upload($file, $prefix = 'photo') {
    if (empty($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'no_file' => true, 'error' => 'No file uploaded'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE specified in the form.',
            UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
        ];
        $msg = $error_messages[$file['error']] ?? ('Upload failed with code: ' . $file['error']);
        return ['success' => false, 'no_file' => false, 'error' => $msg];
    }

    // Size constraint: 15MB
    $max_size = 15 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        return ['success' => false, 'no_file' => false, 'error' => 'File size exceeds maximum 15MB limit.'];
    }

    // Validate Extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'avif'];
    if (!in_array($ext, $allowed_extensions)) {
        return ['success' => false, 'no_file' => false, 'error' => 'Invalid file extension. Allowed: JPG, PNG, WEBP, GIF, SVG, AVIF.'];
    }

    // Validate MIME Type
    $tmp_name = $file['tmp_name'];
    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmp_name);
        finfo_close($finfo);
    } elseif (function_exists('mime_content_type')) {
        $mime = mime_content_type($tmp_name);
    }

    $allowed_mimes = [
        'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png',
        'image/webp', 'image/gif', 'image/svg+xml', 'image/avif',
        'image/x-ms-bmp', 'application/octet-stream' // fallback
    ];

    if (!empty($mime) && !in_array($mime, $allowed_mimes)) {
        return ['success' => false, 'no_file' => false, 'error' => 'Invalid file format (' . htmlspecialchars($mime) . '). Only image files allowed.'];
    }

    // If not SVG, verify image integrity with getimagesize
    if ($ext !== 'svg') {
        $img_info = @getimagesize($tmp_name);
        if ($img_info === false) {
            return ['success' => false, 'no_file' => false, 'error' => 'File is not a valid or readable image.'];
        }
    }

    ensure_upload_dir_exists();

    // Generate clean, collision-free filename
    $clean_prefix = preg_replace('/[^a-zA-Z0-9_-]/', '', $prefix);
    if (empty($clean_prefix)) $clean_prefix = 'photo';
    $unique_suffix = date('Ymd_His') . '_' . substr(bin2hex(random_bytes(4)), 0, 8);
    $filename = $clean_prefix . '_' . $unique_suffix . '.' . $ext;
    $target_dest = SANCTUARY_UPLOAD_DIR . $filename;

    if (!@move_uploaded_file($tmp_name, $target_dest)) {
        return ['success' => false, 'no_file' => false, 'error' => 'Failed to move uploaded file to sanctuary media repository. Check server permissions.'];
    }

    // Return relative path for web and DB storage: e.g. "assets/uploads/photo_...jpg"
    $relative_path = SANCTUARY_UPLOAD_REL . $filename;
    return [
        'success' => true,
        'no_file' => false,
        'path' => $relative_path,
        'filename' => $filename
    ];
}

/**
 * Handle an indexed file upload from an array of files (e.g. $_FILES['exp_image_file']['name'][$idx])
 * 
 * @param array $files_array The multi-file $_FILES entry
 * @param int|string $index The numeric index of the item
 * @param string $prefix Filename prefix
 * @return array Standard result array from handle_image_upload
 */
function handle_indexed_image_upload($files_array, $index, $prefix = 'photo') {
    if (empty($files_array) || !isset($files_array['name']) || !isset($files_array['name'][$index])) {
        return ['success' => false, 'no_file' => true, 'error' => 'No file selected for this index'];
    }

    $name = $files_array['name'][$index];
    if (empty($name)) {
        return ['success' => false, 'no_file' => true, 'error' => 'Empty filename'];
    }

    $single_file = [
        'name'     => $files_array['name'][$index] ?? '',
        'type'     => $files_array['type'][$index] ?? '',
        'tmp_name' => $files_array['tmp_name'][$index] ?? '',
        'error'    => $files_array['error'][$index] ?? UPLOAD_ERR_NO_FILE,
        'size'     => $files_array['size'][$index] ?? 0
    ];

    return handle_image_upload($single_file, $prefix);
}

/**
 * Handle multiple file upload from a multi-file input (e.g. <input type="file" name="..." multiple>)
 * 
 * @param array $files_entry $_FILES['field_name']
 * @param string $prefix Filename prefix
 * @return array Array of successfully uploaded relative paths
 */
function handle_multi_image_upload($files_entry, $prefix = 'sanctuary') {
    $uploaded_paths = [];
    if (empty($files_entry) || !isset($files_entry['name'])) {
        return $uploaded_paths;
    }
    
    if (is_array($files_entry['name'])) {
        $count = count($files_entry['name']);
        for ($i = 0; $i < $count; $i++) {
            if (empty($files_entry['name'][$i]) || ($files_entry['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $single = [
                'name'     => $files_entry['name'][$i],
                'type'     => $files_entry['type'][$i] ?? '',
                'tmp_name' => $files_entry['tmp_name'][$i] ?? '',
                'error'    => $files_entry['error'][$i] ?? UPLOAD_ERR_OK,
                'size'     => $files_entry['size'][$i] ?? 0
            ];
            $res = handle_image_upload($single, $prefix);
            if ($res['success'] && !empty($res['path'])) {
                $uploaded_paths[] = $res['path'];
            }
        }
    } else {
        $res = handle_image_upload($files_entry, $prefix);
        if ($res['success'] && !empty($res['path'])) {
            $uploaded_paths[] = $res['path'];
        }
    }
    return $uploaded_paths;
}

/**
 * Format an image source URL for display inside the admin panel
 * Accounts for relative paths, external URLs, and missing assets
 * 
 * @param string $path Image path stored in DB
 * @param string $default Fallback image path relative to site root
 * @return string Correct src attribute value for <img src="..."> in admin
 */
function admin_img_src($path, $default = 'assets/images/treehouse_exterior.png') {
    $trimmed = trim((string)$path);
    if (empty($trimmed)) {
        return '../' . ltrim($default, '/');
    }
    // If it's an absolute URL or data URI, return as-is
    if (preg_match('/^https?:\/\//i', $trimmed) || preg_match('/^data:/i', $trimmed)) {
        return $trimmed;
    }
    // If already has relative parent prefix
    if (strpos($trimmed, '../') === 0) {
        return $trimmed;
    }
    return '../' . ltrim($trimmed, '/');
}
