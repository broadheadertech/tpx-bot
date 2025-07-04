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
        Schema::create('appscript_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('barber');
            $table->string('service');
            $table->string('customer_no');
            $table->string('name');
            $table->string('booking_type');
            $table->string('time');
            $table->string('date');
            $table->string('amount');
            $table->string('mop');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appscript_reports');
    }
};
