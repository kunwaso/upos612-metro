<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AccountingCoreSeeder::class,
            AccountingReferenceSeeder::class,
            SampleChartOfAccountsSeeder::class,
        ]);
    }
}
