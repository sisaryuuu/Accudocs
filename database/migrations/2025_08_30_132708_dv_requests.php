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

    });
    }
};
