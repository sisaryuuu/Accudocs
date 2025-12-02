<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('active_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('user_type'); // App\Models\AccountingUnit or App\Models\FocalPerson
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('login_at')->nullable();
            $table->timestamp('last_activity')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'user_type']);
            $table->index('last_activity');
        });
    }

    public function down()
    {
        Schema::dropIfExists('active_sessions');
    }
};