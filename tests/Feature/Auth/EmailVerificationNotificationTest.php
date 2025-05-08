<?php

declare(strict_types=1);
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

it('redirects to dashboard if the user has verified email', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('dashboard'));
});

it('sends email verification notification if user is not verified', function (): void {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect();

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('returns a flash message when verification link is sent', function (): void {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect()
        ->assertSessionHas('status', 'verification-link-sent');
});
