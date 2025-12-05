<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gip_attachments', function (Blueprint $table) {
            $table->boolean('is_present')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('gip_attachments', function (Blueprint $table) {
            $table->boolean('is_present')->nullable(false)->default(false)->change();
        });
    }
};