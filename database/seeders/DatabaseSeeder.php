<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Router;
use App\Models\BillingPlan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@mikrotik.local',
            'password' => bcrypt('password'),
        ]);

        // Sample billing plans
        BillingPlan::create([
            'name' => 'Paket 1 Jam',
            'type' => 'time',
            'time_limit' => 60,
            'rate_limit' => '1M/512k',
            'price' => 5000,
            'validity_period' => '1d',
            'description' => 'Paket internet 1 jam dengan kecepatan 1Mbps',
        ]);

        BillingPlan::create([
            'name' => 'Paket 1 GB',
            'type' => 'data',
            'data_limit' => 1073741824, // 1 GB in bytes
            'rate_limit' => '2M/1M',
            'price' => 10000,
            'validity_period' => '7d',
            'description' => 'Paket kuota 1GB dengan kecepatan 2Mbps',
        ]);

        BillingPlan::create([
            'name' => 'Paket Unlimited',
            'type' => 'unlimited',
            'rate_limit' => '5M/5M',
            'price' => 25000,
            'validity_period' => '1d',
            'description' => 'Paket unlimited 1 hari dengan kecepatan 5Mbps',
        ]);

        // Sample router (commented out - user should add their own)
        /*
        Router::create([
            'name' => 'Router Utama',
            'host' => '192.168.1.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => 'password',
            'location' => 'Kantor Pusat',
            'description' => 'Router MikroTik utama untuk hotspot',
        ]);
        */
    }
}
