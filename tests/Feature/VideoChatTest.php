<?php

namespace Tests\Feature;

use App\Models\MatchMessage;
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
        $this->postJson('/match/messages', ['token' => 'x', 'client_id' => 'x', 'body' => 'hi'])->assertUnauthorized();
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
            ->assertDontSee('turn-secret-value', false)
            ->assertSee('id="quality"', false)
            ->assertSee('id="cam-device"', false)
            ->assertSee('id="remote"', false)
            ->assertSee('room-state.js', false)
            ->assertSee('id="chat-toggle"', false)
            ->assertSee('id="chat-log"', false)
            ->assertSee('chat.js', false)
            ->assertSee($user->name)
            ->assertDontSee('end-to-end', false)
            ->assertDontSee('Find Another')
            ->assertDontSee('Saved with this match', false)
            ->assertDontSee('id="camera"', false);
    }

    public function test_match_chat_is_isolated_ordered_and_closed_when_the_match_ends(): void
    {
        $alone = User::factory()->create();
        $this->actingAs($alone)->postJson('/match/find', ['token' => 'alone'])->assertJsonPath('status', 'waiting');
        $this->actingAs($alone)->postJson('/match/messages', [
            'token' => 'alone',
            'client_id' => 'alone-1',
            'body' => 'too early',
        ])->assertStatus(409);
        $this->actingAs($alone)->postJson('/match/end', ['token' => 'alone'])->assertOk();

        [$a, $b] = $this->pair();
        $this->actingAs($a)->postJson('/match/messages', [
            'token' => 'token-a',
            'client_id' => 'msg-1',
            'body' => 'hello',
        ])->assertCreated()->assertJsonPath('message.body', 'hello')->assertJsonPath('message.mine', true);

        $this->actingAs($a)->postJson('/match/messages', [
            'token' => 'token-a',
            'client_id' => 'msg-1',
            'body' => 'hello again',
        ])->assertOk()->assertJsonPath('message.body', 'hello');
        $this->assertSame(1, MatchMessage::query()->count());

        $this->actingAs($a)->postJson('/match/messages', [
            'token' => 'token-a',
            'client_id' => 'msg-2',
            'body' => 'second',
        ])->assertCreated();

        $this->actingAs($b)->getJson('/match/messages?token=token-b&after=0')
            ->assertOk()
            ->assertJsonPath('messages.0.body', 'hello')
            ->assertJsonPath('messages.1.body', 'second')
            ->assertJsonPath('messages.0.mine', false);

        $stranger = User::factory()->create();
        $this->actingAs($stranger)->getJson('/match/messages?token=token-a')->assertNotFound();
        $this->actingAs($stranger)->postJson('/match/messages', [
            'token' => 'token-b',
            'client_id' => 'nope',
            'body' => 'nope',
        ])->assertNotFound();

        $this->actingAs($a)->postJson('/match/messages', [
            'token' => 'token-a',
            'client_id' => 'long',
            'body' => str_repeat('a', 501),
        ])->assertStatus(422);

        $this->actingAs($a)->postJson('/match/end', ['token' => 'token-a'])->assertOk();
        $this->actingAs($b)->postJson('/match/messages', [
            'token' => 'token-b',
            'client_id' => 'after-end',
            'body' => 'too late',
        ])->assertStatus(409);
        $this->actingAs($b)->getJson('/match/messages?token=token-b')->assertStatus(409);
        $this->assertSame(2, MatchMessage::query()->count());

        $this->actingAs($a)->postJson('/match/next', [
            'token' => 'token-a',
            'next_token' => 'token-a2',
        ])->assertJsonPath('status', 'waiting');
        $this->actingAs($a)->getJson('/match/messages?token=token-a2')->assertStatus(409);
        $this->assertSame(0, MatchMessage::query()->whereHas('match', fn ($query) => $query->where('user_one_token', 'token-a2'))->count());
    }

    public function test_chat_rate_limit_is_enforced(): void
    {
        config(['platform.limits.chat' => 1]);
        [$a] = $this->pair();

        $this->actingAs($a)->postJson('/match/messages', [
            'token' => 'token-a',
            'client_id' => 'rate-1',
            'body' => 'one',
        ])->assertCreated();
        $this->actingAs($a)->postJson('/match/messages', [
            'token' => 'token-a',
            'client_id' => 'rate-2',
            'body' => 'two',
        ])->assertStatus(429);
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
            ->assertJsonPath('role', 'answerer')
            ->assertJsonPath('partner.name', $a->name);

        return [$a, $b];
    }
}
