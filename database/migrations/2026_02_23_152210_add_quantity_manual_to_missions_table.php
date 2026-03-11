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
    Schema::table('missions', function (Blueprint $table) {
        $table->boolean('quantity_manual')->default(false)->after('quantity');
        $table->text('quantity_manual_reason')->nullable()->after('quantity_manual');
    });
}

public function down(): void
{
    Schema::table('missions', function (Blueprint $table) {
        $table->dropColumn(['quantity_manual', 'quantity_manual_reason']);
    });
}
};
