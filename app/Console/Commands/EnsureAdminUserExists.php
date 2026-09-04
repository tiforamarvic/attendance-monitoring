<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('app:ensure-admin-user-exists')]
#[Description('Create the single admin user if needed, or sync its password from ADMIN_PASSWORD when set. Safe to run on every boot.')]
class EnsureAdminUserExists extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = config('admin.email');
        $configuredPassword = config('admin.password');

        $admin = User::where('email', $email)->first();

        if ($admin) {
            if ($configuredPassword !== null) {
                $admin->update(['password' => bcrypt($configuredPassword)]);
                $this->info("Admin user already exists ({$email}); password synced from ADMIN_PASSWORD.");
            } else {
                $this->info("Admin user already exists ({$email}); ADMIN_PASSWORD not set, leaving password unchanged.");
            }

            return self::SUCCESS;
        }

        $generated = $configuredPassword === null;
        $password = $configuredPassword ?? Str::random(32);

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
