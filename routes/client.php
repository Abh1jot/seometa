<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Pterodactyl\BlueprintFramework\Extensions\seometa\Models\SeoSetting;
use Pterodactyl\Models\Server;

/*
|--------------------------------------------------------------------------
| SEO & Meta — Client API Routes
|--------------------------------------------------------------------------
| Prefix: /api/client/extensions/seometa
*/

/**
 * Dynamic Open Graph image for server share cards.
 * Public route — social platform crawlers have no auth.
 */
Route::get('/og-image/{serverUuid}', function (string $serverUuid) {

    // Check if server cards are enabled
    if (SeoSetting::get('enable_server_cards', '1') !== '1') {
        abort(404);
    }

    // Find the server
    $server = Server::where('uuid', $serverUuid)
        ->orWhere('uuidShort', $serverUuid)
        ->first();

    $serverName = $server ? $server->name : 'Unknown Server';
    $hostingName = SeoSetting::get('hosting_name', '');
    $hostingLogoPath = SeoSetting::get('hosting_logo', '');
    $themeColor = SeoSetting::get('theme_color', '#1a1a2e');

    // Image dimensions (standard OG size)
    $width = 1200;
    $height = 630;

    $img = imagecreatetruecolor($width, $height);
    imagesavealpha($img, true);

    // Parse theme color for gradient
    $r = hexdec(substr($themeColor, 1, 2));
    $g = hexdec(substr($themeColor, 3, 2));
    $b = hexdec(substr($themeColor, 5, 2));

    // Draw gradient background (dark to theme color to dark)
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            // Radial-ish gradient from center
            $cx = $width / 2;
            $cy = $height / 2;
            $dist = sqrt(pow($x - $cx, 2) + pow($y - $cy, 2));
            $maxDist = sqrt(pow($cx, 2) + pow($cy, 2));
            $factor = max(0, 1 - ($dist / $maxDist));
            $factor = $factor * $factor; // ease

            $pr = (int)($r * $factor * 0.4);
            $pg = (int)($g * $factor * 0.4);
            $pb = (int)($b * $factor * 0.4);

            $color = imagecolorallocate($img, min(255, $pr + 8), min(255, $pg + 8), min(255, $pb + 10));
            imagesetpixel($img, $x, $y, $color);
        }
    }

    // Add subtle grid pattern overlay
    $gridColor = imagecolorallocatealpha($img, 255, 255, 255, 120);
    for ($x = 0; $x < $width; $x += 40) {
        imageline($img, $x, 0, $x, $height, $gridColor);
    }
    for ($y = 0; $y < $height; $y += 40) {
        imageline($img, 0, $y, $width, $y, $gridColor);
    }

    // Draw server name at center
    $white = imagecolorallocate($img, 255, 255, 255);
    $lightGray = imagecolorallocate($img, 180, 180, 190);
    $dimGray = imagecolorallocate($img, 120, 120, 135);

    // Use a default font or find system font
    $fontBold = null;
    $fontRegular = null;

    // Try common font paths
    $fontPaths = [
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        '/usr/share/fonts/TTF/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
    ];
    foreach ($fontPaths as $fp) {
        if (file_exists($fp)) {
            $fontBold = $fp;
            break;
        }
    }

    $fontPathsRegular = [
        '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        '/usr/share/fonts/TTF/DejaVuSans.ttf',
        '/usr/share/fonts/dejavu/DejaVuSans.ttf',
        '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
    ];
    foreach ($fontPathsRegular as $fp) {
        if (file_exists($fp)) {
            $fontRegular = $fp;
            break;
        }
    }

    if ($fontBold) {
        // Server name — large centered text
        $fontSize = 42;
        $bbox = imagettfbbox($fontSize, 0, $fontBold, $serverName);
        $textWidth = abs($bbox[2] - $bbox[0]);

        // Scale down if too wide
        while ($textWidth > ($width - 120) && $fontSize > 20) {
            $fontSize -= 2;
            $bbox = imagettfbbox($fontSize, 0, $fontBold, $serverName);
            $textWidth = abs($bbox[2] - $bbox[0]);
        }

        $textX = ($width - $textWidth) / 2;
        $textY = ($height / 2) + ($fontSize / 3);

        // Text shadow
        $shadow = imagecolorallocatealpha($img, 0, 0, 0, 50);
        imagettftext($img, $fontSize, 0, $textX + 2, $textY + 2, $shadow, $fontBold, $serverName);
        // Main text
        imagettftext($img, $fontSize, 0, $textX, $textY, $white, $fontBold, $serverName);

        // Hosting name — bottom left
        if ($hostingName && $fontRegular) {
            $hostSize = 16;
            $hostY = $height - 30;

            // Logo
            $logoStartX = 30;
            $logoSize = 36;
            $textStartX = $logoStartX;

            if ($hostingLogoPath) {
                $logoFullPath = public_path($hostingLogoPath);
                if (file_exists($logoFullPath)) {
                    $logoInfo = getimagesize($logoFullPath);
                    if ($logoInfo) {
                        $logoSrc = null;
                        switch ($logoInfo[2]) {
                            case IMAGETYPE_PNG: $logoSrc = imagecreatefrompng($logoFullPath); break;
                            case IMAGETYPE_JPEG: $logoSrc = imagecreatefromjpeg($logoFullPath); break;
                            case IMAGETYPE_GIF: $logoSrc = imagecreatefromgif($logoFullPath); break;
                            case IMAGETYPE_WEBP: $logoSrc = imagecreatefromwebp($logoFullPath); break;
                        }
                        if ($logoSrc) {
                            $logoW = imagesx($logoSrc);
                            $logoH = imagesy($logoSrc);
                            imagecopyresampled($img, $logoSrc, $logoStartX, $hostY - $logoSize + 6, 0, 0, $logoSize, $logoSize, $logoW, $logoH);
                            imagedestroy($logoSrc);
                            $textStartX = $logoStartX + $logoSize + 10;
                        }
                    }
                }
            }

            imagettftext($img, $hostSize, 0, $textStartX, $hostY, $lightGray, $fontRegular, $hostingName);
        }

        // "Game Server" label — smaller, above server name
        if ($fontRegular) {
            $labelSize = 14;
            $label = 'GAME SERVER';
            $lbbox = imagettfbbox($labelSize, 0, $fontRegular, $label);
            $lw = abs($lbbox[2] - $lbbox[0]);
            $lx = ($width - $lw) / 2;
            $ly = $textY - $fontSize - 15;
            imagettftext($img, $labelSize, 0, $lx, $ly, $dimGray, $fontRegular, $label);

            // Decorative line under label
            $lineColor = imagecolorallocatealpha($img, $r, $g, $b, 60);
            $lineY = $ly + 10;
            imageline($img, $lx, $lineY, $lx + $lw, $lineY, $lineColor);
        }
    } else {
        // Fallback: use built-in font (no TTF available)
        $font = 5; // largest built-in font
        $textWidth = imagefontwidth($font) * strlen($serverName);
        $textX = ($width - $textWidth) / 2;
        $textY = ($height / 2) - (imagefontheight($font) / 2);
        imagestring($img, $font, $textX, $textY, $serverName, $white);

        if ($hostingName) {
            $hostWidth = imagefontwidth(3) * strlen($hostingName);
            imagestring($img, 3, 30, $height - 40, $hostingName, $lightGray);
        }
    }

    // Output PNG
    ob_start();
    imagepng($img);
    $imageData = ob_get_clean();
    imagedestroy($img);

    return response($imageData, 200)
        ->header('Content-Type', 'image/png')
        ->header('Cache-Control', 'public, max-age=3600');
});
