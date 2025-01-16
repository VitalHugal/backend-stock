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
        Schema::table('inputs', function (Blueprint $table) {
            $table->foreignId('fk_storage_locations_id')->nullable()->constrained('storage_locations')->onUpdate('cascade');
            $table->string('date_of_manufacture')->nullable();
            $table->string('expiration_date')->nullable();
            $table->string('status')->nullable();
            $table->integer('alert')->nullable();
            $table->string('date_of_alert')->after('alert')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inputs', function (Blueprint $table) {
            $table->dropForeign(['fk_storage_locations_id']);
            $table->dropColumn([
                'fk_storage_locations_id',
                'date_of_manufacture',
                'expiration_date',
                'status',
                'alert',
                'date_of_alert',
            ]);
        });
    }
};