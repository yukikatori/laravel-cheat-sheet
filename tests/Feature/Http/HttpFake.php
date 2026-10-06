<?php

// 本当に外部 API へ通信しないようにして、
// テスト内で「この URL にはこのレスポンスが返ったことにする」と指定

declare(strict_types=1);

namespace Tests\Feature\Http\Settings;

use App\Models\GoogleCalendarConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleCalendarControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google_calendar.client_id' => 'test-client-id',
            'services.google_calendar.client_secret' => 'test-client-secret',
            'services.google_calendar.redirect_uri' => 'http://localhost/settings/google-calendar/callback',
        ]);
    }

    public function test_connect_redirects_coach_to_google_oauth_and_stores_state(): void
    {
        $coach = User::factory()->coach()->create();

        $response = $this->actingAs($coach)->get(route('settings.google-calendar.redirect', [
            'redirect_path' => '/settings/availability',
        ]));

        $response->assertRedirect();
        $response->assertSessionHas('google_calendar_oauth_state');
        $response->assertSessionHas('google_calendar_redirect_path', '/settings/availability');

        $location = $response->headers->get('Location');
        $this->assertIsString($location);
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $location);

        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertSame('test-client-id', $query['client_id']);
        $this->assertSame('http://localhost/settings/google-calendar/callback', $query['redirect_uri']);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('offline', $query['access_type']);
        $this->assertSame('consent', $query['prompt']);
        $this->assertSame($this->app['session']->get('google_calendar_oauth_state'), $query['state']);
        $this->assertStringContainsString('https://www.googleapis.com/auth/calendar', $query['scope']);
        $this->assertStringContainsString('https://www.googleapis.com/auth/userinfo.email', $query['scope']);
        $this->assertStringContainsString('https://www.googleapis.com/auth/userinfo.profile', $query['scope']);
    }

    public function test_connect_sanitizes_untrusted_redirect_path(): void
    {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)->get(route('settings.google-calendar.redirect', [
            'redirect_path' => 'https://evil.example/phishing',
        ]));

        $this->assertSame('/settings/availability', session('google_calendar_redirect_path'));
    }

    public function test_non_coach_cannot_connect(): void
    {
        $student = User::factory()->student()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($student)
            ->get(route('settings.google-calendar.redirect'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('settings.google-calendar.redirect'))
            ->assertForbidden();
    }

    public function test_non_coach_cannot_callback_or_destroy(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->get(route('settings.google-calendar.callback', [
                'state' => 'valid-state',
                'code' => 'authorization-code',
            ]))
            ->assertForbidden();

        $this->actingAs($student)
            ->delete(route('settings.google-calendar.destroy'))
            ->assertForbidden();
    }

    public function test_callback_creates_google_calendar_connection_when_state_is_valid(): void
    {
        $coach = User::factory()->coach()->create();

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'new-access-token',
                'refresh_token' => 'new-refresh-token',
                'expires_in' => 3600,
            ]),
            'https://www.googleapis.com/oauth2/v2/userinfo' => Http::response([
                'id' => 'google-user-123',
                'email' => 'coach.google@example.com',
            ]),
        ]);

        $response = $this
            ->actingAs($coach)
            ->withSession([
                'google_calendar_oauth_state' => 'valid-state',
                'google_calendar_redirect_path' => '/settings/availability',
            ])
            ->get(route('settings.google-calendar.callback', [
                'state' => 'valid-state',
                'code' => 'authorization-code',
            ]));

        $response->assertRedirect('/settings/availability');
        $response->assertSessionHas('success', 'Googleカレンダーと連携しました。');

        $this->assertDatabaseHas('google_calendar_connections', [
            'coach_id' => $coach->id,
            'google_account_id' => 'google-user-123',
            'google_email' => 'coach.google@example.com',
            'access_token' => 'new-access-token',
            'refresh_token' => 'new-refresh-token',
        ]);
    }

    public function test_callback_rejects_invalid_state(): void
    {
        $coach = User::factory()->coach()->create();
        Http::fake();

        $response = $this
            ->actingAs($coach)
            ->withSession([
                'google_calendar_oauth_state' => 'expected-state',
                'google_calendar_redirect_path' => '/settings/availability',
            ])
            ->get(route('settings.google-calendar.callback', [
                'state' => 'wrong-state',
                'code' => 'authorization-code',
            ]));

        $response->assertRedirect('/settings/availability');
        $response->assertSessionHas('error', 'Googleカレンダー連携の検証に失敗しました。もう一度お試しください。');
        $this->assertDatabaseCount('google_calendar_connections', 0);
        Http::assertNothingSent();
    }

    public function test_callback_handles_google_oauth_cancel(): void
    {
        $coach = User::factory()->coach()->create();
        Http::fake();

        $response = $this
            ->actingAs($coach)
            ->withSession([
                'google_calendar_oauth_state' => 'valid-state',
                'google_calendar_redirect_path' => '/settings/availability',
            ])
            ->get(route('settings.google-calendar.callback', [
                'state' => 'valid-state',
                'error' => 'access_denied',
            ]));

        $response->assertRedirect('/settings/availability');
        $response->assertSessionHas('error', 'Googleカレンダー連携がキャンセルされました。');
        $this->assertDatabaseCount('google_calendar_connections', 0);
        Http::assertNothingSent();
    }

    public function test_callback_does_not_create_connection_when_token_exchange_fails(): void
    {
        $coach = User::factory()->coach()->create();

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant'], 400),
        ]);

        $response = $this
            ->actingAs($coach)
            ->withSession([
                'google_calendar_oauth_state' => 'valid-state',
                'google_calendar_redirect_path' => '/settings/availability',
            ])
            ->get(route('settings.google-calendar.callback', [
                'state' => 'valid-state',
                'code' => 'authorization-code',
            ]));

        $response->assertRedirect('/settings/availability');
        $response->assertSessionHas('error', 'Googleカレンダー連携に失敗しました。時間をおいて再度お試しください。');
        $this->assertDatabaseCount('google_calendar_connections', 0);
    }

    public function test_callback_keeps_existing_refresh_token_when_google_omits_new_one(): void
    {
        $coach = User::factory()->coach()->create();

        GoogleCalendarConnection::query()->create([
            'coach_id' => $coach->id,
            'google_account_id' => 'old-google-user',
            'google_email' => 'old.google@example.com',
            'access_token' => 'old-access-token',
            'refresh_token' => 'keep-this-refresh-token',
            'token_expires_at' => now()->subHour(),
            'connected_at' => now()->subDay(),
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'updated-access-token',
                'expires_in' => 3600,
            ]),
            'https://www.googleapis.com/oauth2/v2/userinfo' => Http::response([
                'id' => 'updated-google-user',
                'email' => 'updated.google@example.com',
            ]),
        ]);

        $this
            ->actingAs($coach)
            ->withSession([
                'google_calendar_oauth_state' => 'valid-state',
                'google_calendar_redirect_path' => '/settings/availability',
            ])
            ->get(route('settings.google-calendar.callback', [
                'state' => 'valid-state',
                'code' => 'authorization-code',
            ]))
            ->assertRedirect('/settings/availability');

        $this->assertDatabaseHas('google_calendar_connections', [
            'coach_id' => $coach->id,
            'google_account_id' => 'updated-google-user',
            'google_email' => 'updated.google@example.com',
            'access_token' => 'updated-access-token',
            'refresh_token' => 'keep-this-refresh-token',
        ]);
    }

    public function test_callback_succeeds_even_when_userinfo_fails(): void
    {
        $coach = User::factory()->coach()->create();

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'new-access-token',
                'refresh_token' => 'new-refresh-token',
                'expires_in' => 3600,
            ]),
            'https://www.googleapis.com/oauth2/v2/userinfo' => Http::response(['error' => 'unavailable'], 500),
        ]);

        $this
            ->actingAs($coach)
            ->withSession([
                'google_calendar_oauth_state' => 'valid-state',
                'google_calendar_redirect_path' => '/settings/availability',
            ])
            ->get(route('settings.google-calendar.callback', [
                'state' => 'valid-state',
                'code' => 'authorization-code',
            ]))
            ->assertRedirect('/settings/availability')
            ->assertSessionHas('success', 'Googleカレンダーと連携しました。');

        $this->assertDatabaseHas('google_calendar_connections', [
            'coach_id' => $coach->id,
            'google_account_id' => null,
            'google_email' => null,
            'access_token' => 'new-access-token',
            'refresh_token' => 'new-refresh-token',
        ]);
    }

    public function test_destroy_removes_connection_and_redirects(): void
    {
        $coach = User::factory()->coach()->create();
        GoogleCalendarConnection::query()->create([
            'coach_id' => $coach->id,
            'google_account_id' => 'google-user-123',
            'google_email' => 'coach.google@example.com',
            'access_token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'token_expires_at' => now()->addHour(),
            'connected_at' => now(),
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/revoke' => Http::response([], 200),
        ]);

        $response = $this->actingAs($coach)
            ->delete(route('settings.google-calendar.destroy'));

        $response->assertRedirect(route('settings.availability.index'));
        $response->assertSessionHas('success', 'Googleカレンダー連携を解除しました。');
        $this->assertDatabaseMissing('google_calendar_connections', [
            'coach_id' => $coach->id,
        ]);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://oauth2.googleapis.com/revoke'
            && $request['token'] === 'refresh-token');
    }

    public function test_destroy_removes_connection_even_when_revoke_fails(): void
    {
        $coach = User::factory()->coach()->create();
        GoogleCalendarConnection::query()->create([
            'coach_id' => $coach->id,
            'google_account_id' => 'google-user-123',
            'google_email' => 'coach.google@example.com',
            'access_token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'token_expires_at' => now()->addHour(),
            'connected_at' => now(),
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/revoke' => Http::response(['error' => 'temporarily_unavailable'], 500),
        ]);

        $this->actingAs($coach)
            ->delete(route('settings.google-calendar.destroy'))
            ->assertRedirect(route('settings.availability.index'));

        $this->assertDatabaseMissing('google_calendar_connections', [
            'coach_id' => $coach->id,
        ]);
    }
}
