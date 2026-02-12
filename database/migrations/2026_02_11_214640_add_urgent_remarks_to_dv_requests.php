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
        $table->text('urgent_remarks')->nullable()->after('marked_urgent_by');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dv_requests', function (Blueprint $table) {
            $table->dropColumn('urgent_remarks');
        });
    }
};
