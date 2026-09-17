<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        DB::table('settings')->insert([
            'key' => 'platform_fee_percent',
            'value' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('base_amount', 12, 2)->nullable()->after('total_amount');
            $table->decimal('platform_fee_percent', 5, 2)->nullable()->after('base_amount');
            $table->decimal('platform_fee_amount', 12, 2)->nullable()->after('platform_fee_percent');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['base_amount', 'platform_fee_percent', 'platform_fee_amount']);
        });

        Schema::dropIfExists('settings');
    }
};
