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
        Schema::create('focalpersons', function (Blueprint $table) {
            $table->id();
            $table->string('fname', 100); 
            $table->string('mi', 50)->nullable(); // Made nullable for consistency
            $table->string('lname', 100);
            $table->string('TIN_number', 15)->unique();
            $table->string('Address')->nullable();
            $table->string('email')->unique();
            $table->string('profile_image')->default('default.png');
            $table->string('password');
            $table->string('contactnumber', 11)->unique();
            $table->unsignedBigInteger('respocenter_id')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            
            // ✅ Add remember token for "remember me" functionality
            $table->rememberToken();
            
            // ✅ Add concurrent login tracking fields
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->timestamp('last_logout_at')->nullable();
            $table->string('session_id')->nullable();
            
            $table->foreign('respocenter_id')->references('id')->on('responcenters')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('focalpersons');
    }
};