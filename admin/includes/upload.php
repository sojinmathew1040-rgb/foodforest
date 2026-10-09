<?php
// =========================================================================
// Food Forest Sanctuary — Master Media & Image Upload Handler
// Handles secure file storage, validation, directory creation & formatting
// Includes Automated High-Performance Image Compression & Resizing Engine
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
 * Retrieve Image Optimization & Compression Settings from Database
 *
 * @return array
 */
function get_image_optimization_settings() {
    static $cached_settings = null;
    if ($cached_settings !== null) {
        return $cached_settings;
    }

    $defaults = [
        'enabled'       => true,
        'max_dimension' => 1920,
        'quality'       => 82,
        'convert_webp'  => false,
    ];

    try {
        if (function_exists('get_db')) {
            $pdo = get_db();
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'image_%'");
            $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            if (!empty($rows)) {
                if (isset($rows['image_auto_compress_enabled'])) {
                    $defaults['enabled'] = ($rows['image_auto_compress_enabled'] === '1' || $rows['image_auto_compress_enabled'] === 1 || $rows['image_auto_compress_enabled'] === true);
                }
                if (isset($rows['image_max_dimension'])) {
                    $defaults['max_dimension'] = max(0, (int)$rows['image_max_dimension']);
                }
                if (isset($rows['image_jpeg_quality'])) {
                    $defaults['quality'] = max(50, min(100, (int)$rows['image_jpeg_quality']));
                }
                if (isset($rows['image_convert_webp'])) {
                    $defaults['convert_webp'] = ($rows['image_convert_webp'] === '1' || $rows['image_convert_webp'] === 1);
                }
            }
        }
    } catch (Exception $e) {
        // Fallback to defaults
    }

    $cached_settings = $defaults;
    return $cached_settings;
}

/**
 * Automatically compress and resize an image file using PHP GD
 * Handles EXIF orientation, transparency preservation, high-quality resampling, and WebP conversion
 *
 * @param string $filepath Full path to the image on disk
 * @param array $options Overrides for [max_dimension, quality, convert_webp, auto_compress]
 * @return array ['success' => bool, 'path' => string, 'filename' => string, 'original_size' => int, 'new_size' => int, 'savings_bytes' => int, 'savings_pct' => float, 'original_dims' => string, 'new_dims' => string]
 */
