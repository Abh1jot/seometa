<?php
/**
 * SEO & Meta — Dynamic Server OG Image Generator
 * 
 * This file is publicly accessible (no auth) so that social platform
 * crawlers (Discord, Twitter, WhatsApp, Facebook) can fetch the image.
 * 
 * Usage: /extensions/seometa/og.php?id=SERVER_UUID
 */

// Read .env for database credentials
$envPath = __DIR__ . '/../../../.env';
if (!file_exists($envPath)) {
    http_response_code(500);
    exit('Config not found');
}

$env = [];
foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#')) continue;
    if (!str_contains($line, '=')) continue;
    [$key, $val] = explode('=', $line, 2);
    $env[trim($key)] = trim($val, " \t\n\r\0\x0B\"'");
}

// Validate request
$serverUuid = $_GET['id'] ?? '';
if (empty($serverUuid) || !preg_match('/^[a-f0-9\-]+$/i', $serverUuid)) {
    http_response_code(400);
    exit('Invalid server ID');
}

// Connect to database
try {
    $host = $env['DB_HOST'] ?? '127.0.0.1';
    $port = $env['DB_PORT'] ?? '3306';
    $db   = $env['DB_DATABASE'] ?? 'panel';
    $user = $env['DB_USERNAME'] ?? 'pterodactyl';
    $pass = $env['DB_PASSWORD'] ?? '';

    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database error');
}

// Check if server cards are enabled
$stmt = $pdo->prepare("SELECT `value` FROM `blueprint_seometa_settings` WHERE `key` = ?");
$stmt->execute(['enable_server_cards']);
$enabled = $stmt->fetchColumn();
if ($enabled === '0') {
    http_response_code(404);
    exit('Server cards disabled');
}

// Get server name
$stmt = $pdo->prepare("SELECT `name` FROM `servers` WHERE `uuid` = ? OR `uuidShort` = ? LIMIT 1");
$stmt->execute([$serverUuid, $serverUuid]);
$serverName = $stmt->fetchColumn();
if (!$serverName) $serverName = 'Unknown Server';

// Get settings
$settings = [];
$stmt = $pdo->query("SELECT `key`, `value` FROM `blueprint_seometa_settings`");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key']] = $row['value'];
}

$hostingName    = $settings['hosting_name'] ?? '';
$hostingLogo    = $settings['hosting_logo'] ?? '';
$themeColor     = $settings['theme_color'] ?? '#1a1a2e';

// ─── Generate Image ───────────────────────────────────────────────

$width  = 1200;
$height = 630;

$img = imagecreatetruecolor($width, $height);
imagesavealpha($img, true);

// Parse theme color
$r = hexdec(substr($themeColor, 1, 2));
$g = hexdec(substr($themeColor, 3, 2));
$b = hexdec(substr($themeColor, 5, 2));

// Draw gradient background
for ($y = 0; $y < $height; $y++) {
    for ($x = 0; $x < $width; $x++) {
        $cx = $width / 2;
        $cy = $height / 2;
        $dist = sqrt(pow($x - $cx, 2) + pow($y - $cy, 2));
        $maxDist = sqrt(pow($cx, 2) + pow($cy, 2));
        $factor = max(0, 1 - ($dist / $maxDist));
        $factor = $factor * $factor;

        $pr = (int)($r * $factor * 0.4);
        $pg = (int)($g * $factor * 0.4);
        $pb = (int)($b * $factor * 0.4);

        $color = imagecolorallocate($img, min(255, $pr + 8), min(255, $pg + 8), min(255, $pb + 10));
        imagesetpixel($img, $x, $y, $color);
    }
}

// Subtle grid overlay
$gridColor = imagecolorallocatealpha($img, 255, 255, 255, 120);
for ($x = 0; $x < $width; $x += 40) imageline($img, $x, 0, $x, $height, $gridColor);
for ($y = 0; $y < $height; $y += 40) imageline($img, 0, $y, $width, $y, $gridColor);

$white     = imagecolorallocate($img, 255, 255, 255);
$lightGray = imagecolorallocate($img, 180, 180, 190);
$dimGray   = imagecolorallocate($img, 120, 120, 135);

// Find fonts
$fontBold = null;
$fontRegular = null;
$boldPaths = [
    '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
    '/usr/share/fonts/TTF/DejaVuSans-Bold.ttf',
    '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
    '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
];
$regularPaths = [
    '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
    '/usr/share/fonts/TTF/DejaVuSans.ttf',
    '/usr/share/fonts/dejavu/DejaVuSans.ttf',
    '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
];
foreach ($boldPaths as $fp) { if (file_exists($fp)) { $fontBold = $fp; break; } }
foreach ($regularPaths as $fp) { if (file_exists($fp)) { $fontRegular = $fp; break; } }

