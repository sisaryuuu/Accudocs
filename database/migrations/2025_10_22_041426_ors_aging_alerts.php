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
        Schema::create('ors_aging_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dv_request_id')->constrained('dv_requests')->onDelete('cascade');
            $table->foreignId('ors_request_id')->nullable()->constrained('ors_requests')->onDelete('cascade');
            $table->string('current_status'); // ORS_received, ORS_in_process, ORS_onhold, etc.
            $table->integer('days_in_status');
            $table->integer('threshold_days'); // The threshold that was exceeded
            $table->enum('urgency', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->date('alert_date');
            $table->boolean('acknowledged')->default(false);
            $table->dateTime('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('accountingunits')->onDelete('set null');
            $table->text('acknowledgment_notes')->nullable();
            $table->timestamps();
            
            // Composite unique index to prevent duplicate alerts for same ORS on same day
            $table->unique(['ors_request_id', 'alert_date']);
            $table->index(['current_status', 'urgency']);
            $table->index(['alert_date', 'acknowledged']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ors_aging_alerts');
    }
};