<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirects_to_dashboard_if_user_has_verified_email(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('verification.send'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_sends_email_verification_notification_if_user_is_not_verified(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)
            ->post(route('verification.send'));

        $response->assertRedirect();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_returns_flash_message_when_verification_link_is_sent(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)
            ->post(route('verification.send'));

        $response->assertRedirect()
            ->assertSessionHas('status', 'verification-link-sent');
    }
}
