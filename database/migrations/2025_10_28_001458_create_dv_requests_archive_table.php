<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_dv_requests_archive_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First check if table doesn't exist to avoid errors
        if (!Schema::hasTable('dv_requests_archive')) {
            Schema::create('dv_requests_archive', function (Blueprint $table) {
                $table->id();
                
                // Basic fields from dv_requests
                $table->string('reference_number');
                $table->string('dv_number')->nullable();
                $table->unsignedInteger('user_sequence');
                $table->unsignedInteger('accounting_control_number')->nullable();
                
                // Foreign keys (make them nullable first to avoid issues)
                $table->foreignId('accountingunit_id')->nullable();
                $table->foreignId('focalperson_id');
                $table->foreignId('program_id');
                $table->foreignId('respocenter_id')->nullable();
                
                $table->string('particulars');
                $table->decimal('amount', 15, 2);
                $table->string('status');
                $table->string('tupad_modality')->nullable();
                $table->string('tev_modality')->nullable();
                
                // Archive-specific fields
                $table->string('original_status');
                $table->text('archive_reason');
                $table->dateTime('completed_at')->nullable();
                $table->dateTime('archived_at');
                $table->foreignId('archived_by')->nullable();
                
                $table->softDeletes();
                $table->timestamps();
                
                // Basic indexes
                $table->index('reference_number');
                $table->index('status');
                $table->index('focalperson_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dv_requests_archive');
    }
};