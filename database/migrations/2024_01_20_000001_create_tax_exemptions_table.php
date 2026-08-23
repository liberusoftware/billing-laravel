<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_exemptions', function (Blueprint $table): void {
            $table->id();
            $table->integer('customer_id')->nullable();
            $table->string('exemption_number')->nullable();
            $table->text('reason');
            $table->date('expiry_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('documentation_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_exemptions');
    }
};
