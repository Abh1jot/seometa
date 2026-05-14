{{-- SEO & Meta — Admin Settings --}}

@if(session('success'))
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fa fa-check-circle"></i> {{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><i class="fa fa-exclamation-triangle"></i> {{ session('error') }}</div>
@endif

{{-- Header --}}
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-globe"></i> SEO & Meta</h3>
        <small class="text-muted" style="margin-left:10px;">Customize how your panel appears on search engines and social platforms.</small>
    </div>
</div>

<form action="{{ $root }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="action" value="save_settings">

    <div class="row">

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- LEFT SIDE: Settings Form (60%)                             --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        <div class="col-md-7">

            {{-- General SEO --}}
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-search"></i> General SEO</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label>Site Title</label>
                        <input type="text" name="site_title" class="form-control" id="inp-title"
                               value="{{ $settings['site_title'] ?? '' }}"
                               placeholder="My Minecraft Hosting Panel"
                               oninput="updatePreview()">
                        <p class="help-block">Appears in browser tab, Google results, and social embeds.</p>
                    </div>
                    <div class="form-group">
                        <label>Site Description</label>
                        <textarea name="site_description" class="form-control" id="inp-desc" rows="3"
                                  placeholder="Manage your game servers with our powerful control panel."
                                  oninput="updatePreview()">{{ $settings['site_description'] ?? '' }}</textarea>
                        <p class="help-block">Meta description for search engines and social previews. Keep under 160 characters.</p>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Theme Color</label>
                                <div style="display:flex;gap:8px;align-items:center;">
                                    <input type="color" name="theme_color" id="inp-color"
                                           value="{{ $settings['theme_color'] ?? '#1a1a2e' }}"
                                           style="width:50px;height:36px;border:1px solid #555;border-radius:4px;cursor:pointer;"
                                           oninput="updatePreview()">
                                    <input type="text" class="form-control" id="inp-color-text"
                                           value="{{ $settings['theme_color'] ?? '#1a1a2e' }}"
                                           style="width:100px;font-family:monospace;"
                                           oninput="document.getElementById('inp-color').value=this.value;updatePreview()">
                                </div>
                                <p class="help-block">Accent color for Discord/Twitter embeds.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Twitter Card Type</label>
                                <select name="twitter_card_type" class="form-control" id="inp-card-type" onchange="updatePreview()">
                                    <option value="summary_large_image" {{ ($settings['twitter_card_type'] ?? '') === 'summary_large_image' ? 'selected' : '' }}>Large Image</option>
                                    <option value="summary" {{ ($settings['twitter_card_type'] ?? '') === 'summary' ? 'selected' : '' }}>Summary (Small)</option>
                                </select>
                                <p class="help-block">How the card appears on Twitter/X.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Images --}}
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-image"></i> Images</h3>
                </div>
                <div class="box-body">
                    {{-- OG Image --}}
                    <div class="form-group">
                        <label>Open Graph Image</label>
                        @if(!empty($settings['og_image']))
                            <div style="margin-bottom:10px;">
                                <img src="{{ $settings['og_image'] }}" style="max-width:300px;max-height:150px;border-radius:8px;border:1px solid #444;">
                                <br>
                                <label style="margin-top:5px;font-weight:normal;color:#999;">
                                    <input type="checkbox" name="remove_og_image" value="1"> Remove current image
                                </label>
                            </div>
                        @endif
                        <input type="file" name="og_image" accept="image/*" id="inp-og-file" onchange="previewUpload(this, 'preview-og')">
                        <p class="help-block">Recommended: 1200×630px. Shown when your panel link is shared on Discord, Twitter, Facebook, etc.</p>
                    </div>

                    {{-- Favicon --}}
                    <div class="form-group">
                        <label>Favicon</label>
                        @if(!empty($settings['favicon']))
                            <div style="margin-bottom:10px;">
                                <img src="{{ $settings['favicon'] }}" style="width:32px;height:32px;border-radius:4px;border:1px solid #444;">
                                <label style="margin-left:10px;font-weight:normal;color:#999;">
                                    <input type="checkbox" name="remove_favicon" value="1"> Remove
                                </label>
                            </div>
                        @endif
                        <input type="file" name="favicon" accept="image/*,.ico">
                        <p class="help-block">Browser tab icon. Recommended: 32×32px or 64×64px .ico/.png file.</p>
                    </div>
                </div>
            </div>

            {{-- Server Share Cards --}}
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-share-alt"></i> Dynamic Server Share Cards</h3>
                </div>
                <div class="box-body">
                    <p class="text-muted">When someone shares a server link from your panel on Discord or Twitter, a custom image is generated with the server name and your hosting branding.</p>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="enable_server_cards" value="1"
                                   {{ ($settings['enable_server_cards'] ?? '1') === '1' ? 'checked' : '' }}
                                   onchange="updatePreview()">
                            Enable Dynamic Server Share Cards
                        </label>
                    </div>

                    <div class="form-group">
                        <label>Hosting / Company Name</label>
                        <input type="text" name="hosting_name" class="form-control" id="inp-host-name"
                               value="{{ $settings['hosting_name'] ?? '' }}"
                               placeholder="AzionCloud"
                               oninput="updatePreview()">
                        <p class="help-block">Your hosting company name. Appears on dynamic server share images.</p>
                    </div>

                    <div class="form-group">
                        <label>Hosting Logo</label>
                        @if(!empty($settings['hosting_logo']))
                            <div style="margin-bottom:10px;">
                                <img src="{{ $settings['hosting_logo'] }}" style="max-width:120px;max-height:60px;border-radius:6px;border:1px solid #444;">
                                <label style="margin-left:10px;font-weight:normal;color:#999;">
                                    <input type="checkbox" name="remove_hosting_logo" value="1"> Remove
                                </label>
                            </div>
                        @endif
                        <input type="file" name="hosting_logo" accept="image/*" id="inp-host-logo-file" onchange="previewUpload(this, 'preview-host-logo')">
                        <p class="help-block">Your company logo. Appears on the left side of dynamic server share images.</p>
                    </div>
                </div>
            </div>

            {{-- Save --}}
            <div style="margin-bottom:20px;">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fa fa-save"></i> Save Settings
                </button>
            </div>

        </div>

        {{-- ═══════════════════════════════════════════════════════════ --}}
        {{-- RIGHT SIDE: Live Preview (40%)                             --}}
        {{-- ═══════════════════════════════════════════════════════════ --}}
        <div class="col-md-5">
            <div class="box" style="position:sticky;top:80px;">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-eye"></i> Live Preview</h3>
                </div>
                <div class="box-body" style="background:#0e0e1a;padding:20px;">

                    {{-- Google Search Preview --}}
                    <div style="margin-bottom:25px;">
                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#666;margin-bottom:8px;font-weight:600;">Google Search</div>
                        <div style="background:#1a1a2e;border-radius:10px;padding:16px;border:1px solid #2a2a4a;">
                            <div id="preview-google-url" style="color:#8ab4f8;font-size:12px;margin-bottom:4px;">{{ request()->getHost() }}</div>
                            <div id="preview-google-title" style="color:#8ab4f8;font-size:17px;font-weight:400;line-height:1.3;margin-bottom:6px;">{{ $settings['site_title'] ?: 'Your Panel Title' }}</div>
                            <div id="preview-google-desc" style="color:#bdc1c6;font-size:13px;line-height:1.5;">{{ Str::limit($settings['site_description'] ?: 'Your panel description will appear here...', 160) }}</div>
                        </div>
                    </div>

                    {{-- Discord Preview --}}
                    <div style="margin-bottom:25px;">
                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#666;margin-bottom:8px;font-weight:600;">Discord Embed</div>
                        <div style="background:#2f3136;border-radius:4px;overflow:hidden;border-left:4px solid {{ $settings['theme_color'] ?? '#1a1a2e' }};" id="preview-discord-card">
                            <div style="padding:12px 16px;">
                                <div id="preview-discord-domain" style="color:#00b0f4;font-size:11px;margin-bottom:4px;">{{ request()->getHost() }}</div>
                                <div id="preview-discord-title" style="color:#fff;font-size:15px;font-weight:600;margin-bottom:6px;">{{ $settings['site_title'] ?: 'Your Panel Title' }}</div>
                                <div id="preview-discord-desc" style="color:#dcddde;font-size:13px;line-height:1.4;">{{ Str::limit($settings['site_description'] ?: 'Your panel description will appear here...', 160) }}</div>
                            </div>
                            <div id="preview-discord-image" style="padding:0 16px 12px;">
                                @if(!empty($settings['og_image']))
                                    <img src="{{ $settings['og_image'] }}" style="width:100%;border-radius:4px;max-height:200px;object-fit:cover;">
                                @else
                                    <div style="width:100%;height:80px;background:#36393f;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#666;font-size:12px;">
                                        <i class="fa fa-image" style="margin-right:6px;"></i> No OG image set
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Twitter/X Preview --}}
                    <div style="margin-bottom:25px;">
                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#666;margin-bottom:8px;font-weight:600;">Twitter / X</div>
                        <div style="background:#16181c;border:1px solid #2f3336;border-radius:16px;overflow:hidden;">
                            <div id="preview-twitter-image" style="background:#1e2028;height:130px;display:flex;align-items:center;justify-content:center;color:#555;">
                                @if(!empty($settings['og_image']))
                                    <img src="{{ $settings['og_image'] }}" style="width:100%;height:130px;object-fit:cover;">
                                @else
                                    <i class="fa fa-image" style="font-size:24px;"></i>
                                @endif
                            </div>
                            <div style="padding:12px;">
                                <div id="preview-twitter-title" style="color:#e7e9ea;font-size:14px;font-weight:700;margin-bottom:2px;">{{ $settings['site_title'] ?: 'Your Panel Title' }}</div>
                                <div id="preview-twitter-desc" style="color:#71767b;font-size:13px;line-height:1.3;">{{ Str::limit($settings['site_description'] ?: 'Your panel description...', 100) }}</div>
                                <div id="preview-twitter-domain" style="color:#71767b;font-size:13px;margin-top:4px;">{{ request()->getHost() }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Dynamic Server Card Preview --}}
                    <div>
                        <div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#666;margin-bottom:8px;font-weight:600;">Server Share Card (Discord)</div>
                        <div id="preview-server-card" style="background:#2f3136;border-radius:4px;overflow:hidden;border-left:4px solid {{ $settings['theme_color'] ?? '#1a1a2e' }};">
                            <div style="padding:12px 16px;">
                                <div style="color:#00b0f4;font-size:11px;margin-bottom:4px;">{{ request()->getHost() }}</div>
                                <div style="color:#fff;font-size:15px;font-weight:600;margin-bottom:6px;">My Awesome Server</div>
                                <div id="preview-server-hosting" style="color:#dcddde;font-size:13px;">Hosted by <strong id="preview-server-host-name">{{ $settings['hosting_name'] ?: 'Your Hosting' }}</strong></div>
                            </div>
                            <div style="padding:4px 16px 12px;">
                                <div id="preview-server-image" style="width:100%;height:120px;background:linear-gradient(135deg, #0a0a1a 0%, #1a1a3e 50%, #0a0a1a 100%);border-radius:4px;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;">
                                    <div style="position:absolute;left:16px;bottom:12px;display:flex;align-items:center;gap:8px;">
                                        <div id="preview-server-logo" style="width:28px;height:28px;background:#333;border-radius:6px;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                                            @if(!empty($settings['hosting_logo']))
                                                <img src="{{ $settings['hosting_logo'] }}" style="width:100%;height:100%;object-fit:cover;">
                                            @else
                                                <i class="fa fa-server" style="color:#666;font-size:12px;"></i>
                                            @endif
                                        </div>
                                        <span id="preview-server-brand" style="color:rgba(255,255,255,0.5);font-size:11px;">{{ $settings['hosting_name'] ?: 'Your Hosting' }}</span>
                                    </div>
                                    <span style="color:#fff;font-size:22px;font-weight:700;text-shadow:0 2px 8px rgba(0,0,0,0.5);letter-spacing:0.5px;">My Awesome Server</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</form>

