<?php

use App\System;
use Illuminate\Database\Migrations\Migration;

class AddAichatModuleVersionToSystem extends Migration
{
    public function up()
    {
        System::addProperty('aichat_version', '1.0');
    }

    public function down()
    {
        System::removeProperty('aichat_version');
    }
}
