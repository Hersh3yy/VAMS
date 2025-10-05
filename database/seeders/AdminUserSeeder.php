<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin credentials from environment variables or use defaults
        $adminEmail = env('ADMIN_EMAIL', 'info@hiren.ninja');
        $adminName = env('ADMIN_NAME', 'Hiren Test');
        $adminPassword = env('ADMIN_PASSWORD', 'password');

        // Check if admin user already exists
        $existingUser = User::where('email', $adminEmail)->first();

        if (! $existingUser) {
            // Create admin user
            $admin = User::create([
                'name' => $adminName,
                'email' => $adminEmail,
                'email_verified_at' => now(),
                'password' => Hash::make($adminPassword),
                'remember_token' => Str::random(10),
                'is_admin' => true,
                'is_approved' => true,
                'approved_at' => now(),
                'album_display_settings' => [
                    'caption' => true,
                    'altText' => true,
                    'dateCreated' => true,
                    'location' => true,
                    'tags' => true,
                    'title' => true,
                    'author' => true,
                    'main_color' => '#4F46E5', // Default indigo color
                    'secondary_color' => '#10B981', // Default emerald color
                ],
                'api_key' => Str::random(64),
            ]);

            $this->command->info("Admin user '{$adminName}' created with email: {$adminEmail}");
        } else {
            $this->command->info("Admin user '{$adminEmail}' already exists");
        }
    }
}
