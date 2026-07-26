<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('lms.super_admin.email');
        $password = config('lms.super_admin.password');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Super Admin',
                'password' => Hash::make($password),
            ]
        );

        $user->forceFill([
            'is_active' => true,
            'must_change_password' => true,
            'email_verified_at' => now(),
        ])->save();

        $user->assignRole('Super Admin');

        $this->command->warn(
            "Seeded Super Admin ({$email}) with a default password from config/lms.php. Rotate it before using this in production."
        );
    }
}
