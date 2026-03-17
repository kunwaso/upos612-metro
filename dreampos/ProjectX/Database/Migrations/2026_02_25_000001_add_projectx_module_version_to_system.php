<?php

use App\System;
use Illuminate\Database\Migrations\Migration;

class AddProjectxModuleVersionToSystem extends Migration
{
    public function up()
    {
        System::addProperty('projectx_version', '1.0');
    }

    public function down()
    {
        System::removeProperty('projectx_version');
    }
}
