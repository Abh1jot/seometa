<?php

namespace Pterodactyl\Http\Controllers\Admin\Extensions\seometa;

use Illuminate\Http\Request;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Admin\BlueprintAdminLibrary as BlueprintExtensionLibrary;
use Pterodactyl\BlueprintFramework\Extensions\seometa\Models\SeoSetting;

class seometaExtensionController extends Controller
{
    private BlueprintExtensionLibrary $bp;

    public function __construct(BlueprintExtensionLibrary $bp)
    {
        $this->bp = $bp;
    }

    public function index(Request $request)
    {
        $this->ensureOgEndpoint();
        $settings = SeoSetting::allSettings();

        return view('admin.extensions.seometa.index', [
            'root'      => '/admin/extensions/seometa',
            'blueprint' => $this->bp,
            'settings'  => $settings,
        ]);
    }

    public function post(Request $request)
    {
        $action = $request->input('action', 'save_settings');

        if ($action === 'save_settings') {
            return $this->saveSettings($request);
        }

        return redirect('/admin/extensions/seometa')->with('error', 'Unknown action.');
    }

    public function update(Request $request)
    {
        return response()->json(['status' => 'ok']);
    }

    private function saveSettings(Request $request)
    {
        $textFields = [
            'site_title',
            'site_description',
            'theme_color',
            'twitter_card_type',
            'hosting_name',
        ];

        foreach ($textFields as $field) {
            if ($request->has($field)) {
                SeoSetting::set($field, $request->input($field, ''));
            }
        }

        // Toggle fields
        SeoSetting::set('enable_server_cards', $request->has('enable_server_cards') ? '1' : '0');
        SeoSetting::set('allow_google_indexing', $request->has('allow_google_indexing') ? '1' : '0');

        // File uploads (file takes priority over URL)
        $dataDir = $this->getDataDir();

        // OG Image
        if ($request->hasFile('og_image')) {
            $path = $this->handleUpload($request->file('og_image'), $dataDir, 'og_image');
            if ($path) SeoSetting::set('og_image', $path);
        } elseif ($url = $request->input('og_image_url')) {
            $url = trim($url);
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $this->removeFile(SeoSetting::get('og_image'));
                SeoSetting::set('og_image', $url);
            }
        }

