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
        Schema::table('notifications', function (Blueprint $table) {
            // Add dv_request_id column after focalperson_id
            $table->unsignedBigInteger('dv_request_id')->nullable()->after('focalperson_id');
            
            // Add foreign key constraint
            $table->foreign('dv_request_id')->references('id')->on('dv_requests')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['dv_request_id']);
            // Then drop the column
            $table->dropColumn('dv_request_id');
        });
    }
};