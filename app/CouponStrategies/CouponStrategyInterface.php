<?php

namespace App\CouponStrategies;

interface CouponStrategyInterface
{
    public function applyCoupon($price, $value);
}
