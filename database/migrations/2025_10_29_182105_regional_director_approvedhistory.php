<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('regional_director_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dv_request_id')->constrained('dv_requests')->onDelete('cascade');
            $table->foreignId('approved_by')->constrained('accountingunits')->onDelete('cascade');
            $table->enum('approval_step', ['received','signed_approved']);
            $table->string('status_before');
            $table->string('status_after');
            $table->json('approval_data')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('approved_at');
            $table->timestamps();
            
            $table->index(['dv_request_id', 'approval_step']);
            $table->index('approved_by');
            $table->index('approved_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('regional_director_history');
    }
};