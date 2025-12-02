<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dv_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique(); 
            $table->string('dv_number')->nullable(); 
            $table->unsignedInteger('user_sequence');
            $table->unique(['focalperson_id', 'user_sequence']);
            $table->unsignedInteger('accounting_control_number')->nullable();
            $table->unique(['accountingunit_id', 'accounting_control_number']);

            $table->foreignId('accountingunit_id')
                    ->nullable()
                    ->constrained('accountingunits')
                    ->restrictOnDelete();
            
            // relations
            $table->foreignId('focalperson_id')
                  ->constrained('focalpersons')
                  ->onDelete('cascade');

            $table->foreignId('program_id')
                  ->constrained('programs')
                  ->onDelete('cascade');
            
            $table->foreignId('respocenter_id')
                ->nullable()
                ->constrained('responcenters')
                ->restrictOnDelete();
            
            $table->string('particulars');
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('submitted');
            $table->string('tupad_modality')->nullable();
            $table->string('tev_modality')->nullable();
            $table->unsignedBigInteger('locked_by')->nullable();
            $table->timestamp('locked_at')->nullable();
            
            // ============ ADD PAYEE FIELDS ============
            $table->string('payee_fname')->nullable(); // Payee first name
            $table->string('payee_mi', 1)->nullable(); // Payee middle initial
            $table->string('payee_lname')->nullable(); // Payee last name
            $table->string('payee_tin_number')->nullable(); // Payee TIN number
            $table->text('payee_address')->nullable(); // Payee address
            
            // Multiple payees support
            $table->boolean('multiple_payees')->default(false); // Flag for multiple payees
            $table->json('payee_data')->nullable(); // Store multiple payees as JSON
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('dv_requests', function (Blueprint $table) {
            $table->dropUnique(['focalperson_id', 'user_sequence']);
            $table->dropColumn(['control_number', 'user_sequence']);
            $table->dropForeign(['respocenter_id']);
            $table->dropColumn('respocenter_id');
            $table->dropForeign(['focalperson_id']);
            $table->dropColumn('focalperson_id');
            $table->dropForeign(['program_id']);
            $table->dropColumn('program_id');
            $table->dropForeign(['accountingunit_id']);
            $table->dropColumn('accountingunit_id');
            $table->dropColumn('reference_number');
            $table->dropColumn('tupad_modality');
            
            // Drop payee fields
            $table->dropColumn(['payee_fname', 'payee_mi', 'payee_lname', 'payee_tin_number', 'payee_address', 'multiple_payees', 'payee_data']);
        });
        
        Schema::dropIfExists('dv_requests');
    }
};