<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('destinations', function (Blueprint $table) {
            $table->string('iata_code', 8)->nullable()->after('city');
        });

        $codes = [
            'Goa' => 'GOI',
            'Jaipur' => 'JAI',
            'Mumbai' => 'BOM',
            'Delhi' => 'DEL',
            'Bengaluru' => 'BLR',
            'Dubai' => 'DXB',
            'Paris' => 'PAR',
            'Bali' => 'DPS',
            'Singapore' => 'SIN',
            'London' => 'LON',
            'Tokyo' => 'TYO',
        ];

        foreach ($codes as $city => $code) {
            DB::table('destinations')->where('city', $city)->update(['iata_code' => $code]);
        }
    }

    public function down(): void
    {
        Schema::table('destinations', function (Blueprint $table) {
            $table->dropColumn('iata_code');
        });
    }
};
