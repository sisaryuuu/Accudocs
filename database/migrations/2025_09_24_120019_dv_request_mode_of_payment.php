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
        Schema::create('dv_request_mode_of_payment', function (Blueprint $table) {
        $table->id();
        $table->foreignId('dv_request_id')->constrained('dv_requests')->cascadeOnDelete();
        $table->foreignId('mode_of_payment_id')->constrained('mode_of_payments')->cascadeOnDelete();
        $table->timestamps();
       });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
          Schema::dropIfExists('dv_request_mode_of_payment');
    }
};
