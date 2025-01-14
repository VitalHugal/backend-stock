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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fk_product_equipament_id')->constrained('products_equipaments')->onUpdate('cascade');
            $table->foreignId('fk_user_id')->constrained('users')->onUpdate('cascade');
            $table->string('reason_project');
            $table->string('observation');
            $table->integer('quantity');
            $table->string('withdrawal_date');
            $table->string('return_date');
            $table->string('delivery_to');
            $table->string('status')->nullable();
            $table->boolean('reservation_finished');
            $table->string('date_finished')->nullable();
            $table->foreignId('fk_user_id_finished')->nullable()->constrained('users')->onUpdate('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation');
    }
};