<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_update_screen_is_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('password.edit'));

        $response->assertStatus(200)->assertInertia(fn(Assert $page) => $page
            ->component('settings/Password')
        );
    }

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/password')
            ->put('/settings/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/password');

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/password')
            ->put('/settings/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasErrors('current_password')
            ->assertRedirect('/settings/password');
    }

    public function test_validation_exception_thrown_when_too_many_login_attempts_occur(): void
    {
        $request = new LoginRequest();

        $throttleKey = $request->throttleKey();

        RateLimiter::clear($throttleKey);

        foreach (range(1, 5) as $_) {
            RateLimiter::hit($throttleKey);
        }

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(trans('auth.throttle', [
            'seconds' => RateLimiter::availableIn($throttleKey),
            'minutes' => ceil(RateLimiter::availableIn($throttleKey) / 60),
        ]));

        $request->ensureIsNotRateLimited();

        RateLimiter::clear($throttleKey);
    }
}
