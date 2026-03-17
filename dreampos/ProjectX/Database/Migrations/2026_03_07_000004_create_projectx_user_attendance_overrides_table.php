<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectxUserAttendanceOverridesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projectx_user_attendance_overrides', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->index();
            $table->integer('user_id')->index();
            $table->date('work_date')->index();
            $table->unsignedTinyInteger('hour_slot');
            $table->enum('status', ['present', 'break', 'late', 'permission', 'not_present']);
            $table->text('note')->nullable();
            $table->integer('created_by')->nullable()->index();
            $table->integer('updated_by')->nullable()->index();
            $table->timestamps();

            $table->unique(
                ['business_id', 'user_id', 'work_date', 'hour_slot'],
                'projectx_user_attendance_overrides_unique_cell'
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('projectx_user_attendance_overrides');
    }
}
