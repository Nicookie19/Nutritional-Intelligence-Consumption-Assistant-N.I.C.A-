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
        Schema::table('planned_meal_entries', function (Blueprint $table) {
            $table->decimal('protein', 6, 1)->default(0)->after('calories');
            $table->decimal('carbs', 6, 1)->default(0)->after('protein');
            $table->decimal('fat', 6, 1)->default(0)->after('carbs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('planned_meal_entries', function (Blueprint $table) {
            $table->dropColumn(['protein', 'carbs', 'fat']);
        });
    }
};
