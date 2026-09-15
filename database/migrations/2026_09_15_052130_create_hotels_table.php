<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('city');
            $table->string('country');
            $table->string('address');
            $table->text('description');
            $table->unsignedTinyInteger('star_rating')->default(4);
            $table->decimal('guest_rating', 3, 1)->default(8.5);
            $table->decimal('price_per_night', 10, 2);
            $table->unsignedInteger('rooms_available')->default(8);
            $table->json('amenities')->nullable();
            $table->string('image');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
