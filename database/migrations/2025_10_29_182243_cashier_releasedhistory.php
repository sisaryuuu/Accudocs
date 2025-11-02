<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cashier_releasedhistory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dv_request_id')->constrained('dv_requests')->onDelete('cascade');
            $table->foreignId('released_by')->constrained('accountingunits')->onDelete('cascade');
            $table->enum('release_step', ['received','released']);
            $table->string('status_before');
            $table->string('status_after');
            $table->json('release_data')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('released_at');
            $table->timestamps();
            
            $table->index(['dv_request_id', 'release_step']);
            $table->index('released_by');
            $table->index('released_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cashier_releasedhistory');
    }
};