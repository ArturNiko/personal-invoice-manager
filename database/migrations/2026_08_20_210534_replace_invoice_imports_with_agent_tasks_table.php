<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Enums\AgentTaskState;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agent_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->string('status');
            $table->string('file_path');
            $table->text('details')->nullable();
            $table->timestamps();
        });
        Schema::dropIfExists('invoice_imports');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_tasks');

        Schema::create('invoice_imports', function (Blueprint $table) {
            $table->id();
            $table->string('file_path');
            $table->enum('status', AgentTaskState::getAll())->default(AgentTaskState::PENDING->value);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }
};
