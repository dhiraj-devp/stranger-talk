<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Content;
use App\Models\ContentCategory;
use App\Models\MediaAsset;
use App\Models\NavigationItem;
use App\Models\SeoRedirect;
use App\Models\SiteSetting;
use App\Models\Tag;
use App\Services\SeoAuditor;
use App\Services\SeoBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SeoController extends Controller
{
    public function overview(): View
    {
        $published = Content::query()->where('status', 'published');
        $titles = Content::query()->pluck('title')->countBy()->filter(fn ($count) => $count > 1)->keys();
        $descriptions = Content::query()->whereNotNull('seo_description')->pluck('seo_description')->countBy()->filter(fn ($count) => $count > 1)->keys();
        $known = collect(['/', '/privacy', '/terms', '/guidelines', '/blog', '/guides', '/login'])
            ->merge(Content::query()->public()->get()->map(fn (Content $content) => $content->publicPath()));
        $broken = NavigationItem::query()->orderBy('id')->get()->filter(function (NavigationItem $item) use ($known) {
            $path = parse_url($item->url, PHP_URL_PATH) ?: $item->url;

            return str_starts_with($item->url, '/') && ! $known->contains(rtrim($path, '/') ?: '/');
        });

        return view('admin.seo.overview', [
            'indexable' => (clone $published)->where('robots_index', true)->count() + 6,
            'noindex' => (clone $published)->where('robots_index', false)->count(),
            'missingDescription' => (clone $published)->where(function ($query) {
                $query->whereNull('seo_description')->orWhere('seo_description', '');
            })->where(function ($query) {
                $query->whereNull('excerpt')->orWhere('excerpt', '');
            })->count(),
            'duplicateTitles' => $titles,
            'duplicateDescriptions' => $descriptions,
            'customCanonicals' => (clone $published)->whereNotNull('canonical')->where('canonical', '!=', '')->count(),
            'noImage' => (clone $published)->whereIn('type', ['post', 'guide'])->whereNull('featured_image')->count(),
            'missingAlt' => MediaAsset::query()->where(function ($query) {
                $query->whereNull('alt')->orWhere('alt', '');
            })->count(),
            'recent' => Content::query()->latest('updated_at')->limit(8)->get(),
            'broken' => $broken,
            'disallowAll' => (bool) SiteSetting::current()->robots_disallow_all,
        ]);
    }

    public function settings(): View
    {
        return view('admin.seo.settings', ['site' => SiteSetting::current()]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:80'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'default_title' => ['required', 'string', 'max:70'],
            'default_description' => ['required', 'string', 'max:200'],
            'keywords' => ['nullable', 'string', 'max:255'],
            'canonical_base' => ['nullable', 'string', 'max:255'],
            'og_title' => ['nullable', 'string', 'max:70'],
            'og_description' => ['nullable', 'string', 'max:200'],
            'twitter_title' => ['nullable', 'string', 'max:70'],
            'twitter_description' => ['nullable', 'string', 'max:200'],
            'organization_name' => ['nullable', 'string', 'max:80'],
            'organization_description' => ['nullable', 'string', 'max:300'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'support_url' => ['nullable', 'url', 'max:255'],
            'theme_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'language' => ['required', 'string', 'max:12'],
            'locale' => ['required', 'string', 'max:12'],
            'timezone' => ['required', 'timezone'],
            'primary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'secondary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'footer_text' => ['nullable', 'string', 'max:300'],
            'copyright' => ['nullable', 'string', 'max:120'],
            'header_cta_label' => ['nullable', 'string', 'max:40'],
            'header_cta_url' => ['nullable', 'string', 'max:255'],
            'gsc_verification' => ['nullable', 'regex:/^[A-Za-z0-9_-]{10,128}$/'],
            'ga_measurement_id' => ['nullable', 'regex:/^G-[A-Z0-9]+$/'],
            'gtm_id' => ['nullable', 'regex:/^GTM-[A-Z0-9]+$/'],
            'robots_extra' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['canonical_base'] ?? null) {
            $parts = parse_url($data['canonical_base']);
            if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || ! in_array($parts['path'] ?? '', ['', '/'], true)) {
                return back()->withInput()->withErrors(['canonical_base' => 'Use an https origin such as https://kokomeet.live.']);
            }
            $data['canonical_base'] = 'https://'.$parts['host'];
        } else {
            $data['canonical_base'] = null;
        }

        $disallow = $request->boolean('robots_disallow_all');
        if ($disallow && $request->input('robots_confirm') !== 'BLOCK SITE') {
            return back()->withInput()->withErrors(['robots_confirm' => 'Type BLOCK SITE to hide the whole site from crawlers.']);
        }
        $data['robots_disallow_all'] = $disallow;

        $socials = [];
        foreach ((array) $request->input('social_label', []) as $index => $label) {
            $label = trim((string) $label);
            $url = trim((string) $request->input('social_url.'.$index));
            if ($label === '' && $url === '') {
                continue;
            }
            if ($label === '' || ! str_starts_with($url, 'https://')) {
                return back()->withInput()->withErrors(['social_url' => 'Each social profile needs a label and an https URL.']);
            }
            $socials[] = ['label' => $label, 'url' => $url];
        }
        $data['socials'] = $socials;

        foreach (['logo', 'logo_light', 'logo_dark', 'header_logo', 'footer_logo', 'favicon', 'apple_touch_icon', 'web_app_icon', 'og_image', 'twitter_image', 'organization_logo'] as $field) {
            if ($request->hasFile($field)) {
                $data[$field] = $this->storeImage($request->file($field))->path;
            }
        }

        $site = SiteSetting::query()->first() ?? new SiteSetting(SiteSetting::defaults());
        $site->fill($data)->save();
        SiteSetting::forgetCache();
        if (! app()->environment('testing')) {
            $css = ":root{--violet:{$site->primary_color};--theme:{$site->theme_color};}";
            file_put_contents(public_path('css/brand.css'), $css);
        }

        return back()->with('status', 'Site settings saved.');
    }

    public function contents(): View
    {
        return view('admin.seo.contents', [
            'contents' => Content::query()->latest('updated_at')->paginate(20),
            'categories' => ContentCategory::query()->orderBy('name')->get(),
            'tags' => Tag::query()->orderBy('name')->get(),
            'authors' => Author::query()->orderBy('name')->get(),
        ]);
    }

    public function createContent(): View
    {
        return view('admin.seo.content-form', $this->formData(new Content(['status' => 'draft', 'type' => 'page', 'robots_index' => true, 'robots_follow' => true])));
    }

    public function storeContent(Request $request, SeoAuditor $auditor): RedirectResponse
    {
        $content = new Content;
        $this->saveContent($request, $content);

        return redirect()->route('admin.content.edit', $content)->with('audit', $auditor->forContent($content->fresh()));
    }

    public function editContent(Content $content, SeoAuditor $auditor, SeoBuilder $builder): View
    {
        return view('admin.seo.content-form', $this->formData($content) + [
            'audit' => $auditor->forContent($content),
            'preview' => $builder->content($content),
        ]);
    }

    public function updateContent(Request $request, Content $content, SeoAuditor $auditor): RedirectResponse
    {
        $this->saveContent($request, $content);

        return back()->with('audit', $auditor->forContent($content->fresh()))->with('status', 'Content saved.');
    }

    public function destroyContent(Content $content): RedirectResponse
    {
        $content->delete();

        return redirect()->route('admin.content')->with('status', 'Content deleted.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'alpha_dash', 'max:80', 'unique:categories,slug'],
            'description' => ['nullable', 'string', 'max:300'],
        ]);
        ContentCategory::query()->create($data);

        return back()->with('status', 'Category added.');
    }

    public function storeTag(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'alpha_dash', 'max:80', 'unique:tags,slug'],
        ]);
        Tag::query()->create($data);

        return back()->with('status', 'Tag added.');
    }

    public function storeAuthor(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'alpha_dash', 'max:80', 'unique:authors,slug'],
            'bio' => ['nullable', 'string', 'max:500'],
            'image_alt' => ['nullable', 'string', 'max:160'],
        ]);
        if ($request->hasFile('image')) {
            $data['image'] = $this->storeImage($request->file('image'), $data['image_alt'] ?? null)->path;
        }
        Author::query()->create($data);

        return back()->with('status', 'Author added. Add an author only for a real person.');
    }

    public function media(): View
    {
        return view('admin.seo.media', ['media' => MediaAsset::query()->latest()->paginate(24)]);
    }

    public function storeMedia(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            'alt' => ['nullable', 'string', 'max:160'],
        ]);
        $this->storeImage($request->file('file'), $request->input('alt'));

        return back()->with('status', $request->filled('alt') ? 'Image saved.' : 'Image saved. Add alt text before using it on a page.');
    }

    public function destroyMedia(MediaAsset $medium): RedirectResponse
    {
        Storage::disk('public')->delete($medium->path);
        $medium->delete();

        return back()->with('status', 'Image deleted.');
    }

    public function redirects(): View
    {
        return view('admin.seo.redirects', ['redirects' => SeoRedirect::query()->latest()->paginate(30)]);
    }

    public function storeRedirect(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from_path' => ['required', 'regex:/^\/[a-z0-9][a-z0-9\-\/]*$/', 'unique:seo_redirects,from_path'],
            'to_path' => ['nullable', 'regex:/^\/[a-z0-9][a-z0-9\-\/]*$/'],
            'status_code' => ['required', 'in:301,302,410'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);
        $data['from_path'] = $this->guardPath($data['from_path']);
        if ((int) $data['status_code'] === 410) {
            $data['to_path'] = null;
        } elseif (empty($data['to_path'])) {
            return back()->withInput()->withErrors(['to_path' => 'A 301 or 302 redirect needs a destination.']);
        }
        if (($data['to_path'] ?? null) === $data['from_path']) {
            return back()->withInput()->withErrors(['to_path' => 'That redirect points at itself.']);
        }
        if ($data['to_path'] && SeoRedirect::query()->where('from_path', $data['to_path'])->where('enabled', true)->exists()) {
            return back()->withInput()->withErrors(['to_path' => 'That destination is already a redirect. Point at the final URL.']);
        }
        $data['enabled'] = $request->has('enabled');
        SeoRedirect::query()->create($data);
        SeoRedirect::forgetCache();

        return back()->with('status', 'Redirect saved.');
    }

    public function destroyRedirect(SeoRedirect $redirect): RedirectResponse
    {
        $redirect->delete();
        SeoRedirect::forgetCache();

        return back()->with('status', 'Redirect removed.');
    }

    public function navigation(): View
    {
        return view('admin.seo.navigation', [
            'items' => NavigationItem::query()->orderBy('location')->orderBy('sort_order')->get(),
        ]);
    }

    public function storeNavigation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'location' => ['required', 'in:header,footer'],
            'label' => ['required', 'string', 'max:40'],
            'url' => ['required', 'string', 'max:255'],
            'group_label' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
        $url = $data['url'];
        $path = strstr($url, '#', true) ?: $url;
        $safe = preg_match('/^#[a-z0-9\-]+$/', $url)
            || (str_starts_with($url, 'https://') && ! str_contains(substr($url, 8), ' '))
            || (str_starts_with($url, '/') && ! str_starts_with($url, '//') && ! str_contains($path, '//') && preg_match('/^\/[a-z0-9\-\/]*(#[a-z0-9\-]+)?$/', $url));
        if (! $safe) {
            return back()->withInput()->withErrors(['url' => 'Use a site path, a #anchor, or an https URL.']);
        }
        NavigationItem::query()->create($data);
        NavigationItem::forgetCache();

        return back()->with('status', 'Navigation item saved.');
    }

    public function destroyNavigation(NavigationItem $item): RedirectResponse
    {
        $item->delete();
        NavigationItem::forgetCache();

        return back()->with('status', 'Navigation item removed.');
    }

    private function formData(Content $content): array
    {
        return [
            'content' => $content,
            'categories' => ContentCategory::query()->orderBy('name')->get(),
            'tags' => Tag::query()->orderBy('name')->get(),
            'authors' => Author::query()->orderBy('name')->get(),
            'others' => Content::query()->when($content->exists, fn ($query) => $query->where('id', '!=', $content->id))->orderBy('title')->limit(40)->get(),
            'audit' => [],
            'preview' => null,
        ];
    }

    private function saveContent(Request $request, Content $content): void
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(Content::TYPES)],
            'title' => ['required', 'string', 'max:140'],
            'slug' => ['required', 'alpha_dash', 'max:80', Rule::notIn(Content::RESERVED), Rule::unique('contents', 'slug')->ignore($content->id)],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string', 'max:50000'],
            'image_alt' => ['nullable', 'string', 'max:160'],
            'author_id' => ['nullable', 'exists:authors,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'status' => ['required', 'in:draft,published'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:200'],
            'canonical' => ['nullable', 'string', 'max:255'],
            'robots_index' => ['nullable', 'boolean'],
            'robots_follow' => ['nullable', 'boolean'],
            'og_title' => ['nullable', 'string', 'max:70'],
            'og_description' => ['nullable', 'string', 'max:200'],
            'twitter_title' => ['nullable', 'string', 'max:70'],
            'twitter_description' => ['nullable', 'string', 'max:200'],
            'focus_topic' => ['nullable', 'string', 'max:80'],
            'secondary_topics' => ['nullable', 'string', 'max:200'],
            'breadcrumb_title' => ['nullable', 'string', 'max:80'],
            'schema_type' => ['nullable', Rule::in(['WebPage', 'Article', 'FAQPage'])],
            'json_ld' => ['nullable', 'string', 'max:10000'],
            'featured' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
        if (($data['canonical'] ?? '') !== '' && ! preg_match('#^/|^https://#', $data['canonical'])) {
            throw ValidationException::withMessages(['canonical' => 'Canonical must be a site path or an https URL.']);
        }
        if (($data['json_ld'] ?? '') !== '') {
            $decoded = json_decode($data['json_ld'], true);
            if (! is_array($decoded) || preg_match('/aggregateRating|ratingValue|"review"/i', $data['json_ld'])) {
                throw ValidationException::withMessages(['json_ld' => 'Custom JSON-LD must be valid JSON and cannot include ratings or reviews.']);
            }
        }
        $data['robots_index'] = $request->boolean('robots_index');
        $data['robots_follow'] = $request->boolean('robots_follow');
        $data['featured'] = $request->boolean('featured');
        $data['related_ids'] = array_values(array_map('intval', (array) $request->input('related_ids', [])));
        if ($data['status'] === 'published' && ! $content->published_at) {
            $data['published_at'] = now();
        }
        if ($request->hasFile('featured_image')) {
            $request->validate(['featured_image' => ['file', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048']]);
            $asset = $this->storeImage($request->file('featured_image'), $data['image_alt'] ?? null);
            $data['featured_image'] = $asset->path;
            $data['image_width'] = $asset->width;
            $data['image_height'] = $asset->height;
        }
        $content->fill($data)->save();
        $content->tags()->sync(array_map('intval', (array) $request->input('tags', [])));
    }

    private function storeImage(UploadedFile $file, ?string $alt = null): MediaAsset
    {
        $path = $file->store('seo', 'public');
        $size = @getimagesize($file->getRealPath()) ?: [null, null];

        return MediaAsset::query()->create([
            'path' => $path,
            'alt' => $alt,
            'mime' => $file->getMimeType(),
            'width' => $size[0],
            'height' => $size[1],
        ]);
    }

    private function guardPath(string $path): string
    {
        foreach (['/admin', '/api', '/login', '/auth', '/match', '/home', '/video'] as $blocked) {
            if ($path === $blocked || str_starts_with($path, $blocked.'/')) {
                throw ValidationException::withMessages(['from_path' => 'That address has to keep working. Redirect a retired public URL instead.']);
            }
        }

        return $path;
    }
}
