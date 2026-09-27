<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Block;
use App\Models\MatchPreference;
use App\Models\Report;
use App\Models\User;
use App\Models\VideoMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    private ?SocialiteUser $googleUser = null;

    private ?\Throwable $googleError = null;

    protected function setUp(): void
    {
        parent::setUp();

        $provider = \Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
        $provider->shouldReceive('scopes')->andReturnSelf();
        $provider->shouldReceive('redirect')->andReturn(redirect('https://accounts.google.com/o/oauth2/v2/auth'));
        $provider->shouldReceive('user')->andReturnUsing(function () {
            if ($this->googleError) {
                throw $this->googleError;
            }

            return $this->googleUser;
        });
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_profile_setup_and_preferences_do_not_change_matching(): void
    {
        $user = User::factory()->incomplete()->create();
        $this->actingAs($user)->get('/home')->assertRedirect('/profile/setup');
        $this->actingAs($user)->withHeader('CF-IPCountry', 'IN')->get('/profile/setup')
            ->assertOk()
            ->assertSee('India');
        $this->actingAs($user)->post('/profile/setup', [
            'gender' => 'male',
            'country_code' => 'IN',
        ])->assertRedirect('/home');
        $user->refresh();
        $this->assertSame('male', $user->gender);
        $this->assertSame('IN', $user->country_code);
        $this->assertFalse($user->hasFeature('gender_filter'));

        $this->actingAs($user)->post('/preferences', [
            'gender_preference' => 'female',
            'country_preference' => 'GB',
        ])->assertRedirect();
        $this->assertSame('GB', $user->matchPreference()->first()->country_preference);

        $other = User::factory()->create();
        MatchPreference::query()->create([
            'user_id' => $other->id,
            'gender_preference' => 'male',
            'country_preference' => 'JP',
        ]);
        $this->actingAs($user)->postJson('/match/find', ['token' => 'pref-a'])->assertJsonPath('status', 'waiting');
        $this->actingAs($other)->postJson('/match/find', ['token' => 'pref-b'])->assertJsonPath('status', 'connecting');
    }

    public function test_google_redirects_when_configured_and_explains_when_not(): void
    {
        $this->get('/auth/google')->assertRedirect('/login');

        $this->fakeGoogle();
        $this->get('/auth/google')->assertRedirect('https://accounts.google.com/o/oauth2/v2/auth');
    }

    public function test_google_creates_logs_in_and_links_only_verified_accounts(): void
    {
        $this->fakeGoogle([
            'id' => 'gid-1',
            'email' => 'new@example.com',
            'name' => 'New',
            'avatar' => 'https://example.com/a.png',
        ], ['email_verified' => true]);

        $this->get('/login');
        $before = session()->getId();
        $this->get('/auth/google/callback')->assertRedirect('/profile/setup');
        $this->assertAuthenticated();
        $this->assertNotSame($before, session()->getId());
        $created = User::query()->where('email', 'new@example.com')->first();
        $this->assertSame('gid-1', $created->google_id);
        $this->assertNotNull($created->email_verified_at);

        auth()->logout();
        $this->fakeGoogle([
            'id' => 'gid-1',
            'email' => 'new@example.com',
            'name' => 'New',
        ], ['email_verified' => true]);
        $this->get('/auth/google/callback')->assertRedirect('/profile/setup');
        $this->assertSame(1, User::query()->where('google_id', 'gid-1')->count());
        $created->forceFill(['gender' => 'female', 'country_code' => 'CA', 'avatar' => 'https://example.com/mine.png'])->save();
        auth()->logout();
        $this->fakeGoogle([
            'id' => 'gid-1',
            'email' => 'new@example.com',
            'name' => 'New',
            'avatar' => 'https://example.com/other.png',
        ], ['email_verified' => true]);
        $this->get('/auth/google/callback')->assertRedirect('/home');
        $this->assertSame('https://example.com/mine.png', $created->fresh()->avatar);
        $this->assertSame('female', $created->fresh()->gender);

        $local = User::factory()->create([
            'email' => 'local@example.com',
            'email_verified_at' => now(),
            'password' => 'password12',
        ]);
        $hash = $local->password;
        auth()->logout();
        $this->fakeGoogle([
            'id' => 'gid-2',
            'email' => 'local@example.com',
            'name' => 'Local',
        ], ['email_verified' => true]);
        $this->get('/auth/google/callback')->assertRedirect('/home');
        $local->refresh();
        $this->assertSame('gid-2', $local->google_id);
        $this->assertTrue(Hash::check('password12', $local->password));
        $this->assertSame($hash, $local->password);
    }

    public function test_google_refuses_unverified_email_and_account_merges(): void
    {
        $this->fakeGoogle([
            'id' => 'gid-x',
            'email' => 'fresh@example.com',
            'name' => 'Fresh',
        ], ['email_verified' => false]);
        $this->get('/auth/google/callback')->assertRedirect('/login')->assertSessionHasErrors('google');
        $this->assertGuest();

        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $owner->forceFill(['google_id' => 'gid-owned'])->save();
        $other = User::factory()->create([
            'email' => 'other@example.com',
            'email_verified_at' => now(),
        ]);
        $this->fakeGoogle([
            'id' => 'gid-owned',
            'email' => 'other@example.com',
            'name' => 'Other',
        ], ['email_verified' => true]);
        $this->get('/auth/google/callback')->assertRedirect('/home');
        $this->assertAuthenticatedAs($owner);
        $other->refresh();
        $this->assertNull($other->google_id);
    }

    public function test_google_callback_failure_stays_generic(): void
    {
        $this->fakeGoogle([], [], new \Exception('secret-token-should-not-leak'));
        $this->get('/auth/google/callback')->assertRedirect('/login');
        $this->assertStringNotContainsString('secret-token', (string) session('errors')?->first('google'));
    }

    public function test_blocks_and_bans_are_enforced(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        Block::query()->create(['blocker_id' => $a->id, 'blocked_id' => $b->id]);

        $this->actingAs($a)->postJson('/match/find', ['token' => 'a'])->assertJsonPath('status', 'waiting');
        $this->actingAs($b)->postJson('/match/find', ['token' => 'b'])->assertJsonPath('status', 'waiting');
        $this->assertSame(2, VideoMatch::query()->where('status', 'waiting')->count());

        $c = User::factory()->create();
        $this->actingAs($c)->postJson('/match/find', ['token' => 'c'])->assertJsonPath('status', 'connecting');

        $b->forceFill(['status' => User::BANNED, 'banned_until' => null, 'ban_reason' => 'abuse'])->save();
        $this->actingAs($b)->postJson('/match/find', ['token' => 'banned'])->assertForbidden();
        $this->actingAs($b)->get('/video')->assertRedirect('/banned');
    }

    public function test_report_block_and_admin_authorization(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($a)->postJson('/match/find', ['token' => 'ra'])->assertOk();
        $this->actingAs($b)->postJson('/match/find', ['token' => 'rb'])->assertOk();

        $this->actingAs($a)->postJson('/match/report', [
            'token' => 'ra',
            'reason' => 'spam',
            'description' => 'ads',
        ])->assertOk();
        $this->assertSame(1, Report::query()->count());
        $this->actingAs($a)->postJson('/match/report', [
            'token' => 'ra',
            'reason' => 'spam',
        ])->assertOk();
        $this->assertSame(1, Report::query()->count());

        $this->actingAs($a)->postJson('/match/block', ['token' => 'ra'])->assertOk();
        $this->assertTrue(Block::query()->where('blocker_id', $a->id)->where('blocked_id', $b->id)->exists());
        $this->actingAs($b)->getJson('/match/poll?token=rb&cursor=0')->assertJsonPath('end_reason', 'end');

        $this->actingAs($a)->get('/admin')->assertForbidden();
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Pending reports');
        foreach (['/admin/live', '/admin/users', '/admin/bans', '/admin/matches', '/admin/audit', '/admin/health', '/admin/reports'] as $path) {
            $this->actingAs($a)->get($path)->assertForbidden();
            $this->actingAs($admin)->get($path)->assertOk();
        }
        auth()->logout();
        $this->get('/admin')->assertRedirect('/login');
        $this->assertSame(url('/admin'), session('url.intended'));
        $report = Report::query()->first();
        $this->actingAs($admin)->post('/admin/reports/'.$report->id, [
            'status' => 'resolved',
            'moderator_notes' => 'removed',
        ])->assertRedirect();
        $this->assertSame('resolved', $report->fresh()->status);
        $this->assertTrue(AuditLog::query()->where('action', 'resolve_report')->exists());

        $this->actingAs($admin)->post('/admin/users/'.$b->id.'/ban', [
            'reason' => 'spam',
            'permanent' => '1',
        ])->assertRedirect();
        $this->assertTrue($b->fresh()->isBanned());
    }

    public function test_google_start_is_rate_limited(): void
    {
        config([
            'platform.limits.google' => 1,
            'services.google.client_id' => 'test-client',
            'services.google.client_secret' => 'test-secret',
            'services.google.redirect' => 'http://127.0.0.1:8010/auth/google/callback',
        ]);

        $this->get('/auth/google')->assertRedirect('https://accounts.google.com/o/oauth2/v2/auth');
        $this->get('/auth/google')->assertStatus(429);
    }

    public function test_health_hides_secrets(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertJsonPath('database', 'ok')
            ->assertJsonPath('redis', 'disabled')
            ->assertJsonPath('reverb', 'not_configured')
            ->assertDontSee('password', false);
    }

    public function test_cleanup_expires_stale_waiting_matches(): void
    {
        $user = User::factory()->create();
        $match = VideoMatch::query()->create([
            'user_one_id' => $user->id,
            'user_one_token' => 'old',
            'user_one_seen_at' => now()->subMinutes(5),
            'status' => VideoMatch::WAITING,
            'signal' => [],
        ]);

        $this->artisan('platform:cleanup')->assertSuccessful();
        $this->assertSame(VideoMatch::EXPIRED, $match->fresh()->status);
    }

    private function fakeGoogle(array $map = [], array $raw = [], ?\Throwable $error = null): void
    {
        config([
            'services.google.client_id' => 'test-client',
            'services.google.client_secret' => 'test-secret',
            'services.google.redirect' => 'http://127.0.0.1:8010/auth/google/callback',
        ]);

        $this->googleError = $error;
        $this->googleUser = $error ? null : (new SocialiteUser)->setRaw($raw)->map($map);
    }
}
