<?php

use App\Business;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddQuoteWorkflowFieldsToProjectxQuotesTable extends Migration
{
    public function up()
    {
        Schema::table('projectx_quotes', function (Blueprint $table) {
            if (! Schema::hasColumn('projectx_quotes', 'quote_number')) {
                $table->string('quote_number')->nullable()->after('uuid');
                $table->index('quote_number');
            }

            if (! Schema::hasColumn('projectx_quotes', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('sent_at');
                $table->index('confirmed_at');
            }

            if (! Schema::hasColumn('projectx_quotes', 'confirmation_signature')) {
                $table->longText('confirmation_signature')->nullable()->after('confirmed_at');
            }

            if (! Schema::hasColumn('projectx_quotes', 'created_by')) {
                $table->unsignedInteger('created_by')->nullable()->after('line_count');
                $table->index('created_by');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            }
        });

        $this->backfillQuoteNumbers();

        if (! $this->hasUniqueIndex('projectx_quotes', 'projectx_quotes_business_id_quote_number_unique')) {
            Schema::table('projectx_quotes', function (Blueprint $table) {
                $table->unique(['business_id', 'quote_number'], 'projectx_quotes_business_id_quote_number_unique');
            });
        }
    }

    public function down()
    {
        $hasQuoteNumberUnique = $this->hasUniqueIndex('projectx_quotes', 'projectx_quotes_business_id_quote_number_unique');

        Schema::table('projectx_quotes', function (Blueprint $table) use ($hasQuoteNumberUnique) {
            if ($hasQuoteNumberUnique) {
                $table->dropUnique('projectx_quotes_business_id_quote_number_unique');
            }

            if (Schema::hasColumn('projectx_quotes', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropIndex(['created_by']);
                $table->dropColumn('created_by');
            }

            if (Schema::hasColumn('projectx_quotes', 'confirmation_signature')) {
                $table->dropColumn('confirmation_signature');
            }

            if (Schema::hasColumn('projectx_quotes', 'confirmed_at')) {
                $table->dropIndex(['confirmed_at']);
                $table->dropColumn('confirmed_at');
            }

            if (Schema::hasColumn('projectx_quotes', 'quote_number')) {
                $table->dropIndex(['quote_number']);
                $table->dropColumn('quote_number');
            }
        });
    }

    protected function backfillQuoteNumbers(): void
    {
        DB::table('projectx_quotes')
            ->whereNull('quote_number')
            ->orderBy('id')
            ->chunkById(100, function ($quotes) {
                $businesses = Business::whereIn('id', collect($quotes)->pluck('business_id')->unique()->all())
                    ->get(['id', 'ref_no_prefixes'])
                    ->keyBy('id');

                foreach ($quotes as $quote) {
                    $business = $businesses->get((int) $quote->business_id);
                    $prefixes = (array) ($business->ref_no_prefixes ?? []);
                    $prefix = trim((string) ($prefixes['projectx_quote'] ?? config('projectx.quote_defaults.prefix', 'RFQ')));

                    if ($prefix === '') {
                        $prefix = 'RFQ';
                    }

                    $date = ! empty($quote->created_at) ? Carbon::parse($quote->created_at) : Carbon::now();
                    $quoteNumber = sprintf('%s-%s-%s-%06d', $prefix, $date->format('Y'), $date->format('md'), (int) $quote->id);

                    DB::table('projectx_quotes')
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
