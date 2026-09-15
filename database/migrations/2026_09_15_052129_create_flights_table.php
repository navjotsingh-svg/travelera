<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flights', function (Blueprint $table) {
            $table->id();
            $table->string('airline');
            $table->string('flight_number');
            $table->string('origin');
            $table->string('origin_code', 8);
            $table->string('destination');
            $table->string('destination_code', 8);
            $table->dateTime('departure_at');
            $table->dateTime('arrival_at');
            $table->unsignedInteger('duration_minutes');
            $table->string('cabin_class')->default('economy');
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('seats_available')->default(20);
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flights');
    }
};
