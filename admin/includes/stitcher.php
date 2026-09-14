<?php
// =========================================================================
// Food Forest Sanctuary — Automated 3-Photo Panorama Stitching Engine
// Converts 3 Standard Mobile Photos (Left, Center, Right) into a 360°
// Equirectangular / Cylindrical Panoramic Interior View
// =========================================================================

/**
 * Load an image resource from file path, handling JPEG, PNG, WEBP, and GIF
 */
function load_image_resource($filepath) {
    if (!file_exists($filepath)) return null;

    $info = @getimagesize($filepath);
    if (!$info) return null;

    $mime = $info['mime'] ?? '';
    $img = null;

    switch ($mime) {
        case 'image/jpeg':
            $img = @imagecreatefromjpeg($filepath);
            // Handle EXIF orientation if available
            if ($img && function_exists('exif_read_data')) {
                $exif = @exif_read_data($filepath);
                if (!empty($exif['Orientation'])) {
                    switch ($exif['Orientation']) {
                        case 3:
                            $img = imagerotate($img, 180, 0);
                            break;
                        case 6:
                            $img = imagerotate($img, -90, 0);
                            break;
                        case 8:
                            $img = imagerotate($img, 90, 0);
                            break;
                    }
                }
            }
            break;
        case 'image/png':
            $img = @imagecreatefrompng($filepath);
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $img = @imagecreatefromwebp($filepath);
            }
            break;
        case 'image/gif':
            $img = @imagecreatefromgif($filepath);
            break;
    }

    return $img;
}

/**
 * Stitch 3 images (Left, Center, Right) into a 360° panoramic canvas
 *
 * @param array $file_left    $_FILES entry or array with 'tmp_name'
 * @param array $file_center  $_FILES entry or array with 'tmp_name'
 * @param array $file_right   $_FILES entry or array with 'tmp_name'
 * @return array              ['success' => bool, 'path' => string, 'message' => string]
 */