function compress_and_resize_image_file($filepath, $options = []) {
    if (!file_exists($filepath) || !is_readable($filepath)) {
        return ['success' => false, 'error' => 'Source image file not found or not readable.'];
    }

    if (!extension_loaded('gd')) {
        return ['success' => false, 'error' => 'PHP GD extension not installed on server.'];
    }

    @ini_set('memory_limit', '512M');

    $global_settings = get_image_optimization_settings();

    // Check if auto-compression should run
    $should_compress = $options['auto_compress'] ?? ($options['enabled'] ?? $global_settings['enabled']);
    if (!$should_compress) {
        $filesize = @filesize($filepath) ?: 0;
        return [
            'success'       => true,
            'skipped'       => true,
            'path'          => $filepath,
            'original_size' => $filesize,
            'new_size'      => $filesize,
            'savings_pct'   => 0
        ];
    }

    $max_dimension = isset($options['max_dimension']) ? (int)$options['max_dimension'] : $global_settings['max_dimension'];
    $quality       = isset($options['quality']) ? (int)$options['quality'] : $global_settings['quality'];
    $convert_webp  = isset($options['convert_webp']) ? (bool)$options['convert_webp'] : $global_settings['convert_webp'];

    $orig_size = @filesize($filepath) ?: 0;
    $info = @getimagesize($filepath);
    if (!$info) {
        return ['success' => false, 'error' => 'Could not inspect image dimensions or format.'];
    }

    $orig_w = $info[0];
    $orig_h = $info[1];
    $mime   = $info['mime'] ?? '';
    $ext    = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

    // Vector / animation safety: Skip SVG or animated GIF
    if ($ext === 'svg' || $mime === 'image/svg+xml') {
        return ['success' => true, 'skipped' => true, 'path' => $filepath, 'reason' => 'SVG is vector-based.'];
    }

    // Load GD image resource
    $src_img = null;
    switch ($mime) {
        case 'image/jpeg':
        case 'image/pjpeg':
            $src_img = @imagecreatefromjpeg($filepath);
            // Handle EXIF orientation rotation (iPhone / Android mobile photos)
            if ($src_img && function_exists('exif_read_data')) {
                $exif = @exif_read_data($filepath);
                if (!empty($exif['Orientation'])) {
                    switch ($exif['Orientation']) {
                        case 3:
                            $src_img = imagerotate($src_img, 180, 0);
                            break;
                        case 6:
                            $src_img = imagerotate($src_img, -90, 0);
                            // Swapped dimensions after rotation
                            $tmp = $orig_w; $orig_w = $orig_h; $orig_h = $tmp;
                            break;
                        case 8:
                            $src_img = imagerotate($src_img, 90, 0);
                            $tmp = $orig_w; $orig_w = $orig_h; $orig_h = $tmp;
                            break;
                    }
                }
            }
            break;

        case 'image/png':
        case 'image/x-png':
            $src_img = @imagecreatefrompng($filepath);
            break;

        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $src_img = @imagecreatefromwebp($filepath);
            }
            break;

        case 'image/gif':
            // Check for animated GIF (only keep first frame or skip)
            $is_animated = false;
            $fh = @fopen($filepath, 'rb');
            if ($fh) {
                $count = 0;
                while (!feof($fh) && $count < 2) {
                    $chunk = fread($fh, 1024 * 100);
                    $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $chunk);
                }
                fclose($fh);
                if ($count > 1) $is_animated = true;
            }
            if ($is_animated) {
                return ['success' => true, 'skipped' => true, 'path' => $filepath, 'reason' => 'Preserved animated GIF.'];
            }
            $src_img = @imagecreatefromgif($filepath);
            break;

        case 'image/avif':
            if (function_exists('imagecreatefromavif')) {
                $src_img = @imagecreatefromavif($filepath);
            }
            break;

        default:
            return ['success' => false, 'error' => "Unsupported MIME format for compression ($mime)."];
    }

    if (!$src_img) {
        return ['success' => false, 'error' => 'Failed to initialize GD image resource from file.'];
    }

    // Refresh actual width & height from loaded image
    $curr_w = imagesx($src_img);
    $curr_h = imagesy($src_img);

    // Calculate proportional target dimensions
    $new_w = $curr_w;
    $new_h = $curr_h;
    $is_resized = false;

    if ($max_dimension > 0 && ($curr_w > $max_dimension || $curr_h > $max_dimension)) {
        if ($curr_w >= $curr_h) {
            $new_w = $max_dimension;
            $new_h = (int)round(($curr_h / $curr_w) * $max_dimension);
        } else {
            $new_h = $max_dimension;
            $new_w = (int)round(($curr_w / $curr_h) * $max_dimension);
        }
        $is_resized = true;
    }

    // Resample if resized, or create truecolor copy to strip bloated metadata
    $dest_canvas = imagecreatetruecolor($new_w, $new_h);

    // Handle alpha transparency for PNG & WebP
    if ($mime === 'image/png' || $mime === 'image/webp' || $convert_webp) {
        imagealphablending($dest_canvas, false);
        imagesavealpha($dest_canvas, true);
        $transparent = imagecolorallocatealpha($dest_canvas, 255, 255, 255, 127);
        imagefilledrectangle($dest_canvas, 0, 0, $new_w, $new_h, $transparent);
    }

    imagecopyresampled($dest_canvas, $src_img, 0, 0, 0, 0, $new_w, $new_h, $curr_w, $curr_h);
    imagedestroy($src_img);

    // Determine target output path and format
    $final_path = $filepath;
    $final_ext  = $ext;
    $dest_dir   = dirname($filepath);
    $filename_no_ext = pathinfo($filepath, PATHINFO_FILENAME);

    if ($convert_webp && function_exists('imagewebp') && $ext !== 'webp') {
        $final_path = $dest_dir . DIRECTORY_SEPARATOR . $filename_no_ext . '.webp';
        $final_ext  = 'webp';
    }

    // Save with optimal compression
    $saved = false;
    if ($final_ext === 'webp' && function_exists('imagewebp')) {
        $saved = imagewebp($dest_canvas, $final_path, $quality);
        if ($saved && $final_path !== $filepath && file_exists($filepath)) {
            @unlink($filepath); // Remove original uncompressed file if converted
        }
    } elseif ($final_ext === 'png') {
        // PNG compression level (0 to 9, where 9 is max compression)
        $png_compression = min(9, max(1, (int)round(9 - ($quality / 100 * 3))));
        $saved = imagepng($dest_canvas, $final_path, $png_compression);
    } else {
        // JPEG / Default
        $saved = imagejpeg($dest_canvas, $final_path, $quality);
    }

    imagedestroy($dest_canvas);

    if (!$saved || !file_exists($final_path)) {
        return ['success' => false, 'error' => 'Failed to write compressed image to disk.'];
    }

    @clearstatcache(true, $final_path);
    $new_size = @filesize($final_path) ?: $orig_size;
    $saved_bytes = max(0, $orig_size - $new_size);
    $savings_pct = ($orig_size > 0) ? round(($saved_bytes / $orig_size) * 100, 1) : 0;

    return [
        'success'        => true,
        'path'           => $final_path,
        'filename'       => basename($final_path),
        'original_size'  => $orig_size,
        'new_size'       => $new_size,
        'savings_bytes'  => $saved_bytes,
        'savings_pct'    => $savings_pct,
        'original_dims'  => "{$curr_w}x{$curr_h}",
        'new_dims'       => "{$new_w}x{$new_h}",
        'is_resized'     => $is_resized,
        'converted_webp' => ($final_ext === 'webp' && $ext !== 'webp')
    ];
}

