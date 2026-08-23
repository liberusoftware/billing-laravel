<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table): void {
            // Add description column if missing
            if (! Schema::hasColumn('invoice_items', 'description')) {
                $table->string('description')->nullable()->after('invoice_id');
            }
            // Make product_service_id nullable (items may come from free-text descriptions)
            $table->unsignedBigInteger('product_service_id')->nullable()->change();
            // Rename total to total_price to match model fillable
            if (Schema::hasColumn('invoice_items', 'total') && ! Schema::hasColumn('invoice_items', 'total_price')) {
                $table->renameColumn('total', 'total_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table): void {
            $table->dropColumn('description');
            $table->unsignedBigInteger('product_service_id')->nullable(false)->change();
            if (Schema::hasColumn('invoice_items', 'total_price')) {
                $table->renameColumn('total_price', 'total');
            }
        });
    }
};
