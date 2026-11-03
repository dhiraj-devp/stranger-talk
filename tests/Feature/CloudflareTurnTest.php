<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CloudflareTurnTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_request_ice_servers(): void
    {
        $this->getJson('/api/webrtc/ice-servers')->assertUnauthorized();
    }

    public function test_missing_cloudflare_configuration_does_not_call_cloudflare(): void
    {
        config([
            'services.cloudflare_turn.key_id' => '',
            'services.cloudflare_turn.api_token' => '',
        ]);
        Http::fake();

        $this->actingAs(User::factory()->create())
            ->getJson('/api/webrtc/ice-servers')
            ->assertStatus(503)
            ->assertJsonPath('message', 'Video relay is not configured.')
            ->assertDontSee('secret-token-value', false);

        Http::assertNothingSent();
    }

    public function test_a_cloudflare_error_is_hidden_from_the_browser(): void
    {
        config([
            'services.cloudflare_turn.key_id' => 'key-123',
            'services.cloudflare_turn.api_token' => 'secret-token-value',
        ]);
        Http::fake([
            'rtc.live.cloudflare.com/*' => Http::response([
                'error' => 'unauthorized',
                'token' => 'secret-token-value',
            ], 401),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson('/api/webrtc/ice-servers')
            ->assertStatus(502)
            ->assertJsonPath('message', 'Video relay is unavailable. Try again in a moment.')
            ->assertDontSee('secret-token-value', false);

        Http::assertSentCount(1);
    }

    public function test_a_cloudflare_timeout_is_a_safe_error(): void
    {
        config([
            'services.cloudflare_turn.key_id' => 'key-123',
            'services.cloudflare_turn.api_token' => 'secret-token-value',
        ]);
        Http::fake(function () {
            throw new ConnectionException('timed out');
        });

        $this->actingAs(User::factory()->create())
            ->getJson('/api/webrtc/ice-servers')
            ->assertStatus(502)
            ->assertJsonPath('message', 'Video relay is unavailable. Try again in a moment.')
            ->assertDontSee('secret-token-value', false);
    }

    public function test_fresh_cloudflare_ice_servers_are_returned_without_the_api_token(): void
    {
        config([
            'services.cloudflare_turn.key_id' => 'key-123',
            'services.cloudflare_turn.api_token' => 'secret-token-value',
        ]);
        Http::fake([
            'rtc.live.cloudflare.com/*' => Http::response([
                'iceServers' => [
                    'urls' => [
                        'stun:stun.cloudflare.com:3478',
                        'turn:turn.cloudflare.com:3478?transport=udp',
                    ],
                    'username' => 'temp-user',
                    'credential' => 'temp-pass',
                ],
            ]),
        ]);

        $this->actingAs(User::factory()->create())
            ->getJson('/api/webrtc/ice-servers')
            ->assertOk()
            ->assertJsonPath('iceServers.0.username', 'temp-user')
            ->assertJsonPath('iceServers.0.credential', 'temp-pass')
            ->assertJsonPath('iceServers.0.urls.1', 'turn:turn.cloudflare.com:3478?transport=udp')
            ->assertDontSee('secret-token-value', false);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://rtc.live.cloudflare.com/v1/turn/keys/key-123/credentials/generate-ice-servers'
                && $request->hasHeader('Authorization', 'Bearer secret-token-value')
                && $request['ttl'] === 86400;
        });
    }
}