{{-- Support & Feedback --}}
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-life-ring"></i> Support & Feedback</h3></div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4><i class="fa fa-comments"></i> Need Help?</h4>
                        <p>Join our Discord server for support, feature requests, and updates.</p>
                        <a href="https://azioncloud.com/discord" target="_blank" class="btn btn-primary">
                            <i class="fa fa-external-link"></i> Join Discord Server
                        </a>
                    </div>
                    <div class="col-md-6">
                        <h4><i class="fa fa-star"></i> Enjoying SEO & Meta?</h4>
                        <p>If this extension is useful to you, please leave us a <strong>5-star review</strong> on BuiltByBit!</p>
                        <a href="#" target="_blank" class="btn btn-warning">
                            <i class="fa fa-star"></i> Leave a 5-Star Review
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Credit --}}
<div class="text-center" style="padding:10px 0 2px 0;opacity:0.5;font-size:11px;">
    <a href="https://azioncloud.com" target="_blank" style="color:inherit;text-decoration:none;">azioncloud.com</a>
</div>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- Live Preview JavaScript                                        --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<script>
function updatePreview() {
    var title = document.getElementById('inp-title').value || 'Your Panel Title';
    var desc = document.getElementById('inp-desc').value || 'Your panel description will appear here...';
    var color = document.getElementById('inp-color').value || '#1a1a2e';
    var hostName = document.getElementById('inp-host-name').value || 'Your Hosting';

    // Sync color text
    document.getElementById('inp-color-text').value = color;

    // Google
    document.getElementById('preview-google-title').textContent = title;
    document.getElementById('preview-google-desc').textContent = desc.substring(0, 160);

    // Discord
    document.getElementById('preview-discord-title').textContent = title;
    document.getElementById('preview-discord-desc').textContent = desc.substring(0, 160);
    document.getElementById('preview-discord-card').style.borderLeftColor = color;

    // Twitter
    document.getElementById('preview-twitter-title').textContent = title;
    document.getElementById('preview-twitter-desc').textContent = desc.substring(0, 100);

    // Server card
    document.getElementById('preview-server-host-name').textContent = hostName;
    document.getElementById('preview-server-brand').textContent = hostName;
    document.getElementById('preview-server-card').style.borderLeftColor = color;
}

function previewUpload(input, targetId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            // Update Discord preview image
            if (input.id === 'inp-og-file') {
                document.getElementById('preview-discord-image').innerHTML =
                    '<img src="' + e.target.result + '" style="width:100%;border-radius:4px;max-height:200px;object-fit:cover;padding:0 0 0 0;">';
                document.getElementById('preview-twitter-image').innerHTML =
                    '<img src="' + e.target.result + '" style="width:100%;height:130px;object-fit:cover;">';
            }
            if (input.id === 'inp-host-logo-file') {
                document.getElementById('preview-server-logo').innerHTML =
                    '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;">';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
