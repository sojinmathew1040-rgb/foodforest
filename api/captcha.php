<?php
// =========================================================================
// Food Forest Sanctuary — Luxury Cryptographic Captcha Engine
// Endpoint: GET /api/captcha.php?type=guest|admin&v=timestamp
// =========================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$type = preg_replace('/[^a-z0-9_]/', '', strtolower($_GET['type'] ?? 'guest'));
if (empty($type)) $type = 'guest';

// Generate 5-character distinct code
$charset = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
$length = 5;
$code = '';
$charset_len = strlen($charset);
for ($i = 0; $i < $length; $i++) {
    $code .= $charset[random_int(0, $charset_len - 1)];
}

$_SESSION['captcha_' . $type] = $code;

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$width = 170;
$height = 54;

// Build SVG with luxury dark green & gold aesthetic
$chars_svg = '';
$x_offset = 18;
$colors = ['#C5A059', '#E5C378', '#D4AF37', '#F5D77F', '#A3E635'];

for ($i = 0; $i < $length; $i++) {
    $char = $code[$i];
    $rot = random_int(-18, 18);
    $y = random_int(34, 40);
    $color = $colors[$i % count($colors)];
    $chars_svg .= sprintf(
        '<text x="%d" y="%d" transform="rotate(%d %d %d)" fill="%s" font-family="Cinzel, Cormorant Garamond, serif" font-weight="700" font-size="28" letter-spacing="4">%s</text>',
        $x_offset, $y, $rot, $x_offset, $y, $color, $char
    );
    $x_offset += 28;
}

// Security distortion curves
$lines_svg = '';
for ($l = 0; $l < 3; $l++) {
    $x1 = random_int(5, 30);
    $y1 = random_int(10, 45);
    $cx = random_int(50, 110);
    $cy = random_int(5, 50);
    $x2 = random_int(130, 165);
    $y2 = random_int(10, 45);
    $lines_svg .= sprintf(
        '<path d="M %d %d Q %d %d %d %d" stroke="rgba(197, 160, 89, 0.35)" stroke-width="1.6" fill="none" />',
        $x1, $y1, $cx, $cy, $x2, $y2
    );
}

// Noise dots
$dots_svg = '';
for ($d = 0; $d < 30; $d++) {
    $dx = random_int(5, $width - 5);
    $dy = random_int(5, $height - 5);
    $dr = random_int(1, 2);
    $dots_svg .= sprintf('<circle cx="%d" cy="%d" r="%d" fill="rgba(255,255,255,0.18)" />', $dx, $dy, $dr);
}

echo <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}" style="background: #0d1a12; border-radius: 6px; border: 1px solid rgba(197,160,89,0.3); user-select: none;">
    <rect width="100%" height="100%" fill="#0d1a12" />
    <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
        <path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255, 255, 255, 0.04)" stroke-width="0.8"/>
    </pattern>
    <rect width="100%" height="100%" fill="url(#grid)" />
    {$lines_svg}
    {$dots_svg}
    {$chars_svg}
</svg>
SVG;
exit;
