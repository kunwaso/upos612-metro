<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountingCoreSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('organizations')->updateOrInsert(
            ['code' => 'DEFAULT'],
            [
                'name' => 'Default Company',
                'tax_code' => '0000000000',
                'base_currency' => 'VND',
                'timezone' => 'Asia/Ho_Chi_Minh',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $orgId = DB::table('organizations')->where('code', 'DEFAULT')->value('id');

        DB::table('branches')->updateOrInsert(
            ['organization_id' => $orgId, 'code' => 'HO'],
            [
                'name' => 'Head Office',
                'is_head_office' => true,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        for ($m = 1; $m <= 12; $m++) {
            DB::table('accounting_periods')->updateOrInsert(
                ['organization_id' => $orgId, 'year' => 2026, 'month' => $m],
                [
                    'start_date' => now()->setDate(2026, $m, 1)->startOfMonth()->toDateString(),
                    'end_date' => now()->setDate(2026, $m, 1)->endOfMonth()->toDateString(),
                    'status' => 'open',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
