<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    //

     use HttpResponses;

    public function login(LoginUserRequest $request)
    {
        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
        ];

        if (!Auth::attempt($credentials)) {
            return $this->error(null, 'Credentials do not match', 401);
        }

        $user = User::where('email', $request->email)->first();

        // Giới hạn số lượng token hợp lệ của người dùng
        $maxTokens = 1; // Số lượng token tối đa bạn muốn cho phép
        $userTokens = $user->tokens();

        if ($userTokens->count() >= $maxTokens) {
            // Lấy danh sách các token của người dùng theo thứ tự từ mới đến cũ
            $oldestTokens = $userTokens->oldest()->take($userTokens->count() - $maxTokens + 1)->get();

            // Thu hồi các token cũ
            foreach ($oldestTokens as $token) {
                $token->delete();
            }
        }

        $token = $user->createToken('Api Token of ' . $user->name)->plainTextToken;

        return $this->success([
            'user' =>  $user,
            'token' => $token
        ], 'Đăng Nhập Thành Công', 200);
    }

    public function register(StoreUserRequest $request) {

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        $role = Role::findByName('user');
        $user->assignRole($role);



        return $this->success([
            'user'=>$user,
            'token' => $user->createToken('Api Token of ' . $user->name)->plainTextToken
        ], 'Đăng Ký Thành Công' , 201);

    }

    public function getUser() {
        $user = Auth::user();
        $role = false;
        if ($user->hasRole('user')) {
            $role = false;
        }
        else if ($user->hasRole('admin')) {
            $role = true;
        }

        $response = $this->success([
            'user' =>  $user,
            'isAdmin' => $role
        ], 'Đăng Nhập Thành Công', 200);


        return $response;
    }

    public function logout() {
// Thu hồi token xác thực của người dùng
        if (Auth::check()) {
            Auth::user()->currentAccessToken()->delete();
        }

//        // Đăng xuất người dùng khỏi phiên hiện tại
//        Auth::logout();

        // Gửi phản hồi xác nhận đăng xuất
        return response()->json(['message' => 'Đăng xuất thành công'], 200);
    }

}
