@extends('layouts.admin')

@section('content')
    <h1>Site settings</h1>
    <p class="lead">Branding, default metadata, analytics IDs, and robots rules. Keywords are stored for editors and are not printed as a meta keywords tag.</p>
    <form class="panel stack" method="POST" action="{{ route('admin.seo.settings') }}" enctype="multipart/form-data">
        @csrf
        <h2>General</h2>
        @foreach ([
            'site_name' => 'Site name', 'tagline' => 'Tagline', 'default_title' => 'Default title',
            'organization_name' => 'Organization name', 'contact_email' => 'Contact email', 'support_url' => 'Support URL',
            'language' => 'Language', 'locale' => 'Locale', 'timezone' => 'Timezone', 'canonical_base' => 'Canonical origin',
        ] as $name => $label)
            <label>{{ $label }}<input name="{{ $name }}" value="{{ old($name, $site->$name) }}"></label>
        @endforeach
        <label>Default description<textarea name="default_description">{{ old('default_description', $site->default_description) }}</textarea></label>
        <label>Organization description<textarea name="organization_description">{{ old('organization_description', $site->organization_description) }}</textarea></label>
        <label>Keywords for editors<input name="keywords" value="{{ old('keywords', $site->keywords) }}"></label>
        <label>OG title<input name="og_title" value="{{ old('og_title', $site->og_title) }}"></label>
        <label>OG description<textarea name="og_description">{{ old('og_description', $site->og_description) }}</textarea></label>
        <label>X title<input name="twitter_title" value="{{ old('twitter_title', $site->twitter_title) }}"></label>
        <label>X description<textarea name="twitter_description">{{ old('twitter_description', $site->twitter_description) }}</textarea></label>
        <h2>Branding</h2>
        <label>Theme color<input name="theme_color" value="{{ old('theme_color', $site->theme_color) }}"></label>
        <label>Primary color<input name="primary_color" value="{{ old('primary_color', $site->primary_color) }}"></label>
        <label>Secondary color<input name="secondary_color" value="{{ old('secondary_color', $site->secondary_color) }}"></label>
        <label>Footer text<textarea name="footer_text">{{ old('footer_text', $site->footer_text) }}</textarea></label>
        <label>Copyright<input name="copyright" value="{{ old('copyright', $site->copyright) }}"></label>
        <label>Header button<input name="header_cta_label" value="{{ old('header_cta_label', $site->header_cta_label) }}"></label>
        <label>Header button URL<input name="header_cta_url" value="{{ old('header_cta_url', $site->header_cta_url) }}"></label>
        @foreach (['logo' => 'Logo', 'logo_light' => 'Light logo', 'logo_dark' => 'Dark logo', 'header_logo' => 'Header logo', 'footer_logo' => 'Footer logo', 'favicon' => 'Favicon', 'apple_touch_icon' => 'Apple touch icon', 'web_app_icon' => 'App icon', 'og_image' => 'Default OG image', 'twitter_image' => 'Default X image', 'organization_logo' => 'Organization logo'] as $name => $label)
            <label>{{ $label }}
                @if ($url = $site->publicUrl($site->$name))
                    <img src="{{ $url }}" alt="{{ $label }}" height="32">
                @endif
                <input type="file" name="{{ $name }}" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml,.ico">
            </label>
        @endforeach
        <h2>Social profiles</h2>
        @for ($i = 0; $i < 4; $i++)
            <label>Label<input name="social_label[]" value="{{ old('social_label.'.$i, data_get($site->socials, $i.'.label', '')) }}"></label>
            <label>HTTPS URL<input name="social_url[]" value="{{ old('social_url.'.$i, data_get($site->socials, $i.'.url', '')) }}"></label>
        @endfor
        <h2>Analytics</h2>
        <p class="lead">Loaded only in production. IDs are public measurement IDs, not secrets.</p>
        <label>Search Console verification<input name="gsc_verification" value="{{ old('gsc_verification', $site->gsc_verification) }}"></label>
        <label>Analytics measurement ID<input name="ga_measurement_id" value="{{ old('ga_measurement_id', $site->ga_measurement_id) }}" placeholder="G-XXXXXXXX"></label>
        <label>Tag Manager ID<input name="gtm_id" value="{{ old('gtm_id', $site->gtm_id) }}" placeholder="GTM-XXXX"></label>
        <h2>Robots</h2>
        <label>Extra disallow rules<textarea name="robots_extra" placeholder="Disallow: /old-campaign">{{ old('robots_extra', $site->robots_extra) }}</textarea></label>
        <label class="check"><input type="checkbox" name="robots_disallow_all" value="1" @checked(old('robots_disallow_all', $site->robots_disallow_all))> Disallow the entire site</label>
        <label>Type BLOCK SITE to confirm<input name="robots_confirm" value=""></label>
        <button type="submit">Save settings</button>
    </form>
    @if ($errors->any())
        <div class="panel">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif
@endsection
