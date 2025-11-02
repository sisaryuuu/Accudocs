<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_dv_request_history_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dv_request_history', function (Blueprint $table) {
            $table->id();
            
            // Copy all columns from dv_requests
            $table->string('reference_number');
            $table->string('dv_number')->nullable();
            $table->string('ors_number')->nullable();
            $table->unsignedInteger('user_sequence');
            $table->unsignedInteger('accounting_control_number')->nullable();
            
            // Foreign keys
            $table->foreignId('accountingunit_id')->nullable();
            $table->foreignId('focalperson_id');
            $table->foreignId('program_id');
            $table->foreignId('respocenter_id')->nullable();
            
            $table->string('particulars');
            $table->decimal('amount', 15, 2);
            $table->string('status');
            $table->string('tupad_modality')->nullable();
            $table->string('tev_modality')->nullable();
            $table->unsignedBigInteger('locked_by')->nullable();
            $table->timestamp('locked_at')->nullable();
            
            // ORS Number field
        
            
            // History fields
            $table->string('history_reason');
            $table->dateTime('moved_to_history_at');
            $table->foreignId('moved_by')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dv_request_history');
    }
};