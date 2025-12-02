<?php
// database/migrations/2024_01_01_add_primary_role_to_accounting_units.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrimaryRoleAccountingUnits extends Migration
{
    public function up()
    {
        Schema::table('accountingunits', function (Blueprint $table) {
            $table->string('primary_role')->nullable();
        });
    }

    public function down()
    {
        Schema::table('accountingunits', function (Blueprint $table) {
            $table->dropColumn('primary_role');
        });
    }
}