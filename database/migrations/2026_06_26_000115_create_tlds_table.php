<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tlds', static function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->double('base_price')->default(0);
            $table->double('enom_cost')->default(0);
            $table->string('markup_type')->default('fixed');
            $table->double('markup_value')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tlds');
    }
};
