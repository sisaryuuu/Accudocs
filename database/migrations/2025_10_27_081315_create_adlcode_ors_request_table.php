<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration creates a pivot table to support multiple ADL codes per ORS request.
     * The table maintains the order of ADL codes and prevents duplicate entries.
     */
    public function up(): void
    {
        Schema::create('adlcode_ors_request', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ors_request_id');
            $table->unsignedBigInteger('adlcode_id');
            $table->integer('order')->default(1)->comment('Order of ADL code in the list');
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('ors_request_id')
                  ->references('id')
                  ->on('ors_requests')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
                  
            $table->foreign('adlcode_id')
                  ->references('id')
                  ->on('adlcodes')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
            
            // Prevent duplicate ADL codes for the same ORS request
            $table->unique(['ors_request_id', 'adlcode_id'], 'unique_ors_adl');
            
            // Add indexes for better query performance
            $table->index('ors_request_id', 'idx_ors_request');
            $table->index('adlcode_id', 'idx_adlcode');
            $table->index('order', 'idx_order');
        });
        
        // Add comment to table
        DB::statement("ALTER TABLE adlcode_ors_request COMMENT = 'Pivot table for many-to-many relationship between ORS requests and ADL codes'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adlcode_ors_request');
    }
};