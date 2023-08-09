<?php

namespace App\CouponStrategies;


class PercentCouponStrategy implements CouponStrategyInterface
{
    public function applyCoupon($price, $value)
    {
        return $price - ($price * $value / 100);
    }
}
