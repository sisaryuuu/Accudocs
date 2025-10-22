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
        Schema::create('document_aging_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dv_request_id')->constrained('dv_requests')->onDelete('cascade');
            $table->integer('days_old');
            $table->enum('urgency', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->date('alert_date');
            $table->timestamps();
            
            // Composite unique index to prevent duplicate alerts for same document on same day
            $table->unique(['dv_request_id', 'alert_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::dropIfExists('document_aging_alerts');
    }
};
