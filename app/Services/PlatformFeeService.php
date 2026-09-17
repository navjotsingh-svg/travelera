<?php

namespace App\Services;

use App\Models\Setting;

class PlatformFeeService
{
    public function percent(): float
    {
        return max(0, min(100, (float) Setting::getValue('platform_fee_percent', 0)));
    }

    /**
     * @return array{base_amount: float, platform_fee_percent: float, platform_fee_amount: float, total_amount: float}
     */
    public function breakdown(float|string $baseAmount): array
    {
        $base = round((float) $baseAmount, 2);
        $percent = $this->percent();
        $fee = round($base * ($percent / 100), 2);

        return [
            'base_amount' => $base,
            'platform_fee_percent' => $percent,
            'platform_fee_amount' => $fee,
            'total_amount' => round($base + $fee, 2),
        ];
    }

    public function withFee(float|string $baseAmount): float
    {
        return $this->breakdown($baseAmount)['total_amount'];
    }
}
