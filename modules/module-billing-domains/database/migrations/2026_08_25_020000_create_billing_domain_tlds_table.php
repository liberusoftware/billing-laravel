<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('billing_domain_tlds', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 64)->unique();
            $table->decimal('registrar_cost', 12, 4)->nullable();
            $table->decimal('base_price', 12, 4);
            $table->string('markup_type')->default('none');
            $table->decimal('markup_value', 12, 4)->default(0);
            $table->boolean('enabled')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_domain_tlds');
    }
};