if ($fontBold) {
    // "GAME SERVER" label
    if ($fontRegular) {
        $labelSize = 14;
        $label = 'GAME SERVER';
        $lbbox = imagettfbbox($labelSize, 0, $fontRegular, $label);
        $lw = abs($lbbox[2] - $lbbox[0]);
        $lx = ($width - $lw) / 2;
        $ly = ($height / 2) - 50;
        imagettftext($img, $labelSize, 0, $lx, $ly, $dimGray, $fontRegular, $label);

        // Decorative line
        $lineColor = imagecolorallocatealpha($img, $r, $g, $b, 60);
        imageline($img, $lx, $ly + 10, $lx + $lw, $ly + 10, $lineColor);
    }

    // Server name — centered
    $fontSize = 42;
    $bbox = imagettfbbox($fontSize, 0, $fontBold, $serverName);
    $textWidth = abs($bbox[2] - $bbox[0]);
    while ($textWidth > ($width - 120) && $fontSize > 20) {
        $fontSize -= 2;
        $bbox = imagettfbbox($fontSize, 0, $fontBold, $serverName);
        $textWidth = abs($bbox[2] - $bbox[0]);
    }
    $textX = ($width - $textWidth) / 2;
    $textY = ($height / 2) + ($fontSize / 3);

    // Shadow + main text
    $shadow = imagecolorallocatealpha($img, 0, 0, 0, 50);
    imagettftext($img, $fontSize, 0, $textX + 2, $textY + 2, $shadow, $fontBold, $serverName);
    imagettftext($img, $fontSize, 0, $textX, $textY, $white, $fontBold, $serverName);

    // Hosting branding — bottom left
    if ($hostingName && $fontRegular) {
        $hostSize = 16;
        $hostY = $height - 30;
        $logoStartX = 30;
        $textStartX = $logoStartX;

        // Hosting logo
        if ($hostingLogo) {
            $logoFullPath = null;
            if (str_starts_with($hostingLogo, 'http')) {
                // Download remote logo to temp
                $tmpLogo = tempnam(sys_get_temp_dir(), 'logo');
                @file_put_contents($tmpLogo, @file_get_contents($hostingLogo));
                if (filesize($tmpLogo) > 0) $logoFullPath = $tmpLogo;
            } else {
                // Logo path is like /extensions/seometa/logo.png (relative to public/)
                // og.php is at /public/extensions/seometa/og.php
                // So go up to public/ and append the path
                $logoFullPath = __DIR__ . '/../..' . $hostingLogo;
                if (!file_exists($logoFullPath)) {
                    $logoFullPath = __DIR__ . '/../../..' . $hostingLogo;
                }
            }

            if ($logoFullPath && file_exists($logoFullPath)) {
                $logoInfo = @getimagesize($logoFullPath);
                if ($logoInfo) {
                    $logoSrc = null;
                    $logoSize = 36;
                    switch ($logoInfo[2]) {
                        case IMAGETYPE_PNG:  $logoSrc = @imagecreatefrompng($logoFullPath); break;
                        case IMAGETYPE_JPEG: $logoSrc = @imagecreatefromjpeg($logoFullPath); break;
                        case IMAGETYPE_GIF:  $logoSrc = @imagecreatefromgif($logoFullPath); break;
                        case IMAGETYPE_WEBP: $logoSrc = @imagecreatefromwebp($logoFullPath); break;
                    }
                    if ($logoSrc) {
                        imagecopyresampled($img, $logoSrc, $logoStartX, $hostY - $logoSize + 6, 0, 0, $logoSize, $logoSize, imagesx($logoSrc), imagesy($logoSrc));
                        imagedestroy($logoSrc);
                        $textStartX = $logoStartX + $logoSize + 10;
                    }
                }
            }
            if (isset($tmpLogo)) @unlink($tmpLogo);
        }

        imagettftext($img, $hostSize, 0, $textStartX, $hostY, $lightGray, $fontRegular, $hostingName);
    }
} else {
    // Fallback: built-in font
    $font = 5;
    $textWidth = imagefontwidth($font) * strlen($serverName);
    $textX = ($width - $textWidth) / 2;
    $textY = ($height / 2) - (imagefontheight($font) / 2);
    imagestring($img, $font, $textX, $textY, $serverName, $white);

    if ($hostingName) {
        imagestring($img, 3, 30, $height - 40, $hostingName, $lightGray);
    }
}

// Cache for 1 hour
header('Content-Type: image/png');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');

imagepng($img);
imagedestroy($img);
