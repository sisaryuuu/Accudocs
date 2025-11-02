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
            $table->boolean('confirmed_by_focal_person')->default(false)->after('status');
            $table->timestamp('confirmed_at')->nullable()->after('confirmed_by_focal_person');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dv_requests', function (Blueprint $table) {
            $table->dropColumn(['confirmed_by_focal_person', 'confirmed_at']);
        });
    }
};