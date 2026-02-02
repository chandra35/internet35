<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UpdatePlainPasswords extends Command
{
    protected $signature = 'users:update-plain-passwords {password=password}';
    protected $description = 'Update plain_password for existing users';

    public function handle()
    {
        $password = $this->argument('password');
        
        User::whereNull('plain_password')->each(function ($user) use ($password) {
            $user->plain_password = $password;
            $user->save();
            $this->info("Updated: {$user->email}");
        });

        $this->info('Done!');
        return 0;
    }
}
