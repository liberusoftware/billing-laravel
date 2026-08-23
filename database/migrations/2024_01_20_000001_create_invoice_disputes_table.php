<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_disputes', function (Blueprint $table): void {
            $table->id();
            $table->integer('invoice_id')->nullable();
            $table->integer('customer_id')->constrained();
            $table->string('status')->default('open');
            $table->string('reason');
            $table->text('description');
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->integer('resolved_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_disputes');
    }
};
