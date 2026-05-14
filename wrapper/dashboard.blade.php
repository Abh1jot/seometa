{{-- SEO & Meta — Dashboard Wrapper --}}
{{-- Injects Open Graph, Twitter, and SEO meta tags into every page --}}

@php
    use Pterodactyl\BlueprintFramework\Extensions\seometa\Models\SeoSetting;

    $seoTitle       = SeoSetting::get('site_title', '');
    $seoDescription = SeoSetting::get('site_description', '');
    $seoOgImage     = SeoSetting::get('og_image', '');
    $seoFavicon     = SeoSetting::get('favicon', '');
    $seoThemeColor  = SeoSetting::get('theme_color', '#1a1a2e');
    $seoCardType    = SeoSetting::get('twitter_card_type', 'summary_large_image');
    $seoHostName    = SeoSetting::get('hosting_name', '');
    $seoServerCards = SeoSetting::get('enable_server_cards', '1');
    $seoIndexing    = SeoSetting::get('allow_google_indexing', '1');
    $seoCurrentUrl  = url()->current();

    // Detect if we're on a server page → use dynamic OG image
    $seoIsServerPage = false;
    $seoServerUuid   = null;

    $path = request()->path();
    if (preg_match('#^server/([a-f0-9\-]+)#i', $path, $m)) {
        $seoIsServerPage = true;
        $seoServerUuid   = $m[1];
    }

    // Use public og.php endpoint for server pages (no auth needed for crawlers)
    $seoFinalOgImage = $seoOgImage;
    if ($seoIsServerPage && $seoServerCards === '1' && $seoServerUuid) {
        $seoFinalOgImage = url('/extensions/seometa/og.php?id=' . $seoServerUuid);
    }

    // Build absolute URLs
    if ($seoFinalOgImage && !str_starts_with($seoFinalOgImage, 'http')) {
        $seoFinalOgImage = url($seoFinalOgImage);
    }
    if ($seoFavicon && !str_starts_with($seoFavicon, 'http')) {
        $seoFavicon = url($seoFavicon);
    }
@endphp

{{-- Server-rendered meta tags (visible to social crawlers) --}}
@if($seoDescription)
<meta name="description" content="{{ e($seoDescription) }}">
@endif
@if($seoThemeColor)
<meta name="theme-color" content="{{ e($seoThemeColor) }}">
@endif
@if($seoIndexing !== '1')
<meta name="robots" content="noindex, nofollow">
@endif

{{-- Open Graph --}}
<meta property="og:type" content="website">
<meta property="og:url" content="{{ e($seoCurrentUrl) }}">
@if($seoTitle)
<meta property="og:title" content="{{ e($seoTitle) }}">
@endif
@if($seoDescription)
<meta property="og:description" content="{{ e($seoDescription) }}">
@endif
@if($seoFinalOgImage)
<meta property="og:image" content="{{ $seoFinalOgImage }}">
@endif
@if($seoHostName)
<meta property="og:site_name" content="{{ e($seoHostName) }}">
@endif

{{-- Twitter Card --}}
<meta name="twitter:card" content="{{ e($seoCardType) }}">
@if($seoTitle)
<meta name="twitter:title" content="{{ e($seoTitle) }}">
@endif
@if($seoDescription)
<meta name="twitter:description" content="{{ e($seoDescription) }}">
@endif
@if($seoFinalOgImage)
<meta name="twitter:image" content="{{ $seoFinalOgImage }}">
@endif

{{-- Favicon + Title (JS needed to override Pterodactyl's defaults) --}}
<script>
(function(){
    @if($seoTitle)
    document.title = @json($seoTitle);
    @endif

    @if($seoFavicon)
    // Remove all existing favicon links
    document.querySelectorAll('link[rel="icon"], link[rel="shortcut icon"], link[rel="apple-touch-icon"]').forEach(function(el) { el.remove(); });
    // Add new favicon
    var fav = document.createElement('link');
    fav.rel = 'icon';
    fav.href = @json($seoFavicon);
    document.head.appendChild(fav);
    var fav2 = document.createElement('link');
    fav2.rel = 'shortcut icon';
    fav2.href = @json($seoFavicon);
    document.head.appendChild(fav2);
    @endif
})();
</script>
