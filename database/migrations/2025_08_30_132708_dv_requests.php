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
            $table->string('dv_number')->nullable(); 
            
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
    
            // request details
            $table->string('particulars');
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('submitted');
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
         $table->dropForeign(['accountingunit_id']);
        $table->dropColumn('accountingunit_id');

    });
    }
};
