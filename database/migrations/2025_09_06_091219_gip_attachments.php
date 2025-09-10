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
        Schema::create('gip_attachments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('dv_request_id')
                ->constrained('dv_requests')
                ->onDelete('cascade');

      
            $table->string('name'); 
            $table->boolean('is_present')->default(false); // True if attachment is on hand

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
      public function down(): void
    {
        Schema::dropIfExists('gip_attachments');
    }
};
