<?php

namespace App\Models;




use App\CouponStrategies\FixedCouponStrategy;
use App\CouponStrategies\PercentCouponStrategy;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Coupon extends Model
{
    use HasFactory;

    protected $table = 'coupons';

    protected $fillable = [
        'code',
        'description',
        'type',
        'value',
        'cart_value',
    ];

    public function getPrice($price , $type , $value) {
        if ($type === 'fixed') {
            $strategy = new FixedCouponStrategy();
        }else if ($type === 'percent') {
            $strategy = new PercentCouponStrategy();
        }else {
            return $price;
        }

        return $strategy->applyCoupon($price , $value);
    }
}
