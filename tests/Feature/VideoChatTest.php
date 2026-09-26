<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VideoMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_sign_in_is_google_only(): void
    {
        $this->get('/login')->assertOk()->assertSee('Continue with Google')->assertDontSee('Forgot password');
        $this->get('/register')->assertNotFound();
        $this->get('/forgot-password')->assertNotFound();
        $this->post('/login')->assertMethodNotAllowed();
    }

    public function test_login_and_logout(): void
    {
        $user = User::factory()->create(['name' => 'Sam']);

        $this->actingAs($user)->get('/home')->assertOk()->assertSee('Welcome, '.$user->name)->assertSee('Find Stranger');
        $this->actingAs($user)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_guests_cannot_match_or_open_video(): void
    {
        $this->get('/')->assertOk()->assertSee('Start Video Chat');
        $this->get('/video')->assertRedirect('/login');
        $this->postJson('/match/find', ['token' => 'x'])->assertUnauthorized();
    }

    public function test_two_users_match_and_exchange_signals_without_trusting_client_ids(): void
    {
        [$a, $b] = $this->pair();

        $this->actingAs($a)->postJson('/match/signal', [
            'token' => 'token-a',
            'type' => 'offer',
            'user_id' => $b->id,
            'match_id' => 999,
            'payload' => ['type' => 'offer', 'sdp' => 'v=offer'],
        ])->assertOk();

        $this->actingAs($b)->postJson('/match/signal', [
            'token' => 'token-b',
            'type' => 'offer',
            'payload' => ['type' => 'offer', 'sdp' => 'nope'],
        ])->assertForbidden();

        $this->actingAs($b)->getJson('/match/poll?token=token-b&cursor=0')
            ->assertOk()
            ->assertJsonPath('role', 'answerer')
            ->assertJsonPath('offer.sdp', 'v=offer');

        $this->actingAs($b)->postJson('/match/signal', [
            'token' => 'token-b',
            'type' => 'answer',
            'payload' => ['type' => 'answer', 'sdp' => 'v=answer'],
        ])->assertOk();

        $this->actingAs($a)->postJson('/match/signal', [
            'token' => 'token-a',
            'type' => 'candidate',
            'payload' => ['candidate' => 'candidate:a', 'sdpMid' => '0', 'sdpMLineIndex' => 0],
        ])->assertOk();

        $this->actingAs($b)->getJson('/match/poll?token=token-b&cursor=0')
            ->assertJsonPath('candidates.0.candidate', 'candidate:a')
            ->assertJsonPath('answer', null);

        $this->actingAs($a)->getJson('/match/poll?token=token-a&cursor=0')
            ->assertJsonPath('answer.sdp', 'v=answer')
            ->assertJsonPath('candidates', []);

        $match = VideoMatch::query()->first();
        $this->assertSame($a->id, $match->user_one_id);
        $this->assertSame($b->id, $match->user_two_id);
        $this->assertSame(1, VideoMatch::query()->count());
    }

    public function test_a_user_is_not_matched_with_themselves_and_has_one_active_match(): void
    {
        $a = User::factory()->create();

        $this->actingAs($a)->postJson('/match/find', ['token' => 'token-a'])->assertJsonPath('status', 'waiting');
        $this->actingAs($a)->postJson('/match/find', ['token' => 'token-a'])->assertJsonPath('status', 'waiting');
        $this->assertSame(1, VideoMatch::query()->active()->count());

        $this->actingAs($a)->postJson('/match/find', ['token' => 'token-a-2'])->assertJsonPath('status', 'waiting');
        $this->assertSame(1, VideoMatch::query()->active()->count());
        $this->assertSame('disconnect', VideoMatch::query()->where('status', 'ended')->value('end_reason'));
    }

    public function test_next_end_leave_and_logout_notify_the_partner(): void
    {
        [$a, $b] = $this->pair();

        $this->actingAs($a)->postJson('/match/next', [
            'token' => 'token-a',
            'next_token' => 'token-a-next',
        ])->assertJsonPath('status', 'waiting');
        $this->actingAs($b)->getJson('/match/poll?token=token-b&cursor=0')
            ->assertJsonPath('status', 'ended')
            ->assertJsonPath('end_reason', 'next');

        $this->actingAs($b)->postJson('/match/find', ['token' => 'token-b-2'])->assertJsonPath('status', 'connecting');
        $this->actingAs($a)->postJson('/match/end', ['token' => 'token-a-next'])->assertJsonPath('end_reason', 'end');
        $this->actingAs($b)->getJson('/match/poll?token=token-b-2&cursor=0')->assertJsonPath('end_reason', 'end');

        $this->actingAs($a)->postJson('/match/find', ['token' => 'token-a-3'])->assertJsonPath('status', 'waiting');
        $this->actingAs($a)->postJson('/match/leave', ['token' => 'token-a-3']);
        $this->actingAs($b)->postJson('/match/find', ['token' => 'token-b-3'])->assertJsonPath('status', 'waiting');

        $c = User::factory()->create();
        $this->actingAs($b)->postJson('/match/leave', ['token' => 'token-b-3']);
        $this->actingAs($a)->postJson('/match/find', ['token' => 'left-a']);
        $this->actingAs($c)->postJson('/match/find', ['token' => 'left-c'])->assertJsonPath('status', 'connecting');
        $this->actingAs($a)->post('/logout')->assertRedirect('/login');
        $this->actingAs($c)->getJson('/match/poll?token=left-c&cursor=0')
            ->assertJsonPath('status', 'ended')
            ->assertJsonPath('end_reason', 'disconnect');
    }

    public function test_stale_partner_is_disconnected(): void
    {
        [$a] = $this->pair();

        $match = VideoMatch::query()->first();
        $match->user_two_seen_at = now()->subSeconds(30);
        $match->save();

        $this->actingAs($a)->getJson('/match/poll?token=token-a&cursor=0')
            ->assertJsonPath('status', 'ended')
            ->assertJsonPath('end_reason', 'disconnect');
    }

    public function test_turn_secret_is_hidden_until_turn_is_configured(): void
    {
        config([
            'webrtc.stun_urls' => ['stun:stun.l.google.com:19302'],
            'webrtc.turn.url' => '',
            'webrtc.turn.credential' => 'turn-secret-value',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->get('/video')
            ->assertOk()
            ->assertSee('stun:stun.l.google.com:19302', false)
            ->assertDontSee('turn-secret-value', false);
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function pair(): array
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        $this->actingAs($a)->postJson('/match/find', ['token' => 'token-a'])->assertJsonPath('status', 'waiting');
        $this->actingAs($b)->postJson('/match/find', ['token' => 'token-b'])
            ->assertJsonPath('status', 'connecting')
            ->assertJsonPath('role', 'answerer');

        return [$a, $b];
    }
}
