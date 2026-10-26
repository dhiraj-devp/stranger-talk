<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\SeoRedirect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_are_indexable_and_private_routes_are_not(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Start Video Chat')
            ->assertSee('<h1>', false)
            ->assertSee('rel="canonical"', false)
            ->assertSee('name="description"', false)
            ->assertSee('og:title', false)
            ->assertSee('twitter:card', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('Koko Meet');

        $this->get('/privacy')->assertOk()->assertSee('index,follow', false)->assertSee('<h1>Privacy</h1>', false);
        $this->get('/terms')->assertOk()->assertSee('<h1>Terms</h1>', false);
        $this->get('/about')->assertOk()->assertSee('<h1>About Koko Meet</h1>', false);
        $this->get('/contact')->assertOk();
        $this->get('/safety')->assertOk();
        $this->get('/blog')->assertOk()->assertSee('How random video chat works');
        $this->get('/blog/how-random-video-chat-works')->assertOk()->assertSee('Article', false);
        $this->get('/guides')->assertOk();
        $this->get('/guides/random-video-chat')->assertRedirect('/guides/what-to-expect')->assertStatus(301);
        $this->get('/guides/what-to-expect')->assertOk()->assertSee('Article', false);
        $this->get('/random-video-chat')->assertOk()->assertSee('<h1>Random video chat</h1>', false)->assertSee('index,follow', false)->assertSee('href="/safety"', false);
        $this->get('/talk-to-strangers')->assertOk()->assertSee('<h1>Talk to strangers online</h1>', false)->assertSee('index,follow', false);
        $this->get('/omegle-alternative')->assertOk()->assertSee('<h1>An Omegle alternative for one-to-one chat</h1>', false)->assertSee('index,follow', false);
        $this->get('/features')->assertOk()->assertSee('<h1>Koko Meet features</h1>', false)->assertSee('index,follow', false);
        $this->get('/help')->assertOk()->assertSee('<h1>Help with camera and connection problems</h1>', false)->assertSee('index,follow', false);
        $this->get('/community-guidelines')->assertRedirect('/guidelines')->assertStatus(301);
        $this->get('/login')->assertOk()->assertSee('noindex,nofollow', false)->assertSee('Continue with Google');

        $this->get('/sitemap.xml')->assertOk()
            ->assertSee(url('/about'), false)
            ->assertSee(url('/blog/how-random-video-chat-works'), false)
            ->assertSee(url('/privacy'), false)
            ->assertSee(url('/blog'), false)
            ->assertSee(url('/guides'), false)
            ->assertSee(url('/contact'), false)
            ->assertSee(url('/random-video-chat'), false)
            ->assertSee(url('/omegle-alternative'), false)
            ->assertDontSee(url('/admin'), false)
            ->assertDontSee(url('/login'), false)
            ->assertDontSee(url('/video'), false)
            ->assertDontSee(url('/home'), false)
            ->assertDontSee(url('/health'), false);

        $this->get('/robots.txt')->assertOk()
            ->assertSee('Allow: /')
            ->assertSee('Disallow: /admin')
            ->assertSee('Disallow: /video')
            ->assertSee('Sitemap:');

        $this->get('/site.webmanifest')->assertOk()->assertJsonPath('name', 'Koko Meet');

        $settings = \App\Models\SiteSetting::query()->first();
        $settings->forceFill(['logo' => 'seo/logo.png', 'favicon' => 'seo/icon.png'])->save();
        \App\Models\SiteSetting::forgetCache();
        $this->get('/')->assertSee('/storage/seo/logo.png', false)->assertSee('/storage/seo/icon.png', false);
        \Illuminate\Support\Facades\Storage::disk('public')->put('seo/logo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
        $this->get('/storage/seo/logo.png')->assertOk();
        $this->get('/storage/../.env')->assertNotFound();
        $this->get('/missing-public-page')->assertNotFound();
        $this->get('/About')->assertRedirect('/about');

        $this->artisan('seo:audit')->assertSuccessful();

        $csv = storage_path('framework/testing-queries.csv');
        file_put_contents($csv, "query,page,clicks,impressions,ctr,position\nrandom video chat,https://kokomeet.live/,3,40,7.5%,8\nunrelated hobby,,0,2,0%,40\n");
        $this->artisan('seo:queries', ['csv' => $csv])->assertSuccessful();
        $report = file_get_contents(storage_path('app/seo/query-report.md'));
        $this->assertStringContainsString('Map to /random-video-chat.', $report);
        $this->assertStringContainsString('Do not create a page yet.', $report);
        @unlink($csv);
    }

    public function test_admin_can_publish_and_redirects_do_not_loop(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/seo')->assertForbidden();
        $this->actingAs($admin)->get('/admin/seo')->assertOk()->assertSee('Indexable URLs');
        $this->actingAs($admin)->get('/admin/seo/settings')->assertOk();
        $this->actingAs($admin)->get('/admin/content')->assertOk();
        $this->actingAs($admin)->get('/admin/media')->assertOk();
        $this->actingAs($admin)->get('/admin/redirects')->assertOk();
        $this->actingAs($admin)->get('/admin/navigation')->assertOk();

        $this->actingAs($admin)->post('/admin/content', [
            'type' => 'page',
            'title' => 'A retired note',
            'slug' => 'retired-note',
            'body' => 'This page explains a removed experiment and should stay out of search.',
            'excerpt' => 'A short note that is not meant to be indexed.',
            'status' => 'published',
            'robots_index' => '0',
            'robots_follow' => '1',
        ])->assertRedirect();

        $page = Content::query()->where('slug', 'retired-note')->first();
        $this->assertNotNull($page);
        $this->assertFalse($page->robots_index);
        $this->get('/retired-note')->assertOk()->assertSee('noindex', false);
        $this->get('/sitemap.xml')->assertDontSee(url('/retired-note'), false);

        $this->actingAs($admin)->post('/admin/redirects', [
            'from_path' => '/retired-note',
            'to_path' => '/about',
            'status_code' => '301',
            'enabled' => '1',
        ])->assertRedirect();
        $this->get('/retired-note')->assertRedirect('/about');

        $this->actingAs($admin)->post('/admin/redirects', [
            'from_path' => '/about',
            'to_path' => '/retired-note',
            'status_code' => '301',
            'enabled' => '1',
        ])->assertSessionHasErrors('to_path');

        SeoRedirect::query()->create([
            'from_path' => '/gone-page',
            'to_path' => null,
            'status_code' => 410,
            'enabled' => true,
        ]);
        SeoRedirect::forgetCache();
        $this->get('/gone-page')->assertStatus(410);
    }
}
