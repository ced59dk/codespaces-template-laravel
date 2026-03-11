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
        Schema::table('services', function (Blueprint $table) {
            $table->decimal('rate_day_hour', 12, 2)->default(0);
            $table->decimal('rate_night_hour', 12, 2)->default(0);

            $table->decimal('rate_sun_day_hour', 12, 2)->default(0);
            $table->decimal('rate_sun_night_hour', 12, 2)->default(0);

            $table->decimal('rate_hol_day_hour', 12, 2)->default(0);
            $table->decimal('rate_hol_night_hour', 12, 2)->default(0);

            $table->decimal('rate_sun_hol_day_hour', 12, 2)->default(0);
            $table->decimal('rate_sun_hol_night_hour', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'rate_day_hour','rate_night_hour',
                'rate_sun_day_hour','rate_sun_night_hour',
                'rate_hol_day_hour','rate_hol_night_hour',
                'rate_sun_hol_day_hour','rate_sun_hol_night_hour',
            ]);
        });
    }
};
