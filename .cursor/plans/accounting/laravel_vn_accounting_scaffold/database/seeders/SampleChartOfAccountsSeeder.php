<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SampleChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $orgId = DB::table('organizations')->where('code', 'DEFAULT')->value('id');

        $accounts = [
            ['111', 'Cash on hand', 'asset', 'debit'],
            ['112', 'Cash in bank', 'asset', 'debit'],
            ['131', 'Receivables from customers', 'asset', 'debit'],
            ['1331', 'Deductible VAT', 'asset', 'debit'],
            ['1388', 'Other receivables', 'asset', 'debit'],
            ['141', 'Advances to employees', 'asset', 'debit'],
            ['152', 'Raw materials', 'asset', 'debit'],
            ['153', 'Tools and supplies', 'asset', 'debit'],
            ['156', 'Merchandise inventory', 'asset', 'debit'],
            ['211', 'Tangible fixed assets', 'asset', 'debit'],
            ['214', 'Accumulated depreciation', 'asset_contra', 'credit'],
            ['242', 'Prepaid expenses', 'asset', 'debit'],
            ['331', 'Payables to vendors', 'liability', 'credit'],
            ['3331', 'Output VAT', 'liability', 'credit'],
            ['334', 'Payroll payable', 'liability', 'credit'],
            ['338', 'Other payables', 'liability', 'credit'],
            ['411', 'Owner equity', 'equity', 'credit'],
            ['421', 'Retained earnings', 'equity', 'credit'],
            ['511', 'Sales revenue', 'revenue', 'credit'],
            ['515', 'Financial income', 'revenue', 'credit'],
            ['632', 'Cost of goods sold', 'expense', 'debit'],
            ['635', 'Financial expense', 'expense', 'debit'],
            ['641', 'Selling expense', 'expense', 'debit'],
            ['642', 'General and administrative expense', 'expense', 'debit'],
            ['711', 'Other income', 'revenue', 'credit'],
            ['811', 'Other expense', 'expense', 'debit'],
        ];

        foreach ($accounts as [$code, $nameVi, $type, $normal]) {
            DB::table('accounts')->updateOrInsert(
                ['organization_id' => $orgId, 'code' => $code],
                [
                    'name_vi' => $nameVi,
                    'account_type' => $type,
                    'normal_balance' => $normal,
                    'level' => strlen($code) <= 3 ? 1 : 2,
                    'is_postable' => true,
                    'status' => 'active',
                    'effective_from' => '2026-01-01',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
