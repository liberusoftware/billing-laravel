<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products_services', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('base_price', 8, 2)->default(0);
            $table->string('type')->default('service');
            $table->string('pricing_model')->nullable();
            $table->json('custom_pricing_data')->nullable();
            $table->integer('product_type_id')->nullable()->constrained();
            $table->integer('hosting_server_id')->nullable()->constrained();
            $table->integer('trial_days')->default(0);
            $table->boolean('trial_enabled')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products_services');
    }
};
