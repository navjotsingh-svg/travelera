<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cabs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('vehicle_type');
            $table->unsignedTinyInteger('capacity');
            $table->string('city');
            $table->decimal('price_per_km', 8, 2);
            $table->decimal('base_fare', 8, 2);
            $table->string('image');
            $table->text('description');
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cabs');
    }
};
