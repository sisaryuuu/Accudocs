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
        Schema::table('dv_requests', function (Blueprint $table) {
           $table->string('qr_code_path')->nullable()->after('reference_number');
             $table->string('qr_code_token')->nullable()->after('qr_code_path');
            $table->timestamp('qr_code_generated_at')->nullable()->after('qr_code_token');
             $table->enum('qr_status', ['inactive', 'active'])->default('inactive')->after('qr_code_token');
             $table->longText('qr_code_data')->nullable()->after('qr_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dv_requests', function (Blueprint $table) {
           $table->dropColumn(['qr_code_path', 'qr_code_token', 'qr_code_generated_at']);
        });
    }
};
