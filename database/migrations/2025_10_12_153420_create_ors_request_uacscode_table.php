<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ors_request_uacscode', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ors_request_id')
                  ->constrained('ors_requests')
                  ->cascadeOnDelete();
            $table->foreignId('uacscode_id')
                  ->constrained('uacscodes')
                  ->cascadeOnDelete();
            $table->integer('order')->default(0); 
            $table->timestamps();
        
            $table->unique(['ors_request_id', 'uacscode_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ors_request_uacscode');
    }
};