<?php




namespace App\CouponStrategies;

class FixedCouponStrategy implements CouponStrategyInterface
{
    public function applyCoupon($price, $value)
    {
        return $price - $value;
    }
}
