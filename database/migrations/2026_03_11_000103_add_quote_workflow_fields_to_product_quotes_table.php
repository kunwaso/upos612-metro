<?php

use App\Business;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddQuoteWorkflowFieldsToProductQuotesTable extends Migration
{
    public function up()
    {
        Schema::table('product_quotes', function (Blueprint $table) {
            if (! Schema::hasColumn('product_quotes', 'quote_number')) {
                $table->string('quote_number')->nullable()->after('uuid');
                $table->index('quote_number');
            }

            if (! Schema::hasColumn('product_quotes', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('sent_at');
                $table->index('confirmed_at');
            }

            if (! Schema::hasColumn('product_quotes', 'confirmation_signature')) {
                $table->longText('confirmation_signature')->nullable()->after('confirmed_at');
            }

            if (! Schema::hasColumn('product_quotes', 'created_by')) {
                $table->unsignedInteger('created_by')->nullable()->after('line_count');
                $table->index('created_by');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            }
        });

        $this->backfillQuoteNumbers();

        if (! $this->hasUniqueIndex('product_quotes', 'product_quotes_business_id_quote_number_unique')) {
            Schema::table('product_quotes', function (Blueprint $table) {
                $table->unique(['business_id', 'quote_number'], 'product_quotes_business_id_quote_number_unique');
            });
        }
    }

    public function down()
    {
        $hasQuoteNumberUnique = $this->hasUniqueIndex('product_quotes', 'product_quotes_business_id_quote_number_unique');

        Schema::table('product_quotes', function (Blueprint $table) use ($hasQuoteNumberUnique) {
            if ($hasQuoteNumberUnique) {
                $table->dropUnique('product_quotes_business_id_quote_number_unique');
            }

            if (Schema::hasColumn('product_quotes', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropIndex(['created_by']);
                $table->dropColumn('created_by');
            }

            if (Schema::hasColumn('product_quotes', 'confirmation_signature')) {
                $table->dropColumn('confirmation_signature');
            }

            if (Schema::hasColumn('product_quotes', 'confirmed_at')) {
                $table->dropIndex(['confirmed_at']);
                $table->dropColumn('confirmed_at');
            }

            if (Schema::hasColumn('product_quotes', 'quote_number')) {
                $table->dropIndex(['quote_number']);
                $table->dropColumn('quote_number');
            }
        });
    }

    protected function backfillQuoteNumbers(): void
    {
        DB::table('product_quotes')
            ->whereNull('quote_number')
            ->orderBy('id')
            ->chunkById(100, function ($quotes) {
                $businesses = Business::whereIn('id', collect($quotes)->pluck('business_id')->unique()->all())
                    ->get(['id', 'ref_no_prefixes'])
                    ->keyBy('id');

                foreach ($quotes as $quote) {
                    $business = $businesses->get((int) $quote->business_id);
                    $prefixes = (array) ($business->ref_no_prefixes ?? []);
                    $prefix = trim((string) ($prefixes['product_quote'] ?? config('product.quote_defaults.prefix', 'RFQ')));

                    if ($prefix === '') {
                        $prefix = 'RFQ';
                    }

                    $date = ! empty($quote->created_at) ? Carbon::parse($quote->created_at) : Carbon::now();
                    $quoteNumber = sprintf('%s-%s-%s-%06d', $prefix, $date->format('Y'), $date->format('md'), (int) $quote->id);

                    DB::table('product_quotes')
                        ->where('id', (int) $quote->id)
                        ->update(['quote_number' => $quoteNumber]);
                }
            });
    }

    protected function hasUniqueIndex(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();
        $result = DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();

        return (bool) $result;
    }
}