function stitch_three_photos_to_360($file_left, $file_center, $file_right) {
    if (!extension_loaded('gd')) {
        return ['success' => false, 'message' => 'PHP GD extension is required for photo stitching.'];
    }

    $tmp_left = $file_left['tmp_name'] ?? '';
    $tmp_center = $file_center['tmp_name'] ?? '';
    $tmp_right = $file_right['tmp_name'] ?? '';

    if (empty($tmp_left) || empty($tmp_center) || empty($tmp_right)) {
        return ['success' => false, 'message' => 'All three photos (Left, Center, Right) must be provided.'];
    }

    // Load source images
    $src_left = load_image_resource($tmp_left);
    $src_center = load_image_resource($tmp_center);
    $src_right = load_image_resource($tmp_right);

    if (!$src_left || !$src_center || !$src_right) {
        if ($src_left) imagedestroy($src_left);
        if ($src_center) imagedestroy($src_center);
        if ($src_right) imagedestroy($src_right);
        return ['success' => false, 'message' => 'One or more uploaded images could not be processed. Please use JPG, PNG, or WEBP.'];
    }

    // Standard working dimensions
    $target_h = 1024; // Standard height for spherical texture mapping

    $w_l = imagesx($src_left);
    $h_l = imagesy($src_left);
    $w_c = imagesx($src_center);
    $h_c = imagesy($src_center);
    $w_r = imagesx($src_right);
    $h_r = imagesy($src_right);

    // Calculate scaled widths
    $scale_w_l = (int)round(($w_l / $h_l) * $target_h);
    $scale_w_c = (int)round(($w_c / $h_c) * $target_h);
    $scale_w_r = (int)round(($w_r / $h_r) * $target_h);

    // Overlap blending width (15% of average width)
    $avg_w = ($scale_w_l + $scale_w_c + $scale_w_r) / 3;
    $overlap = (int)round($avg_w * 0.15);
    if ($overlap < 60) $overlap = 60;
    if ($overlap > 220) $overlap = 220;

    // Resample each image into a normalized height strip
    $scaled_l = imagecreatetruecolor($scale_w_l, $target_h);
    $scaled_c = imagecreatetruecolor($scale_w_c, $target_h);
    $scaled_r = imagecreatetruecolor($scale_w_r, $target_h);

    imagecopyresampled($scaled_l, $src_left, 0, 0, 0, 0, $scale_w_l, $target_h, $w_l, $h_l);
    imagecopyresampled($scaled_c, $src_center, 0, 0, 0, 0, $scale_w_c, $target_h, $w_c, $h_c);
    imagecopyresampled($scaled_r, $src_right, 0, 0, 0, 0, $scale_w_r, $target_h, $w_r, $h_r);

    // Free original large resources
    imagedestroy($src_left);
    imagedestroy($src_center);
    imagedestroy($src_right);

    // Total content width after overlapping Left, Center, Right
    $total_content_w = $scale_w_l + ($scale_w_c - $overlap) + ($scale_w_r - $overlap);

    // Equirectangular 360 canvas is typically 2:1 aspect ratio
    $pano_w = 2048;
    $pano_h = 1024;

    // Create intermediate panoramic strip
    $strip = imagecreatetruecolor($total_content_w, $target_h);

    // 1. Copy Left image
    imagecopy($strip, $scaled_l, 0, 0, 0, 0, $scale_w_l, $target_h);

    // 2. Blend Left -> Center in overlap zone
    $start_c_x = $scale_w_l - $overlap;
    for ($i = 0; $i < $overlap; $i++) {
        $pct = $i / (float)$overlap; // 0.0 at Left edge -> 1.0 at Center
        $pct_int = (int)round($pct * 100);
        $strip_x = $start_c_x + $i;
        imagecopymerge($strip, $scaled_c, $strip_x, 0, $i, 0, 1, $target_h, $pct_int);
    }
    // Copy remaining Center
    if ($scale_w_c > $overlap) {
        imagecopy($strip, $scaled_c, $start_c_x + $overlap, 0, $overlap, 0, $scale_w_c - $overlap, $target_h);
    }

    // 3. Blend Center -> Right in overlap zone
    $start_r_x = $start_c_x + $scale_w_c - $overlap;
    for ($i = 0; $i < $overlap; $i++) {
        $pct = $i / (float)$overlap; // 0.0 at Center edge -> 1.0 at Right
        $pct_int = (int)round($pct * 100);
        $strip_x = $start_r_x + $i;
        imagecopymerge($strip, $scaled_r, $strip_x, 0, $i, 0, 1, $target_h, $pct_int);
    }
    // Copy remaining Right
    if ($scale_w_r > $overlap) {
        imagecopy($strip, $scaled_r, $start_r_x + $overlap, 0, $overlap, 0, $scale_w_r - $overlap, $target_h);
    }

    // Free intermediate scaled images
    imagedestroy($scaled_l);
    imagedestroy($scaled_c);
    imagedestroy($scaled_r);

    // 4. Create final 2:1 Equirectangular Canvas
    $pano = imagecreatetruecolor($pano_w, $pano_h);

    // Resample the stitched strip into 2:1 equirectangular canvas
    imagecopyresampled($pano, $strip, 0, 0, 0, 0, $pano_w, $pano_h, $total_content_w, $target_h);
    imagedestroy($strip);

    // 5. Seamless circular wrap-around blend on outer vertical edges
    $edge_wrap = 40;
    for ($i = 0; $i < $edge_wrap; $i++) {
        $alpha = (int)round(($i / (float)$edge_wrap) * 50);
        imagecopymerge($pano, $pano, $pano_w - $edge_wrap + $i, 0, $i, 0, 1, $pano_h, 50 - $alpha);
        imagecopymerge($pano, $pano, $i, 0, $pano_w - $edge_wrap + $i, 0, 1, $pano_h, $alpha);
    }

    // 6. Save final stitched panorama to uploads directory
    $upload_dir = dirname(__DIR__, 2) . '/assets/images/uploads';
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0777, true);
    }

    $filename = 'pano_stitched_' . date('Ymd_His') . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.jpg';
    $dest_path = $upload_dir . '/' . $filename;
    $relative_path = 'assets/images/uploads/' . $filename;

    $saved = imagejpeg($pano, $dest_path, 92);
    imagedestroy($pano);

    if ($saved) {
        return [
            'success' => true,
            'path' => $relative_path,
            'filename' => $filename,
            'message' => '3-Angle Panorama successfully stitched and generated!'
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to save generated panoramic image to disk.'];
    }
}
?>
