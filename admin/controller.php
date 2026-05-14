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

        // File uploads
        $dataDir = $this->getDataDir();

        if ($request->hasFile('og_image')) {
            $path = $this->handleUpload($request->file('og_image'), $dataDir, 'og_image');
            if ($path) SeoSetting::set('og_image', $path);
        }

        if ($request->hasFile('favicon')) {
            $path = $this->handleUpload($request->file('favicon'), $dataDir, 'favicon');
            if ($path) SeoSetting::set('favicon', $path);
        }

        if ($request->hasFile('hosting_logo')) {
            $path = $this->handleUpload($request->file('hosting_logo'), $dataDir, 'hosting_logo');
            if ($path) SeoSetting::set('hosting_logo', $path);
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
        $fullPath = public_path($urlPath);
        if (file_exists($fullPath)) @unlink($fullPath);
    }
}
