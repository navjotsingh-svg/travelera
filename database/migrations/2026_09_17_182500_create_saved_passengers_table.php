<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_passengers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 10)->default('mr');
            $table->string('given_name');
            $table->string('family_name');
            $table->string('gender', 1)->default('m');
            $table->date('born_on')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number', 30)->nullable();
            $table->string('passport_country')->nullable();
            $table->string('passport_number', 40)->nullable();
            $table->date('passport_expiry')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'given_name', 'family_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_passengers');
    }
};
