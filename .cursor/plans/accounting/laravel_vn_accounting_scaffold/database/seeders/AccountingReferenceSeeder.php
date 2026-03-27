<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountingReferenceSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('currencies')->updateOrInsert(['code' => 'VND'], [
            'name' => 'Vietnamese Dong',
            'decimal_places' => 0,
            'is_base_currency' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('currencies')->updateOrInsert(['code' => 'USD'], [
            'name' => 'US Dollar',
            'decimal_places' => 2,
            'is_base_currency' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orgId = DB::table('organizations')->where('code', 'DEFAULT')->value('id');

        foreach ([
            ['code' => 'VAT0', 'name' => 'VAT 0%', 'tax_type' => 'VAT', 'rate' => 0],
            ['code' => 'VAT5', 'name' => 'VAT 5%', 'tax_type' => 'VAT', 'rate' => 5],
            ['code' => 'VAT10', 'name' => 'VAT 10%', 'tax_type' => 'VAT', 'rate' => 10],
            ['code' => 'NOVAT', 'name' => 'No VAT', 'tax_type' => 'VAT', 'rate' => 0],
        ] as $tax) {
            DB::table('tax_codes')->updateOrInsert(
                ['organization_id' => $orgId, 'code' => $tax['code']],
                array_merge($tax, [
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        DB::table('payment_terms')->updateOrInsert(
            ['organization_id' => $orgId, 'code' => 'IMMEDIATE'],
            ['name' => 'Immediate', 'due_days' => 0, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]
        );

        DB::table('payment_terms')->updateOrInsert(
            ['organization_id' => $orgId, 'code' => 'NET30'],
            ['name' => 'Net 30', 'due_days' => 30, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]
        );
    }
}
