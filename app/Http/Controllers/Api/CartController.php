<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;

use Cart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;


class CartController extends Controller
{
    use HttpResponses;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $cartKey = 'cart:user-' . Auth::id();
        $cartItems = Redis::hgetall($cartKey);

        $cartProducts = [];
        $totalQty = 0;
        $totalCart = 0;
        $finalPrice = 0;
        $code = null;

        $cartInfo = Redis::get('cart:user-' . Auth::id() . ':info');
        $cartInfo = json_decode($cartInfo, true);

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

        // Kiểm tra nếu có giá cuối cùng và mã giảm giá trong dữ liệu giỏ hàng
        // thì gán giá trị tương ứng vào $finalPrice và $code
        if (isset($cartInfo['final_price'])) {
            $finalPrice = $cartInfo['final_price'];
        } else {
            $finalPrice = $totalCart;
        }

        if (isset($cartInfo['code'])) {
            $code = $cartInfo['code'];
        }

        return $this->success([
            'cart_products' => $cartProducts,
            'total_qty' => $totalQty,
            'total_cart_price' => $totalCart,
            'code' => $code,
            'final_price' => $finalPrice,
        ], 'Lấy danh sách cart thành công', 200);
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
    public function store(Request $request)
    {
        $product_id = $request->product_id;


        $product = Product::find($product_id);




        // Tạo key duy nhất cho cart của người dùng (có thể sử dụng user ID hoặc session ID)
        $cartKey = 'cart:user-' . Auth::id();

        // Lấy dữ liệu hiện có trong cart của người dùng
        $cartItems = Redis::hgetall($cartKey);

        // Kiểm tra xem sản phẩm có tồn tại trong giỏ hàng chưa
        if (array_key_exists($product_id, $cartItems)) {
            // Nếu sản phẩm đã tồn tại, tăng số lượng (qty) lên 1
            $productData = json_decode($cartItems[$product_id], true);
            if ($productData['qty'] >= $product->quantity) {
                return $this->error($productData, 'Không thể thêm vào cart', 400);
            }else{
                $productData['qty'] ++;
                $cartItems[$request->product_id] = json_encode($productData);
//                $cartItems['']'total_price' => $product['qty'] * $product['price']
                Redis::hmset($cartKey, $cartItems);
                return $this->success($productData, 'Cập nhật thành công', 200);
            }
        } else {
            // Nếu sản phẩm chưa tồn tại, thêm sản phẩm mới vào giỏ hàng
            $product_name = $product->name;
            $product_price = $product->regular_price;
            $cart = [
                'name' => $product_name,
                'qty' => 1,
                'price' => $product_price,

            ];

            // Lưu thông tin sản phẩm vào cart trong Redis
            Redis::hset($cartKey, $product_id, json_encode($cart));
            $productData = json_decode(json_encode($cart), true);
            return $this->success($productData, 'Cart tạo thành công', 201);
        }


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


    public function increaseQuantity(Request $request) {
        $cartKey = 'cart:user-' . Auth::id();

        $product = Product::find($request->product_id);



        // Lấy tất cả các sản phẩm trong cart của người dùng
        $cartItems = Redis::hgetall($cartKey);


        $productData = json_decode($cartItems[$request->product_id], true);


        if ($productData['qty'] >= $product->quantity) {
            return $this->error($productData, 'Không thể thêm vào cart', 400);
        } else{
           $productData['qty'] ++;
           $cartItems[$request->product_id] = json_encode($productData);
           Redis::hmset($cartKey, $cartItems);
           return $this->success($productData, 'Cập nhật thành công', 200);
       }


    }
    public function decreaseQuantity(Request $request) {
        $cartKey = 'cart:user-' . Auth::id();



        // Lấy tất cả các sản phẩm trong cart của người dùng
        $cartItems = Redis::hgetall($cartKey);


        $productData = json_decode($cartItems[$request->product_id], true);


        $productData['qty'] --;

        if($productData['qty'] == 0) {
            Redis::hdel($cartKey, $request->product_id);
        }else{
            $cartItems[$request->product_id] = json_encode($productData);
            Redis::hmset($cartKey, $cartItems);
        }





        return $this->success($productData, 'Cập nhật thành công', 200);
    }
    public function clearCart()
    {
        $cartKey = 'cart:user-' . Auth::id();

        // Xóa key chứa giỏ hàng của người dùng
        Redis::del($cartKey);

        // Xóa key chứa thông tin giảm giá của người dùng
        $cartInfoKey = 'cart:user-' . Auth::id() . ':info';
        Redis::del($cartInfoKey);

        return response()->json(['message' => 'Đã xóa toàn bộ giỏ hàng'], 200);
    }

    public function updateCart(Request $request) {
//        dd($request->product_quantity);

        $cartKey = 'cart:user-' . Auth::id();

        // Lấy dữ liệu hiện có trong cart của người dùng
        $cartItems = Redis::hgetall($cartKey);

        $productData = json_decode($cartItems[$request->product_id], true);
    }
}
