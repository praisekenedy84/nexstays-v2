<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class NexstayInstallDemoCommand extends Command
{
    protected $signature = 'nexstay:install-demo
                            {--migrate-central : Run central migrations first}
                            {--seed : Seed demo hotel data into the tenant}';

    protected $description = 'Provision the demo tenant (schema, domain) and optional demo data';

    public function handle(): int
    {
        if ($this->option('migrate-central')) {
            $this->components->info('Running central migrations…');
            $this->call('migrate', ['--force' => true]);
        }

        $tenantId = config('nexstay.demo.tenant_id');
        $domain = config('nexstay.demo.domain');

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $tenant = Tenant::create(['id' => $tenantId]);
            $this->components->info("Created tenant [{$tenantId}].");
        } else {
            $this->components->info("Tenant [{$tenantId}] already exists.");
        }

        if ($tenant->domains()->where('domain', $domain)->doesntExist()) {
            $tenant->createDomain($domain);
            $this->components->info("Added domain [{$domain}].");
        }

        $this->call('tenants:migrate', [
            '--tenants' => [$tenantId],
        ]);

        if ($this->option('seed')) {
            $this->call('tenants:seed', [
                '--tenants' => [$tenantId],
            ]);
        }

        $baseUrl = "http://{$domain}.localhost:8000";
        $password = config('nexstay.demo.password');

        $this->newLine();
        $this->components->info('Demo tenant ready.');
        $this->table(
            ['Role', 'Email', 'Password'],
            [
                ['Admin (GM)', config('nexstay.demo.admin_email'), $password],
                ['Front desk', config('nexstay.demo.front_desk_email'), $password],
                ['Housekeeper', config('nexstay.demo.housekeeper_email'), $password],
            ]
        );
        $this->line("  API base: {$baseUrl}/api/v1");
        $this->line("  Login:    POST {$baseUrl}/api/v1/auth/login");
        $this->newLine();

        return self::SUCCESS;
    }
}
