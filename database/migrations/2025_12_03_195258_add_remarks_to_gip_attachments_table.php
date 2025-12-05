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
        Schema::table('gip_attachments', function (Blueprint $table) {
            // Add remarks column for documenting what is lacking
            $table->text('remarks')->nullable()->after('is_present');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gip_attachments', function (Blueprint $table) {
            $table->dropColumn('remarks');
        });
    }
};