/**
 * Handle a single file upload from $_FILES with automatic compression & resizing
 * 
 * @param array $file Single file subarray from $_FILES (e.g. $_FILES['hero_bg_image_file'])
 * @param string $prefix Filename prefix for organization (e.g. 'hero', 'room', 'gallery')
 * @param array $options Optimization options override [auto_compress => bool, max_dimension => int, quality => int, convert_webp => bool]
 * @return array ['success' => bool, 'path' => string, 'filename' => string, 'error' => string, 'no_file' => bool, 'optimization' => array]
 */
function handle_image_upload($file, $prefix = 'photo', $options = []) {
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

    // Size constraint: 25MB (compressed down afterwards)
    $max_size = 25 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        return ['success' => false, 'no_file' => false, 'error' => 'File size exceeds maximum 25MB limit.'];
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

    // Check if auto_compress was explicitly passed via POST form
    if (!isset($options['auto_compress'])) {
        if (isset($_POST['auto_compress'])) {
            $options['auto_compress'] = ($_POST['auto_compress'] === '1' || $_POST['auto_compress'] === 'on' || $_POST['auto_compress'] === true);
        }
    }

    // Generate clean, collision-free filename
    $clean_prefix = preg_replace('/[^a-zA-Z0-9_-]/', '', $prefix);
    if (empty($clean_prefix)) $clean_prefix = 'photo';
    $unique_suffix = date('Ymd_His') . '_' . substr(bin2hex(random_bytes(4)), 0, 8);
    $filename = $clean_prefix . '_' . $unique_suffix . '.' . $ext;
    $target_dest = SANCTUARY_UPLOAD_DIR . $filename;

    if (!@move_uploaded_file($tmp_name, $target_dest)) {
        if (!@copy($tmp_name, $target_dest)) {
            return ['success' => false, 'no_file' => false, 'error' => 'Failed to move uploaded file to sanctuary media repository. Check server permissions.'];
        }
    }

    // Run Automated High-Performance Compression & Resizing
    $opt_result = compress_and_resize_image_file($target_dest, $options);
    if (!empty($opt_result['success']) && !empty($opt_result['filename'])) {
        $filename = $opt_result['filename'];
    }

    // Return relative path for web and DB storage: e.g. "assets/uploads/photo_...jpg"
    $relative_path = SANCTUARY_UPLOAD_REL . $filename;
    return [
        'success'      => true,
        'no_file'      => false,
        'path'         => $relative_path,
        'filename'     => $filename,
        'optimization' => $opt_result
    ];
}

/**
 * Handle an indexed file upload from an array of files (e.g. $_FILES['exp_image_file']['name'][$idx])
 * 
 * @param array $files_array The multi-file $_FILES entry
 * @param int|string $index The numeric index of the item
 * @param string $prefix Filename prefix
 * @param array $options Optimization options override
 * @return array Standard result array from handle_image_upload
 */
function handle_indexed_image_upload($files_array, $index, $prefix = 'photo', $options = []) {
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

    return handle_image_upload($single_file, $prefix, $options);
}

/**
 * Handle multiple file upload from a multi-file input (e.g. <input type="file" name="..." multiple>)
 * 
 * @param array $files_entry $_FILES['field_name']
 * @param string $prefix Filename prefix
 * @param array $options Optimization options override
 * @return array Array of successfully uploaded relative paths
 */
function handle_multi_image_upload($files_entry, $prefix = 'sanctuary', $options = []) {
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
            $res = handle_image_upload($single, $prefix, $options);
            if ($res['success'] && !empty($res['path'])) {
                $uploaded_paths[] = $res['path'];
            }
        }
    } else {
        $res = handle_image_upload($files_entry, $prefix, $options);
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
