<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PlatformAdmin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class NexstayCreatePlatformAdminCommand extends Command
{
    protected $signature = 'nexstay:create-platform-admin
                            {email : Admin email address}
                            {--name= : Display name (defaults to email prefix)}
                            {--password= : Password (auto-generated if omitted)}';

    protected $description = 'Create a NexStay platform administrator account';

    public function handle(): int
    {
        $email = $this->argument('email');
        $name  = $this->option('name') ?: str($email)->before('@')->title()->toString();
        $password = $this->option('password') ?: $this->generatePassword();

        if (PlatformAdmin::where('email', $email)->exists()) {
            $this->components->warn("A platform admin with email [{$email}] already exists.");
            return self::FAILURE;
        }

        PlatformAdmin::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
        ]);

        $this->newLine();
        $this->components->info('Platform admin created successfully.');
        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $name],
                ['Email', $email],
                ['Password', $this->option('password') ? '(as provided)' : $password],
                ['Login URL', rtrim(config('app.url'), '/').'/platform/login'],
            ]
        );
        $this->newLine();

        return self::SUCCESS;
    }

    private function generatePassword(): string
    {
        $chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$%';
        return substr(str_shuffle(str_repeat($chars, 4)), 0, 16);
    }
}
