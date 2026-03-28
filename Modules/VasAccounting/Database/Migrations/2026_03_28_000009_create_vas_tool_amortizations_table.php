<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVasToolAmortizationsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('vas_tool_amortizations')) {
            return;
        }

        Schema::create('vas_tool_amortizations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedBigInteger('tool_id');
            $table->unsignedBigInteger('accounting_period_id');
            $table->unsignedBigInteger('voucher_id')->nullable();
            $table->date('amortization_date');
            $table->decimal('amount', 22, 4)->default(0);
            $table->string('status', 30)->default('draft');
            $table->timestamp('posted_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('tool_id')->references('id')->on('vas_tools')->onDelete('cascade');
            $table->foreign('accounting_period_id')->references('id')->on('vas_accounting_periods')->onDelete('cascade');
            $table->foreign('voucher_id')->references('id')->on('vas_vouchers')->onDelete('set null');
            $table->unique(['business_id', 'tool_id', 'accounting_period_id'], 'vas_tool_amortizations_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vas_tool_amortizations');
    }
}
