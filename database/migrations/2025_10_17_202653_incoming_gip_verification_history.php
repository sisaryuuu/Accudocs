<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('gip_verification_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dv_request_id')->constrained('dv_requests')->onDelete('cascade');
            $table->foreignId('verified_by')->constrained('accountingunits');
            $table->enum('verification_step', ['receive', 'process', 'verify', 'reject']);
            $table->string('status_before');
            $table->string('status_after');
            $table->json('verification_data')->nullable(); 
            $table->text('remarks')->nullable();
            $table->timestamp('verified_at');
            $table->timestamps();
            
            $table->index(['dv_request_id', 'verification_step']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('gip_verification_history');
    }
};