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
        Schema::create('payment_details', function (Blueprint $table) {
    $table->id();
   $table->foreignId('dv_request_mode_of_payment_id')
      ->constrained('dv_request_mode_of_payment')
      ->nullable()
      ->cascadeOnDelete();

    // Common fields
     $table->string('bank_name')->nullable();
     $table->string('bank_accnum')->nullable();
     $table->string('bank_address')->nullable();

    

   
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::dropIfExists('payment_details');
    }
};
