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
       Schema::create('dv_request_logs', function (Blueprint $table) {
            $table->id();

            // link to the DV request
            $table->foreignId('dv_request_id')
                ->constrained('dv_requests')
                ->onDelete('cascade');

             $table->foreignId('accountingunit_id')->nullable()->constrained()->onDelete('cascade');

            $table->string('external_actor')->nullable();

            // step in the workflow
            $table->string('action'); 
            

            // optional notes (remarks, reasons, etc.)
            $table->text('remarks')->nullable();

           
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('dv_request_logs', function (Blueprint $table) {
        $table->dropForeign(['accountingunit_id']);
        $table->dropColumn('accountingunit_id');
    });
    }
};
