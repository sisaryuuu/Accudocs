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

            // who performed the action
             $table->foreignId('accountingunit_id')->nullable()->constrained()->onDelete('cascade');

            // optional: if outside of the system (e.g., Regional Director, Cashier via QR scan)
            $table->string('external_actor')->nullable();

            // step in the workflow
            $table->string('action'); 
            // e.g. "submitted", "received", "verified", "approved", "finalized", "released"

            // optional notes (remarks, reasons, etc.)
            $table->text('remarks')->nullable();

            // auto timestamp for when action happened
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::dropIfExists('dv_request_logs');
         dropConstrainedForeignId('accountingunit_id');
    }
};
