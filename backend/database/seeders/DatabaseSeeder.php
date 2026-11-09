<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('platform.admin_email');
        $password = config('platform.admin_password');

        if (! $email || ! $password) {
            $this->command->warn('ADMIN_EMAIL et ADMIN_PASSWORD doivent être définis dans .env.');

            return;
        }

        $user = User::firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => 'Administrateur',
            'password' => Hash::make($password),
            'role' => 'admin',
            'status' => 'active',
        ])->save();
    }
}
