<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flight_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('origin', 80);
            $table->string('destination', 80);
            $table->date('departure_date')->nullable();
            $table->date('return_date')->nullable();
            $table->string('cabin')->nullable();
            $table->unsignedTinyInteger('adults')->default(1);
            $table->unsignedInteger('results_count')->default(0);
            $table->boolean('had_error')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['origin', 'destination']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flight_searches');
    }
};
