<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products_equipaments', function (Blueprint $table) {
            $table->boolean('is_grup')->default(0);
        });

        DB::statement("ALTER TABLE products_equipaments CHANGE COLUMN quantity_min quantity_min INT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products_equipaments', function (Blueprint $table) {
            $table->dropColumn([
                'is_grup',
            ]);
        });

        DB::statement("ALTER TABLE products_equipaments CHANGE COLUMN quantity_min quantity_min INT ");
    }
};