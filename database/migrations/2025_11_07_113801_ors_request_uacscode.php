<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ors_request_uacscode', function (Blueprint $table) {
            // Add amount column to track individual UACS code amounts
            $table->decimal('amount', 15, 2)->default(0)->after('order');
        });
    }

    public function down(): void
    {
        Schema::table('ors_request_uacscode', function (Blueprint $table) {
            $table->dropColumn('amount');
        });
    }
};