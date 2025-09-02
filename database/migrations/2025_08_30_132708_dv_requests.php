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
            $table->string('dv_number')->nullable(); // assigned at finalization



             $table->foreignId('accounting_unit_id')->nullable()->constrained('accountingunits')->onDelete('cascade');
            
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
    
            // request details
            $table->string('particulars');
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('submitted');

            // workflow
            $table->foreignId('received_by')->nullable()->constrained('accountingunits');
            $table->timestamp('received_at')->nullable();

            $table->foreignId('verified_by')->nullable()->constrained('accountingunits');
            $table->timestamp('verified_at')->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('accountingunits');
            $table->timestamp('approved_at')->nullable();

            $table->foreignId('finalized_by')->nullable()->constrained('accountingunits');
            $table->timestamp('finalized_at')->nullable();

            $table->foreignId('released_by')->nullable()->constrained('accountingunits');
            $table->timestamp('released_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
         Schema::table('dv_requests', function (Blueprint $table) {
        $table->dropForeign(['respocenter_id']);
        $table->dropColumn('respocenter_id');
                $table->dropForeign(['focalperson_id']);
        $table->dropColumn('focalperson_id');
                $table->dropForeign(['program_id']);
        $table->dropColumn('program_id');
         $table->dropForeign(['accounting_unit_id']);
        $table->dropColumn('accounting_unit_id');

    });
    }
};
