<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', static function (Blueprint $table): void {
            $table->decimal('tax_amount', 10, 2)->nullable()->after('discount_amount');
            $table->boolean('is_recurring')->nullable()->after('is_installment');
            $table->foreignId('invoice_template_id')->nullable()->after('is_recurring');
            $table->text('notes')->nullable()->after('invoice_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', static function (Blueprint $table): void {
            $table->dropColumn(['tax_amount', 'is_recurring', 'invoice_template_id', 'notes']);
        });
    }
};
