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

    // Detect if we're on a server page and use dynamic OG image
    $seoIsServerPage = false;
    $seoServerUuid   = null;

    $path = request()->path();
    if (preg_match('#^server/([a-f0-9\-]+)#i', $path, $m)) {
        $seoIsServerPage = true;
        $seoServerUuid   = $m[1];
    }

    // Use dynamic server image if on a server page and enabled
    $seoFinalOgImage = $seoOgImage;
    if ($seoIsServerPage && $seoServerCards === '1' && $seoServerUuid) {
        $seoFinalOgImage = '/api/client/extensions/seometa/og-image/' . $seoServerUuid;
    }

    // Build absolute URL for OG image
    if ($seoFinalOgImage && !str_starts_with($seoFinalOgImage, 'http')) {
        $seoFinalOgImage = url($seoFinalOgImage);
    }
    if ($seoFavicon && !str_starts_with($seoFavicon, 'http')) {
        $seoFavicon = url($seoFavicon);
    }
@endphp

<script>
(function() {
    'use strict';

    var head = document.head || document.getElementsByTagName('head')[0];

    function setMeta(property, content, isName) {
        if (!content) return;
        var attr = isName ? 'name' : 'property';
        var existing = document.querySelector('meta[' + attr + '="' + property + '"]');
        if (existing) {
            existing.setAttribute('content', content);
        } else {
            var meta = document.createElement('meta');
            meta.setAttribute(attr, property);
            meta.setAttribute('content', content);
            head.appendChild(meta);
        }
    }

    function setLink(rel, href, type) {
        if (!href) return;
        var existing = document.querySelector('link[rel="' + rel + '"]');
        if (existing) {
            existing.setAttribute('href', href);
            if (type) existing.setAttribute('type', type);
        } else {
            var link = document.createElement('link');
            link.setAttribute('rel', rel);
            link.setAttribute('href', href);
            if (type) link.setAttribute('type', type);
            head.appendChild(link);
        }
    }

    // Page title
    @if($seoTitle)
        document.title = @json($seoTitle);
    @endif

    // Basic meta
    @if($seoDescription)
        setMeta('description', @json($seoDescription), true);
    @endif

    // Open Graph
    @if($seoTitle)
        setMeta('og:title', @json($seoTitle));
    @endif
    @if($seoDescription)
        setMeta('og:description', @json($seoDescription));
    @endif
    setMeta('og:type', 'website');
    setMeta('og:url', @json($seoCurrentUrl));
    @if($seoFinalOgImage)
        setMeta('og:image', @json($seoFinalOgImage));
    @endif
    @if($seoHostName)
        setMeta('og:site_name', @json($seoHostName));
    @endif

    // Twitter Card
    setMeta('twitter:card', @json($seoCardType), true);
    @if($seoTitle)
        setMeta('twitter:title', @json($seoTitle), true);
    @endif
    @if($seoDescription)
        setMeta('twitter:description', @json($seoDescription), true);
    @endif
    @if($seoFinalOgImage)
        setMeta('twitter:image', @json($seoFinalOgImage), true);
    @endif

    // Theme color
    @if($seoThemeColor)
        setMeta('theme-color', @json($seoThemeColor), true);
    @endif

    // Robots (noindex)
    @if($seoIndexing !== '1')
        setMeta('robots', 'noindex, nofollow', true);
    @endif

    // Favicon
    @if($seoFavicon)
        setLink('icon', @json($seoFavicon));
        setLink('shortcut icon', @json($seoFavicon));
    @endif

})();
</script>
