<?php

declare(strict_types=1);

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

test('password update screen is rendered', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('password.edit'));

    $response->assertStatus(200)->assertInertia(fn(Assert $page): Illuminate\Testing\Fluent\AssertableJson => $page
        ->component('settings/Password')
    );
});

test('password can be updated', function (): void {
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

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('correct password must be provided to update password', function (): void {
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
});

it('throws a validation exception when too many login attempts occur', function (): void {
    $request = new LoginRequest();

    $throttleKey = $request->throttleKey();

    foreach (range(1, 5) as $_) {
        RateLimiter::hit($throttleKey);
    }

    expect(fn() => $request->ensureIsNotRateLimited())
        ->toThrow(ValidationException::class, trans('auth.throttle', [
            'seconds' => RateLimiter::availableIn($throttleKey),
            'minutes' => ceil(RateLimiter::availableIn($throttleKey) / 60),
        ]));

})->beforeEach(fn() => RateLimiter::clear(new LoginRequest()->throttleKey()))
    ->afterEach(fn() => RateLimiter::clear(new LoginRequest()->throttleKey()));
