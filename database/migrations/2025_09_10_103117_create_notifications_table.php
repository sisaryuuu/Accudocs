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
            Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('focalperson_id')
            ->constrained('focalpersons') // specify the correct table name
            ->onDelete('cascade');
            $table->string('status');
            $table->string('action');
            $table->text('remarks')->nullable();
            $table->timestamp('read_at')->nullable(); // <- track read/unread
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
