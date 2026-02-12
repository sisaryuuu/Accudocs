<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_urgent_flag_to_dv_requests.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('dv_requests', function (Blueprint $table) {
            $table->boolean('is_urgent')->default(false)->after('status');
            $table->timestamp('marked_urgent_at')->nullable()->after('is_urgent');
            $table->foreignId('marked_urgent_by')->nullable()->after('marked_urgent_at')
                  ->constrained('accountingunits')
                  ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('dv_requests', function (Blueprint $table) {
            $table->dropForeign(['marked_urgent_by']);
            $table->dropColumn(['is_urgent', 'marked_urgent_at', 'marked_urgent_by', 'urgent_remarks']);
        });
    }
};