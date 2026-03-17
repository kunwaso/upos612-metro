<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add delivery_date to transactions for ProjectX sales order tracking.
     * Root base may not have this column; ProjectX owns it for sale orders.
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasColumn('transactions', 'delivery_date')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dateTime('delivery_date')->nullable()->index()->after('shipping_address');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        if (! Schema::hasColumn('transactions', 'delivery_date')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('delivery_date');
        });
    }
};
