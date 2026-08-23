<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_recurring')->default(true);
            $table->boolean('requires_server')->default(false);
            $table->timestamps();
        });

        // Insert default product types
        DB::table('product_types')->insert([
            [
                'name' => 'One Time',
                'slug' => 'one-time',
                'is_recurring' => false,
                'requires_server' => false,
            ],
            [
                'name' => 'Shared Hosting',
                'slug' => 'shared-hosting',
                'is_recurring' => true,
                'requires_server' => true,
            ],
            [
                'name' => 'VPS',
                'slug' => 'vps',
                'is_recurring' => true,
                'requires_server' => true,
            ],
            [
                'name' => 'Dedicated Server',
                'slug' => 'dedicated',
                'is_recurring' => true,
                'requires_server' => true,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('product_types');
    }
};
