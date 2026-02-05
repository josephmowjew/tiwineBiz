<?php

namespace App\Console\Commands;

use Database\Seeders\AdminSeeder;
use Illuminate\Console\Command;

class SeedAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the default admin user and Mufasah Electronics shop';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Seeding admin user and Mufasah Electronics shop...');

        $seeder = new AdminSeeder;
        $seeder->setContainer($this->laravel);
        $seeder->setCommand($this);
        $seeder->run();

        $this->info('');
        $this->info('✓ Admin account created successfully!');
        $this->info('  Email: admin@mufashelectronics.com');
        $this->info('  Password: Mufasah@2026');

        return self::SUCCESS;
    }
}
