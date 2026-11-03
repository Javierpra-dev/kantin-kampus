<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class AdminSetPasswordCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:set-password {username}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set a new password for an admin canteen account';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $username = $this->argument('username');

        $admin = Admin::where('username', $username)->first();
        $user = User::where('username', $username)->first();

        if (!$admin && !$user) {
            $this->error("Admin dengan username '{$username}' tidak ditemukan.");
            return Command::FAILURE;
        }

        $password = $this->secret('Masukkan password baru:');

        if (empty($password)) {
            $this->error('Password tidak boleh kosong.');
            return Command::FAILURE;
        }

        if ($admin) {
            $admin->password = Hash::make($password);
            $admin->save();
        }

        if ($user) {
            $user->password = Hash::make($password);
            $user->save();
        }

        $this->info("Password untuk admin '{$username}' berhasil diperbarui.");

        return Command::SUCCESS;
    }
}

