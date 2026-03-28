<?php

use App\Models\User;
use Laravel\Fortify\Features;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

test('user is redirected to google', function () {
    Socialite::fake('google');

    $response = $this->get(route('social.redirect', 'google'));

    $response->assertRedirect();
});

test('new user is created from google login', function () {
    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-123',
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]));

    $response = $this->get(route('social.callback', 'google'));

    $response->assertRedirect(config('fortify.home'));
    $this->assertAuthenticated();

    $this->assertDatabaseHas('users', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'google_id' => 'google-123',
    ]);
});

test('existing email user is linked and logged in via google', function () {
    $user = User::factory()->create([
        'email' => 'jane@example.com',
    ]);

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-456',
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]));

    $response = $this->get(route('social.callback', 'google'));

    $response->assertRedirect(config('fortify.home'));
    $this->assertAuthenticatedAs($user);

    expect($user->fresh()->google_id)->toBe('google-456');
});

test('returning google user is logged in directly', function () {
    $user = User::factory()->create([
        'email' => 'jane@example.com',
        'google_id' => 'google-789',
    ]);

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-789',
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]));

    $response = $this->get(route('social.callback', 'google'));

    $response->assertRedirect(config('fortify.home'));
    $this->assertAuthenticatedAs($user);
});

test('google login does not create duplicate when email exists', function () {
    User::factory()->create([
        'email' => 'jane@example.com',
    ]);

    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-111',
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]));

    $this->get(route('social.callback', 'google'));

    expect(User::where('email', 'jane@example.com')->count())->toBe(1);
});

test('new google user has verified email', function () {
    Socialite::fake('google', (new SocialiteUser)->map([
        'id' => 'google-222',
        'name' => 'New User',
        'email' => 'new@example.com',
    ]));

    $this->get(route('social.callback', 'google'));

    expect(User::where('email', 'new@example.com')->first()->email_verified_at)->not->toBeNull();
});

test('social-only user can register with email to add a password', function () {
    $this->skipUnlessFortifyFeature(Features::registration());

    $user = User::factory()->create([
        'email' => 'jane@example.com',
        'password' => null,
        'google_id' => 'google-333',
    ]);

    $response = $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $this->assertAuthenticated();
    expect(User::where('email', 'jane@example.com')->count())->toBe(1);

    $user->refresh();
    expect($user->password)->not->toBeNull();
    expect($user->google_id)->toBe('google-333');
});

test('invalid provider is rejected', function () {
    $response = $this->get('/auth/facebook/redirect');

    $response->assertNotFound();
});
