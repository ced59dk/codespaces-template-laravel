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
            $table->unsignedInteger('min_total')->default(0)->after('quantity');

            $table->unsignedInteger('min_day')->default(0);
            $table->unsignedInteger('min_night')->default(0);

            $table->unsignedInteger('min_sun_day')->default(0);
            $table->unsignedInteger('min_sun_night')->default(0);

            $table->unsignedInteger('min_hol_day')->default(0);
            $table->unsignedInteger('min_hol_night')->default(0);

            $table->unsignedInteger('min_sun_hol_day')->default(0);
            $table->unsignedInteger('min_sun_hol_night')->default(0);

            $table->decimal('amount_ht', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->dropColumn([
                'min_total',
                'min_day','min_night',
                'min_sun_day','min_sun_night',
                'min_hol_day','min_hol_night',
                'min_sun_hol_day','min_sun_hol_night',
                'amount_ht',
            ]);
        });
    }
};
