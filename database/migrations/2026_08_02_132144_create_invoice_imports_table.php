<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Enums\InvoiceImportState;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoice_imports', function (Blueprint $table) {
            $table->id();
            $table->string('file_path');
            $table->enum('status', InvoiceImportState::getAll())->default(InvoiceImportState::PENDING->value);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_imports');
    }
};