        // Favicon
        if ($request->hasFile('favicon')) {
            $path = $this->handleUpload($request->file('favicon'), $dataDir, 'favicon');
            if ($path) SeoSetting::set('favicon', $path);
        } elseif ($url = $request->input('favicon_url')) {
            $url = trim($url);
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $this->removeFile(SeoSetting::get('favicon'));
                SeoSetting::set('favicon', $url);
            }
        }

        // Hosting Logo
        if ($request->hasFile('hosting_logo')) {
            $path = $this->handleUpload($request->file('hosting_logo'), $dataDir, 'hosting_logo');
            if ($path) SeoSetting::set('hosting_logo', $path);
        } elseif ($url = $request->input('hosting_logo_url')) {
            $url = trim($url);
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $this->removeFile(SeoSetting::get('hosting_logo'));
                SeoSetting::set('hosting_logo', $url);
            }
        }

        // Handle remove image actions
        if ($request->input('remove_og_image')) {
            $this->removeFile(SeoSetting::get('og_image'));
            SeoSetting::set('og_image', '');
        }
        if ($request->input('remove_favicon')) {
            $this->removeFile(SeoSetting::get('favicon'));
            SeoSetting::set('favicon', '');
        }
        if ($request->input('remove_hosting_logo')) {
            $this->removeFile(SeoSetting::get('hosting_logo'));
            SeoSetting::set('hosting_logo', '');
        }

        return redirect('/admin/extensions/seometa')->with('success', 'SEO settings saved successfully.');
    }

    private function handleUpload($file, string $dataDir, string $prefix): ?string
    {
        if (!$file->isValid()) return null;

        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'];
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, $allowed)) return null;

        $oldPath = SeoSetting::get($prefix);
        if ($oldPath) $this->removeFile($oldPath);

        $filename = $prefix . '_' . time() . '.' . $ext;
        $file->move($dataDir, $filename);

        return '/extensions/seometa/' . $filename;
    }

    private function getDataDir(): string
    {
        $dir = public_path('extensions/seometa');
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        return $dir;
    }

    private function removeFile(?string $urlPath): void
    {
        if (!$urlPath) return;
        if (str_starts_with($urlPath, 'http')) return; // Don't delete URLs
        $fullPath = public_path($urlPath);
        if (file_exists($fullPath)) @unlink($fullPath);
    }

    /**
     * Deploy og.php to public directory for social crawlers.
     * This file generates dynamic server OG images without auth.
     */
    private function ensureOgEndpoint(): void
    {
        $dir = $this->getDataDir();
        $target = $dir . '/og.php';

        // Try to find source from known Blueprint locations
        $sources = [
            base_path('.blueprint/dev/public/og.php'),
            base_path('resources/views/blueprint/extensions/seometa/public/og.php'),
        ];

        foreach ($sources as $src) {
            if (file_exists($src)) {
                // Copy if missing or source is newer
                if (!file_exists($target) || filemtime($src) > filemtime($target)) {
                    @copy($src, $target);
                }
                return;
            }
        }

        // Fallback: write a minimal bootstrap og.php if no source found
        if (!file_exists($target)) {
            $content = $this->getOgPhpContent();
            @file_put_contents($target, $content);
        }
    }

    private function getOgPhpContent(): string
    {
        return <<<'OGPHP'
<?php
$envPath = __DIR__ . '/../../.env';
if (!file_exists($envPath)) { http_response_code(500); exit; }
$env = [];
foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
}
$id = $_GET['id'] ?? '';
if (!$id || !preg_match('/^[a-f0-9\-]+$/i', $id)) { http_response_code(400); exit; }
try {
    $pdo = new PDO('mysql:host='.($env['DB_HOST']??'127.0.0.1').';port='.($env['DB_PORT']??'3306').';dbname='.($env['DB_DATABASE']??'panel'), $env['DB_USERNAME']??'', $env['DB_PASSWORD']??'');
} catch (Exception $e) { http_response_code(500); exit; }
$s = $pdo->prepare("SELECT `value` FROM `blueprint_seometa_settings` WHERE `key`=?");
$s->execute(['enable_server_cards']); if ($s->fetchColumn()==='0') { http_response_code(404); exit; }
$s = $pdo->prepare("SELECT `name` FROM `servers` WHERE `uuid`=? OR `uuidShort`=? LIMIT 1");
$s->execute([$id,$id]); $name = $s->fetchColumn() ?: 'Unknown Server';
$cfg = []; $r = $pdo->query("SELECT `key`,`value` FROM `blueprint_seometa_settings`");
while ($row = $r->fetch(PDO::FETCH_ASSOC)) $cfg[$row['key']] = $row['value'];
$tc = $cfg['theme_color'] ?? '#1a1a2e'; $hn = $cfg['hosting_name'] ?? ''; $hl = $cfg['hosting_logo'] ?? '';
$w=1200; $h=630; $img=imagecreatetruecolor($w,$h);
$cr=hexdec(substr($tc,1,2)); $cg=hexdec(substr($tc,3,2)); $cb=hexdec(substr($tc,5,2));
for($y=0;$y<$h;$y++) for($x=0;$x<$w;$x++) {
    $f=max(0,1-(sqrt(pow($x-$w/2,2)+pow($y-$h/2,2))/sqrt(pow($w/2,2)+pow($h/2,2)))); $f*=$f;
    imagesetpixel($img,$x,$y,imagecolorallocate($img,min(255,(int)($cr*$f*0.4)+8),min(255,(int)($cg*$f*0.4)+8),min(255,(int)($cb*$f*0.4)+10)));
}
$gc=imagecolorallocatealpha($img,255,255,255,120);
for($x=0;$x<$w;$x+=40)imageline($img,$x,0,$x,$h,$gc); for($y=0;$y<$h;$y+=40)imageline($img,0,$y,$w,$y,$gc);
$wh=imagecolorallocate($img,255,255,255); $lg=imagecolorallocate($img,180,180,190); $dg=imagecolorallocate($img,120,120,135);
$fb=null; $fr=null;
foreach(['/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf','/usr/share/fonts/TTF/DejaVuSans-Bold.ttf','/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf'] as $p) if(file_exists($p)){$fb=$p;break;}
foreach(['/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf','/usr/share/fonts/TTF/DejaVuSans.ttf','/usr/share/fonts/dejavu/DejaVuSans.ttf'] as $p) if(file_exists($p)){$fr=$p;break;}
if($fb){
    if($fr){$ls=14;$lb='GAME SERVER';$lbb=imagettfbbox($ls,0,$fr,$lb);$lw=abs($lbb[2]-$lbb[0]);$lx=($w-$lw)/2;$ly=$h/2-50;imagettftext($img,$ls,0,$lx,$ly,$dg,$fr,$lb);imageline($img,$lx,$ly+10,$lx+$lw,$ly+10,imagecolorallocatealpha($img,$cr,$cg,$cb,60));}
    $fs=42;$bb=imagettfbbox($fs,0,$fb,$name);$tw=abs($bb[2]-$bb[0]);while($tw>$w-120&&$fs>20){$fs-=2;$bb=imagettfbbox($fs,0,$fb,$name);$tw=abs($bb[2]-$bb[0]);}
    $tx=($w-$tw)/2;$ty=$h/2+$fs/3;imagettftext($img,$fs,0,$tx+2,$ty+2,imagecolorallocatealpha($img,0,0,0,50),$fb,$name);imagettftext($img,$fs,0,$tx,$ty,$wh,$fb,$name);
    if($hn&&$fr){$hy=$h-30;$sx=30;
        if($hl){$lp=str_starts_with($hl,'http')?$hl:(__DIR__.'/../..'.$hl);if(str_starts_with($hl,'http')){$tmp=tempnam(sys_get_temp_dir(),'l');@file_put_contents($tmp,@file_get_contents($hl));$lp=$tmp;}
            if($lp&&file_exists($lp)){$li=@getimagesize($lp);if($li){$ls2=null;switch($li[2]){case 1:$ls2=@imagecreatefromgif($lp);break;case 2:$ls2=@imagecreatefromjpeg($lp);break;case 3:$ls2=@imagecreatefrompng($lp);break;case 18:$ls2=@imagecreatefromwebp($lp);break;}
if($ls2){imagecopyresampled($img,$ls2,30,$hy-30,0,0,36,36,imagesx($ls2),imagesy($ls2));imagedestroy($ls2);$sx=76;}}}if(isset($tmp))@unlink($tmp);}
        imagettftext($img,16,0,$sx,$hy,$lg,$fr,$hn);}
}else{$f5=5;imagestring($img,$f5,($w-imagefontwidth($f5)*strlen($name))/2,$h/2-imagefontheight($f5)/2,$name,$wh);if($hn)imagestring($img,3,30,$h-40,$hn,$lg);}
header('Content-Type: image/png'); header('Cache-Control: public, max-age=3600');
imagepng($img); imagedestroy($img);
OGPHP;
    }
}
