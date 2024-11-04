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
        Schema::create('category_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fk_category_id')->constrained('category')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('fk_user_id')->constrained('users')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
            $table->softDeletes();
        });

        // DB::table('category_user')->insert([
        //     'fk_user_id' => 1,
        //     'fk_category_id' => 1,
        // ]);
        // DB::table('category_user')->insert([
        //     'fk_user_id' => 1,
        //     'fk_category_id' => 2,
        // ]);
        // DB::table('category_user')->insert([
        //     'fk_user_id' => 1,
        //     'fk_category_id' => 3,
        // ]);
        // DB::table('category_user')->insert([
        //     'fk_user_id' => 1,
        //     'fk_category_id' => 4,
        // ]);
        // DB::table('category_user')->insert([
        //     'fk_user_id' => 1,
        //     'fk_category_id' => 5,
        // ]);
        // DB::table('category_user')->insert([
        //     'fk_user_id' => 1,
        //     'fk_category_id' => 6,
        // ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_users');
    }
};