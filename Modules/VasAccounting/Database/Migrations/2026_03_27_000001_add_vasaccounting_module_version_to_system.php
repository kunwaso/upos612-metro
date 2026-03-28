<?php

use App\System;
use Illuminate\Database\Migrations\Migration;

class AddVasaccountingModuleVersionToSystem extends Migration
{
    public function up()
    {
        System::addProperty('vasaccounting_version', '1.0');
    }

    public function down()
    {
        System::removeProperty('vasaccounting_version');
    }
}
