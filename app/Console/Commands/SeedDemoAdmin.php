<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SeedDemoAdmin extends Command
{
    protected $signature = 'pdc:seed-admin';

    protected $description = 'Create or reset the demo admin user';

    public function handle(): int
    {
        $email = $this->ask('Email', 'admin@pardodlaimigs.lv');
        $password = $this->secret('Password');

        if (! $password) {
            $this->error('Password cannot be empty.');

            return static::FAILURE;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin',
                'password' => Hash::make($password),
                'role' => 'admin',
                'email_verified_at' => now(),
            ],
        );

        $this->info("Admin user ready: {$user->email}");

        return static::SUCCESS;
    }
}
