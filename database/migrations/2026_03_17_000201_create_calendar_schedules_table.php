<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_schedules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('user_id')->index();
            $table->unsignedInteger('created_by')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('start_at')->index();
            $table->dateTime('end_at')->index();
            $table->boolean('all_day')->default(false);
            $table->string('color', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_schedules');
    }
};
