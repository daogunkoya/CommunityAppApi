<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\User;

class TestEmail extends Command
{
    protected $signature = 'test:email {email?}';
    protected $description = 'Test email functionality';

    public function handle()
    {
        $email = $this->argument('email') ?? 'test@example.com';

        $this->info("Testing email functionality...");
        $this->info("Sending test email to: {$email}");

        try {
            // Create a test user for email verification
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'password' => bcrypt('password'),
                    'location' => 'Test City',
                ]
            );

            // Generate verification token
            $verificationToken = $user->generateEmailVerificationToken();

            // Send verification email
            $user->notify(new \App\Notifications\EmailVerificationNotification($verificationToken));

            $this->info("✅ Test email sent successfully!");
            $this->info("Check your Mailtrap inbox to see the email.");

        } catch (\Exception $e) {
            $this->error("❌ Email test failed: " . $e->getMessage());
            $this->error("Check your mail configuration in .env file");
        }
    }
}
