<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('dv_requests', function (Blueprint $table) {
            // ⭐ EXISTING FIELDS (from your code)
            $table->timestamp('last_action_at')->nullable()->after('status');
            $table->string('last_action_type')->nullable()->after('last_action_at');
            $table->unsignedBigInteger('last_acted_by')->nullable()->after('last_action_type');
            
            // ⭐ COMPLETE DEPARTMENT RECEIVED TIMESTAMPS
            // IMSD Department
            $table->timestamp('imsd_received_at')->nullable()->after('last_acted_by');
            $table->timestamp('imsd_verified_at')->nullable()->after('imsd_received_at');
            $table->timestamp('imsd_last_action_at')->nullable()->after('imsd_verified_at');
            
            // GIP Department
            $table->timestamp('gip_received_at')->nullable()->after('imsd_last_action_at');
            $table->timestamp('gip_verified_at')->nullable()->after('gip_received_at');
            $table->timestamp('gip_last_action_at')->nullable()->after('gip_verified_at');
            
            // TUPAD Department
            $table->timestamp('tupad_received_at')->nullable()->after('gip_last_action_at');
            $table->timestamp('tupad_verified_at')->nullable()->after('tupad_received_at');
            $table->timestamp('tupad_last_action_at')->nullable()->after('tupad_verified_at');
            
            // TEV Department
            $table->timestamp('tev_received_at')->nullable()->after('tupad_last_action_at');
            $table->timestamp('tev_verified_at')->nullable()->after('tev_received_at');
            $table->timestamp('tev_last_action_at')->nullable()->after('tev_verified_at');
            
            // Budget Department
            $table->timestamp('budget_received_at')->nullable()->after('tev_last_action_at');
            $table->timestamp('budget_verified_at')->nullable()->after('budget_received_at');
            $table->timestamp('budget_last_action_at')->nullable()->after('budget_verified_at');
            
            // Accountant Department
            $table->timestamp('accountant_received_at')->nullable()->after('budget_last_action_at');
            $table->timestamp('accountant_verified_at')->nullable()->after('accountant_received_at');
            $table->timestamp('accountant_last_action_at')->nullable()->after('accountant_verified_at');
            
            // Outgoing Department
            $table->timestamp('outgoing_received_at')->nullable()->after('accountant_last_action_at');
            $table->timestamp('outgoing_verified_at')->nullable()->after('outgoing_received_at');
            $table->timestamp('outgoing_last_action_at')->nullable()->after('outgoing_verified_at');
            
            // Index for performance
            $table->index('status');
            $table->index('last_action_at');
            $table->index(['status', 'gip_received_at']);
            $table->index(['status', 'tupad_received_at']);
            $table->index(['status', 'tev_received_at']);
            $table->index(['status', 'budget_received_at']);
            $table->index(['status', 'accountant_received_at']);
            $table->index(['status', 'outgoing_received_at']);
            
            $table->foreign('last_acted_by')->references('id')->on('accountingunits')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('dv_requests', function (Blueprint $table) {
            $table->dropForeign(['last_acted_by']);
            
            $table->dropColumn([
                'last_action_at', 'last_action_type', 'last_acted_by',
                'imsd_received_at', 'imsd_verified_at', 'imsd_last_action_at',
                'gip_received_at', 'gip_verified_at', 'gip_last_action_at',
                'tupad_received_at', 'tupad_verified_at', 'tupad_last_action_at',
                'tev_received_at', 'tev_verified_at', 'tev_last_action_at',
                'budget_received_at', 'budget_verified_at', 'budget_last_action_at',
                'accountant_received_at', 'accountant_verified_at', 'accountant_last_action_at',
                'outgoing_received_at', 'outgoing_verified_at', 'outgoing_last_action_at',
            ]);
            
            $table->dropIndex(['status']);
            $table->dropIndex(['last_action_at']);
            $table->dropIndex(['status', 'gip_received_at']);
            $table->dropIndex(['status', 'tupad_received_at']);
            $table->dropIndex(['status', 'tev_received_at']);
            $table->dropIndex(['status', 'budget_received_at']);
            $table->dropIndex(['status', 'accountant_received_at']);
            $table->dropIndex(['status', 'outgoing_received_at']);
        });
    }
};