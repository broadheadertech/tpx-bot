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
        Schema::create('reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('barber_id');
            $table->unsignedBigInteger(column: 'service_id');
            $table->foreign('barber_id')->references('id')->on('barbers')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('barbers')->onDelete('cascade');
            $table->string('customer_no');
            $table->string('name');
            $table->string('booking_type');
            $table->string('time');
            $table->string('date');
            $table->string('amount');
            $table->string('mop');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
