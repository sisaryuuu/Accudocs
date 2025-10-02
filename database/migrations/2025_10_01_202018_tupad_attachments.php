<?php
// Migration File: create_tupad_attachments_table.php

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
        Schema::create('tupad_attachments', function (Blueprint $table) {
            $table->id();
              $table->foreignId('dv_request_id')
                ->nullable()
                ->constrained('dv_requests')
                ->onDelete('cascade');

            $table->string('name');
            $table->enum('modality', [
                'rpmt',
                'lgu',
                'ngo_po',
                'money_remittance',
                'individual_beneficiaries',
                'group_beneficiaries',
            ]);
            
            $table->boolean('is_present')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tupad_attachments');
    }
};

