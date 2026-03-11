<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->string('reference_commande')->nullable();
            $table->string('objet');
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $table->decimal('quantity', 12, 2)->nullable();
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->decimal('vat_rate', 5, 2)->nullable();
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('transmitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('transmitted_at')->nullable();

            $table->string('external_invoice_ref')->nullable();
            $table->timestamp('external_invoiced_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'start_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missions');
    }
};