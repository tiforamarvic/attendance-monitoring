<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('app:ensure-admin-user-exists')]
#[Description('Create the single admin user if one does not already exist yet. Safe to run on every boot.')]
class EnsureAdminUserExists extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = config('admin.email');

        if (User::where('email', $email)->exists()) {
            $this->info("Admin user already exists ({$email}); nothing to do.");

            return self::SUCCESS;
        }

        $password = config('admin.password');
        $generated = $password === null;
        $password ??= Str::random(32);

        User::create([
            'name' => config('admin.name'),
            'email' => $email,
            'password' => bcrypt($password),
        ]);

        $this->info("Admin user created: {$email}");

        if ($generated) {
            $this->warn("No ADMIN_PASSWORD env var was set, so a random password was generated:\n{$password}");
            $this->warn('Copy it now — set ADMIN_PASSWORD in your environment to control it going forward.');
        }

        return self::SUCCESS;
    }
}
