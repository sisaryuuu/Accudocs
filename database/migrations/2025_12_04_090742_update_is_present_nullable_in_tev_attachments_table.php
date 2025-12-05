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
        Schema::table('tev_attachments', function (Blueprint $table) {
            $table->boolean('is_present')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tev_attachments', function (Blueprint $table) {
            $table->boolean('is_present')->nullable(false)->default(false)->change();
        });
    }
};
