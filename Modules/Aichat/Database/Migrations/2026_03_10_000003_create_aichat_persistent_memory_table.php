<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CreateAichatPersistentMemoryTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('aichat_persistent_memory')) {
            Schema::create('aichat_persistent_memory', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id');
                $table->string('slug', 32)->unique('aichat_persistent_memory_slug_unique');
                $table->string('display_name', 150)->nullable();
                $table->timestamps();

                $table->foreign('business_id')
                    ->references('id')
                    ->on('business')
                    ->onDelete('cascade');
                $table->unique('business_id', 'aichat_persistent_memory_business_unique');
            });
        }

        $this->backfillPersistentMemoryContainers();
    }

    public function down()
    {
        Schema::dropIfExists('aichat_persistent_memory');
    }

    protected function backfillPersistentMemoryContainers(): void
    {
        if (! Schema::hasTable('aichat_persistent_memory') || ! Schema::hasTable('business')) {
            return;
        }

        DB::table('business')
            ->select('id')
            ->orderBy('id')
            ->chunk(200, function ($businesses) {
                foreach ($businesses as $business) {
                    $businessId = (int) $business->id;
                    if ($businessId <= 0) {
                        continue;
                    }

                    $exists = DB::table('aichat_persistent_memory')
                        ->where('business_id', $businessId)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $timestamp = now();
                    DB::table('aichat_persistent_memory')->insert([
                        'business_id' => $businessId,
                        'slug' => $this->generateUniqueSlug(),
                        'display_name' => null,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ]);
                }
            });
    }

    protected function generateUniqueSlug(): string
    {
        do {
            $slug = Str::lower(Str::random(16));
            $exists = DB::table('aichat_persistent_memory')
                ->where('slug', $slug)
                ->exists();
        } while ($exists);

        return $slug;
    }
}

