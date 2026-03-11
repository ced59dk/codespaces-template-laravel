<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('code_article_compta');
            $table->enum('unit_type', ['hour', 'day', 'fixed'])->default('hour');
            $table->decimal('unit_price_default', 12, 2)->default(0);
            $table->decimal('vat_rate_default', 5, 2)->default(20.00);
            $table->timestamps();

            $table->unique(['tenant_id', 'code_article_compta']);
            $table->index(['tenant_id', 'code_article_compta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};