<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('paypal_order_id')->nullable()->after('stripe_payment_intent_id');
            $table->string('paypal_capture_id')->nullable()->after('paypal_order_id');
            $table->index('paypal_order_id');
        });

        Schema::table('checkout_attempts', function (Blueprint $table) {
            $table->string('paypal_order_id')->nullable()->after('stripe_checkout_session_id');
            $table->index('paypal_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['paypal_order_id']);
            $table->dropColumn(['paypal_order_id', 'paypal_capture_id']);
        });

        Schema::table('checkout_attempts', function (Blueprint $table) {
            $table->dropIndex(['paypal_order_id']);
            $table->dropColumn(['paypal_order_id']);
        });
    }
};
