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
        Schema::create('ors_requests', function (Blueprint $table) {
            $table->id();
            $table->string('ors_number')->nullable()->unique();

            $table->foreignId('accountingunit_id')
                    ->nullable()
                    ->constrained('accountingunits')
                    ->restrictOnDelete();

            $table->foreignId('focalperson_id')
                    ->constrained('focalpersons')
                    ->onDelete('cascade');

            $table->foreignId('dv_request_id')
                ->constrained('dv_requests')
                ->onDelete('cascade');
                
            //code relations
            $table->foreignID('allotment_class_id')
                ->nullable()
                ->constrained('allotment_classes')
                ->restrictOnDelete();
            $table->foreignID('contcurrent_id')
                ->nullable()
                ->constrained('contcurrents')
                ->restrictOnDelete();
            $table->foreignID('mfopap_id')
                ->nullable()
                ->constrained('mfopaps')
                ->restrictOnDelete();
            $table->foreignID('uacscode_id')
                ->nullable()
                ->constrained('uacscodes')  
                ->restrictOnDelete();
            $table->foreignID('adlcode_id')
                ->nullable()
                ->constrained('adlcodes')
                ->restrictOnDelete();
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ors_requests');
    }
};
