<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GrantAdmin extends Command
{
    protected $signature = 'admin:grant {email}';

    protected $description = 'Grant admin access to an existing user';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        $user->forceFill(['is_admin' => true])->save();
        $this->info('Admin access granted.');

        return self::SUCCESS;
    }
}
