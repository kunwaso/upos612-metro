<?php

namespace Modules\VasAccounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\VasAccounting\Utils\VasAccountingUtil;

class VasAccountingDatabaseSeeder extends Seeder
{
    public function run()
    {
        $businessId = (int) config('app.default_business_id', 1);
        app(VasAccountingUtil::class)->bootstrapBusiness($businessId, 1);
    }
}
