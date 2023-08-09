<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});





//public routes
Route::post('/login',[AuthController::class,'login']);
Route::post('/register',[AuthController::class,'register']);
Route::get('/categories',[\App\Http\Controllers\Api\CategoryController::class,'index']);
Route::get('/categories/{id}',[\App\Http\Controllers\Api\CategoryController::class,'show']);
Route::get('/coupons',[\App\Http\Controllers\Api\CouponController::class,'index']);
Route::get('/products',[\App\Http\Controllers\Api\ProductController::class,'index']);

//private routes
Route::middleware(['auth:sanctum'])->group(function (){
    //user
    Route::get('/user',[AuthController::class,'getUser']);

    Route::post('/logout',[AuthController::class,'logout']);

    //cart
    Route::get('/cart',[\App\Http\Controllers\Api\CartController::class , 'index']);
    Route::post('/cart',[\App\Http\Controllers\Api\CartController::class , 'store']);
    Route::patch('/cart/increaseQuantity',[\App\Http\Controllers\Api\CartController::class , 'increaseQuantity']);
    Route::patch('/cart/decreaseQuantity',[\App\Http\Controllers\Api\CartController::class , 'decreaseQuantity']);
    Route::patch('/cart',[\App\Http\Controllers\Api\CartController::class , 'updateCart']);
    Route::delete('/cart',[\App\Http\Controllers\Api\CartController::class , 'clearCart']);

    //coupon

    Route::post('/coupons/apply',[\App\Http\Controllers\Api\CouponController::class,'applyCoupon']);

    Route::patch('/coupons/unapply',[\App\Http\Controllers\Api\CouponController::class,'unApplyCoupon']);

});


//for admin
Route::prefix('admin')->middleware(['auth:sanctum','role:admin'])->group(function (){
//    Route::resource('/categories',\App\Http\Controllers\Api\CategoryController::class);
    //category

//    Route::get('/categories/{id}',[\App\Http\Controllers\Api\CategoryController::class,'show'])->withoutMiddleware(['auth:sanctum','role:admin']);
    Route::post('/categories',[\App\Http\Controllers\Api\CategoryController::class,'store']);
    Route::patch('/categories/{id}',[\App\Http\Controllers\Api\CategoryController::class,'update']);
    Route::delete('/categories/{id}',[\App\Http\Controllers\Api\CategoryController::class,'destroy']);

//    Route::resource('/products',\App\Http\Controllers\Api\ProductController::class);

    //coupon
    Route::post('/coupons',[\App\Http\Controllers\Api\CouponController::class,'store']);


//    Route::post('/logout',[AuthController::class,'logout']);
});
