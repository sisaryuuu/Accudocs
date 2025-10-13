<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ors_requests', function (Blueprint $table) {
            $table->id();
            $table->string('ors_number')->nullable()->unique();

            $table->unsignedBigInteger('dv_request_id');
            $table->unsignedBigInteger('focalperson_id')->nullable();
              $table->foreignId('accountingunit_id')
                    ->nullable()
                    ->constrained('accountingunits')
                    ->restrictOnDelete();

              $table->foreignId('adlcode_id')
                    ->nullable()
                    ->constrained('adlcodes')
                    ->restrictOnDelete();

              $table->foreignId('allotment_class_id')
                    ->nullable()
                    ->constrained('allotment_classes')
                    ->restrictOnDelete();

              $table->foreignId('mfopap_id')
                    ->nullable()
                    ->constrained('mfopaps')
                    ->restrictOnDelete();

              $table->foreignId('contcurrent_id')
                    ->nullable()
                    ->constrained('contcurrents')
                    ->restrictOnDelete();

            $table->decimal('gross', 15,2)->nullable();
            $table->decimal('net', 15,2)->nullable();
            $table->text('remarks')->nullable();

            $table->string('status')->default('ORS_received');
            $table->unsignedBigInteger('obligated_by')->nullable();
            $table->timestamp('obligated_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ors_requests');
    }
};
