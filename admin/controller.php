<?php

use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Admin\BlueprintAdminLibrary as BlueprintExtensionLibrary;
use Pterodactyl\BlueprintFramework\Extensions\seometa\Models\SeoSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * SEO & Meta — Admin Controller
 * Handles settings management and image uploads.
 */
return new class {

    public function __construct(private BlueprintExtensionLibrary $blueprint) {}

    /**
     * GET — Render the admin settings page.
     */
    public function index(): mixed
    {
        $settings = SeoSetting::allSettings();
        $root = '/admin/extensions/seometa';

        return $this->blueprint->view('index', [
            'root'     => $root,
            'blueprint' => $this->blueprint,
            'settings' => $settings,
        ]);
    }

    /**
     * POST — Save settings and handle file uploads.
     */
    public function post(Request $request): mixed
    {
        $action = $request->input('action', 'save_settings');

        if ($action === 'save_settings') {
            return $this->saveSettings($request);
        }

        return redirect('/admin/extensions/seometa')->with('error', 'Unknown action.');
    }

    /**
     * PATCH — AJAX endpoints (for future use).
     */
    public function update(Request $request): mixed
    {
        return response()->json(['status' => 'ok']);
    }

    /**
     * Save all SEO settings.
     */
    private function saveSettings(Request $request): mixed
    {
        // Text fields
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

    /**
     * Handle file upload. Returns the public URL path or null.
     */
    private function handleUpload($file, string $dataDir, string $prefix): ?string
    {
        if (!$file->isValid()) return null;

        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico'];
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, $allowed)) return null;

        // Remove old file
        $oldPath = SeoSetting::get($prefix);
        if ($oldPath) $this->removeFile($oldPath);

        $filename = $prefix . '_' . time() . '.' . $ext;
        $file->move($dataDir, $filename);

        // Return URL path relative to public
        return '/extensions/seometa/' . $filename;
    }

    /**
     * Get the data directory path.
     */
    private function getDataDir(): string
    {
        $dir = base_path('resources/views/blueprint/extensions/seometa/data');
        if (!is_dir($dir)) {
            // Try the public path
            $dir = public_path('extensions/seometa');
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * Remove a previously uploaded file.
     */
    private function removeFile(?string $urlPath): void
    {
        if (!$urlPath) return;
        $fullPath = public_path($urlPath);
        if (file_exists($fullPath)) @unlink($fullPath);
    }
};
