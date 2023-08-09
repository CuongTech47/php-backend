<?php

namespace App\Http\Controllers\Api;

use App\CouponStrategies\FixedCouponStrategy;
use App\CouponStrategies\PercentCouponStrategy;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCouponRequest;
use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class CouponController extends Controller
{
    use HttpResponses;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $coupons = Coupon::all();
        return CouponResource::collection($coupons);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCouponRequest $request)
    {
        // Xác thực và lấy dữ liệu từ yêu cầu
        $validatedData = $request->validated();

        // Tạo coupon mới và lưu vào cơ sở dữ liệu
        $coupon = Coupon::create([
            'code' => $validatedData['code'],
            'description' => $validatedData['description'],
            'type' => $validatedData['type'],
            'value' => $validatedData['value'],
            'cart_value' => $validatedData['cart_value'],
        ]);

        // Trả về thông tin coupon vừa được tạo dưới dạng một đối tượng JSON
        return new CouponResource($coupon);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function applyCoupon(Request $request) {
        $coupon = Coupon::where('code',$request->coupon_code)->first();
        if (!$coupon) {
            return $this->error(null, 'Coupon không tồn tại hoặc đã hết hạn',400);
        }
        $code = $coupon->code;
        $cart_value = $coupon->cart_value;
        $value = $coupon->value;


        $cartKey = 'cart:user-' . Auth::id();
        $cartItems = Redis::hgetall($cartKey);
        $cartProducts = [];
        $totalQty = 0;
        $totalCart = 0;


        foreach ($cartItems as $productId => $productData) {
            $product = json_decode($productData, true);
            $cartProducts[] = [
                'product_id' => $productId,
                'qty' => $product['qty'],
                'name' => $product['name'],
                'price' => $product['price'],
                'total_price' => $product['qty'] * $product['price']
            ];

            // Tính tổng số lượng sản phẩm
            $totalQty += $product['qty'];
            // Tính tổng tiền của từng sản phẩm
            $totalCart += $product['qty'] * $product['price'];
        }

        if ($totalCart >= $cart_value ) {
            $finalPrice = $coupon->getPrice($totalCart , $coupon->type , $value);
            $finalCoupon = $totalCart - $finalPrice;
            // Cập nhật thông tin giỏ hàng sau khi tính toán xong vào Redis
//            foreach ($cartItems as $productId => $productData) {
//                $product = json_decode($productData, true);
//                $product['final_price'] = $finalPrice;
//                $product['code'] = $code;
//                $cartItems[$productId] = json_encode($product);
//            }
//            Redis::hmset($cartKey, $cartItems);

            $cartInfo = [
                'final_price' => $finalPrice,
                'code' => $code,
            ];
            Redis::set('cart:user-' . Auth::id() . ':info', json_encode($cartInfo));
            return $this->success([
                'cart_products' => $cartProducts,
                'total_qty' => $totalQty,
                'total_cart_price' => $totalCart,
                'code'=>$code,
                'finalCouponPrice' => $finalCoupon,
                'finalPrice' => $finalPrice,
            ],'Lấy danh sách cart thành công',200);
        } else {
            return $this->error([
                'cart_products' => $cartProducts,
                'total_qty' => $totalQty,
                'total_cart_price' => $totalCart,
                ], 'Áp dụng mã giảm giá không thành công',400);
        }

    }

    public function unApplyCoupon() {
        $cartInfoKey = 'cart:user-' . Auth::id() . ':info';
        $cartInfo = Redis::get($cartInfoKey);

        if (!$cartInfo) {
            return response()->json(['message' => 'Giỏ hàng không có coupon để hủy'], 400);
        }

        Redis::del($cartInfoKey);

        return response()->json(['message' => 'Đã hủy coupon trong giỏ hàng'], 200);
    }




}
