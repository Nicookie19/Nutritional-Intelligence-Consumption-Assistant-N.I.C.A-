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
        Schema::table('user_experiences', function (Blueprint $table) {
            $table->unsignedSmallInteger('height_cm')->default(0)->change();
            $table->decimal('current_weight_kg', 5, 1)->default(0)->change();
            $table->decimal('target_weight_kg', 5, 1)->default(0)->change();
            $table->decimal('starting_weight_kg', 5, 1)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_experiences', function (Blueprint $table) {
            $table->unsignedSmallInteger('height_cm')->default(1)->change();
            $table->decimal('current_weight_kg', 5, 1)->default(1)->change();
            $table->decimal('target_weight_kg', 5, 1)->default(1)->change();
            $table->decimal('starting_weight_kg', 5, 1)->default(1)->change();
        });
    }
};
