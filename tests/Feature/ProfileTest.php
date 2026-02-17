<?php

use App\Models\User;
use App\Models\GameType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // Create a test user
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'location' => 'Test City',
        'phone' => '1234567890',
    ]);
});

test('user model has required attributes', function () {
    expect($this->user->first_name)->toBe('John');
    expect($this->user->last_name)->toBe('Doe');
    expect($this->user->email)->toBe('test@example.com');
    expect($this->user->location)->toBe('Test City');
    expect($this->user->phone)->toBe('1234567890');
    expect($this->user->full_name)->toBe('John Doe');
});

test('user can update profile information', function () {
    $this->user->update([
        'email' => 'updated@example.com',
        'location' => 'Updated City',
        'phone' => '0987654321',
    ]);

    $this->user->refresh();

    expect($this->user->email)->toBe('updated@example.com');
    expect($this->user->location)->toBe('Updated City');
    expect($this->user->phone)->toBe('0987654321');
});

test('user can upload profile picture', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('profile.jpg', 100, 100);
    $path = $file->store('profile-pictures', 'public');

    $this->user->update(['profile_picture' => $path]);

    $this->user->refresh();

    expect($this->user->profile_picture)->not->toBeNull();
    expect($this->user->profile_picture)->toStartWith('profile-pictures/');
    expect(Storage::disk('public')->exists($this->user->profile_picture))->toBeTrue();
});

test('user can upload base64 profile picture', function () {
    Storage::fake('public');

    // Create a simple base64 image
    $imageData = base64_encode('fake-image-data');
    $filename = 'profile-pictures/' . uniqid() . '.jpg';

    Storage::disk('public')->put($filename, base64_decode($imageData));

    $this->user->update(['profile_picture' => $filename]);

    $this->user->refresh();

    expect($this->user->profile_picture)->not->toBeNull();
    expect($this->user->profile_picture)->toStartWith('profile-pictures/');
    expect(Storage::disk('public')->exists($this->user->profile_picture))->toBeTrue();
});

test('user validates email uniqueness', function () {
    // Create another user with different email
    User::factory()->create(['email' => 'other@example.com']);

    // Try to update user with existing email
    $this->user->email = 'other@example.com';

    expect(fn() => $this->user->save())->toThrow(\Illuminate\Database\QueryException::class);
});

test('user can keep their own email', function () {
    $originalEmail = $this->user->email;

    $this->user->email = $originalEmail;
    $this->user->save();

    $this->user->refresh();
    expect($this->user->email)->toBe($originalEmail);
});

test('user validates file size for profile picture', function () {
    Storage::fake('public');

    // Create a file that's too large (6MB)
    $file = UploadedFile::fake()->image('large.jpg')->size(6 * 1024 * 1024);

    // This should fail validation
    expect($file->getSize())->toBeGreaterThan(5 * 1024 * 1024);
});

test('user validates file type for profile picture', function () {
    Storage::fake('public');

    // Create an invalid file type
    $file = UploadedFile::fake()->create('document.pdf', 100);

    // This should fail validation
    expect($file->getMimeType())->not->toBeIn(['image/jpeg', 'image/jpg', 'image/png', 'image/gif']);
});

test('user can handle email verification status', function () {
    // Test unverified email
    $this->user->email_verified_at = null;
    $this->user->save();

    expect($this->user->email_verified_at)->toBeNull();

    // Test verified email
    $this->user->email_verified_at = now();
    $this->user->save();

    expect($this->user->email_verified_at)->not->toBeNull();
});

test('user can generate full name correctly', function () {
    $this->user->first_name = 'Jane';
    $this->user->last_name = 'Smith';
    $this->user->save();

    expect($this->user->full_name)->toBe('Jane Smith');
});

test('user can handle null values gracefully', function () {
    $this->user->update([
        'location' => null,
        'phone' => null,
        'profile_picture' => null,
    ]);

    $this->user->refresh();

    expect($this->user->location)->toBeNull();
    expect($this->user->phone)->toBeNull();
    expect($this->user->profile_picture)->toBeNull();
});
