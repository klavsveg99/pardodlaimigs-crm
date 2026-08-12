<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'pdc:create-admin
        {name : Full name}
        {email : Email}
        {password : Password (min 8 chars)}
        {--role=admin : Role (admin|agent)}
        {--super : Mark as Filament super-admin (no policy restrictions)}';

    protected $description = 'Create an admin user for the CRM.';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email.');

            return self::FAILURE;
        }
        $password = (string) $this->argument('password');
        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }
        if (User::where('email', $email)->exists()) {
            $this->error("User with email {$email} already exists.");

            return self::FAILURE;
        }

        $user = User::create([
            'name' => (string) $this->argument('name'),
            'email' => $email,
            'password' => Hash::make($password),
            'role' => $this->option('role'),
        ]);

        $this->info("Created user {$user->email}");
        $this->table(['Field', 'Value'], [
            ['ID',       $user->id],
            ['Name',     $user->name],
            ['Email',    $user->email],
            ['Role',     $user->role ?? 'admin'],
        ]);
        $this->warn('Keep these credentials in a password manager.');

        return self::SUCCESS;
    }
}
