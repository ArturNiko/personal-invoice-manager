<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceCurrency;
use App\Enums\InvoiceType;
use App\Enums\InvoiceReccuranceType;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('status', InvoiceStatus::getAll())->default(InvoiceStatus::PENDING->value);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('price_total', 12, 2)->nullable();
            $table->decimal('price_occurrence', 12, 2)->nullable();
            $table->enum('currency', InvoiceCurrency::getAll())->default(config('invoices.default_currency', InvoiceCurrency::EUR->value));
            $table->enum('type', InvoiceType::getAll());
            $table->enum('recurrence', InvoiceReccuranceType::getAll())->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
