<?php

use App\Enums\InvoiceType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->nullable()->after('end_date');
        });

        DB::table('invoices')->update([
            'price' => DB::raw('COALESCE(price_occurrence, price_total)'),
        ]);

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['price_total', 'price_occurrence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('price_total', 12, 2)->nullable()->after('end_date');
            $table->decimal('price_occurrence', 12, 2)->nullable()->after('price_total');
        });

        DB::table('invoices')
            ->where('type', InvoiceType::RECURRING->value)
            ->update(['price_occurrence' => DB::raw('price')]);

        DB::table('invoices')
            ->where('type', InvoiceType::ONE_TIME->value)
            ->update(['price_total' => DB::raw('price')]);

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};