<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uacscodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')
                  ->nullable()
                  ->constrained('uacscodes')
                  ->nullOnDelete();
            $table->string('uacscode');
            $table->text('description');
            $table->integer('level')->default(0); // 0=root, 1=category, 2=item, etc.
            $table->boolean('is_selectable')->default(true); // Only leaf nodes selectable
            $table->integer('sort_order')->default(0); // For custom ordering
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uacscodes');
    }
};