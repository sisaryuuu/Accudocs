<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ors_requests', function (Blueprint $table) {
            $table->id();
            $table->string('ors_number')->nullable()->unique();

            $table->unsignedBigInteger('dv_request_id');
            $table->unsignedBigInteger('focalperson_id')->nullable();
            
            $table->foreignId('accountingunit_id')
                  ->nullable()
                  ->constrained('accountingunits')
                  ->restrictOnDelete();

            $table->foreignId('adlcode_id')
                  ->nullable()
                  ->constrained('adlcodes')
                  ->restrictOnDelete();

            $table->foreignId('allotment_class_id')
                  ->nullable()
                  ->constrained('allotment_classes')
                  ->restrictOnDelete();

            $table->foreignId('mfopap_id')
                  ->nullable()
                  ->constrained('mfopaps')
                  ->restrictOnDelete();

            $table->foreignId('contcurrent_id')
                  ->nullable()
                  ->constrained('contcurrents')
                  ->restrictOnDelete();

            $table->decimal('gross', 15,2)->nullable();
            $table->decimal('net', 15,2)->nullable();
            $table->text('remarks')->nullable();

            $table->string('status')->default('ORS_received');
            $table->unsignedBigInteger('obligated_by')->nullable();
            $table->timestamp('obligated_at')->nullable();

            // ✅ ADD DEPARTMENT-SPECIFIC TIMESTAMPS FOR BUDGET OFFICER
            $table->timestamp('budget_received_at')->nullable();
            $table->timestamp('budget_verified_at')->nullable();
            $table->timestamp('budget_last_action_at')->nullable();
            
            // ✅ OPTIONAL: Keep general timestamps for backward compatibility
            $table->timestamp('received_at')->nullable();
            $table->timestamp('last_action_at')->nullable();

            // Rejection fields (if not already in your table)
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Received fields (if not already in your table)
            $table->unsignedBigInteger('received_by')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ors_requests');
    }
};