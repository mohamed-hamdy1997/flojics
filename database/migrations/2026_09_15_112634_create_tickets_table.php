<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject');
            $table->text('description')->nullable();
            $table->string('priority')->default('Medium');
            $table->string('status')->default('Open');
            $table->timestamp('escalated_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('escalated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
