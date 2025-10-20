<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('budget_obligation_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dv_request_id')->constrained('dv_requests')->onDelete('cascade');
            $table->foreignId('ors_request_id')->nullable()->constrained('ors_requests')->onDelete('cascade');
            $table->foreignId('verified_by')->constrained('accountingunits')->onDelete('cascade');
            $table->enum('verification_step', ['receive', 'process', 'obligate', 'onhold', 'revise', 'reject']);
            $table->string('status_before');
            $table->string('status_after');
            $table->json('verification_data')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('verified_at');
            $table->timestamps();
            
            $table->index(['dv_request_id', 'verification_step']);
            $table->index('verified_by');
            $table->index('ors_request_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('budget_obligation_history');
    }
};