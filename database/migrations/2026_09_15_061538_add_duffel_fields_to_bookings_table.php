<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('provider')->default('local')->after('user_id');
            $table->string('duffel_offer_id')->nullable()->after('provider');
            $table->string('duffel_order_id')->nullable()->after('duffel_offer_id');
            $table->string('airline_pnr')->nullable()->after('duffel_order_id');
            $table->string('currency', 3)->default('INR')->after('total_amount');
            $table->json('snapshot')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'provider',
                'duffel_offer_id',
                'duffel_order_id',
                'airline_pnr',
                'currency',
                'snapshot',
            ]);
        });
    }
};
