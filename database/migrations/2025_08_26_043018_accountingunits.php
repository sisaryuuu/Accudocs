<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
         Schema::create('accountingunits', function (Blueprint $table) {
            $table->id();
            $table->string('fname',100); 
            $table->string('mi',1); 
            $table->string('lname',100);
            $table->string('email')->unique();
            $table->string('password');
            $table->string('profile_image')->default('default.png');
            $table->timestamps(); // Add this line for created_at and updated_at
         });
    }
            
     /**
      * Reverse the migrations.
      */  
    public function down(): void
    {
         Schema::dropIfExists('accountingunits');
    }
};