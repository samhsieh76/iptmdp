<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller {
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('guest')->except('logout');
    }

    public function username() {
        return 'username';
    }


    protected function credentials(Request $request) {
        // 取得使用者輸入的登入資訊
        $credentials = $request->only($this->username(), 'password');

        // 在這裡加入判斷帳號是否被停用（軟刪除）的邏輯
        $user = User::where($this->username(), $request->{$this->username()})->first();
        if ($user && $user->deleted_at) {
            // 如果帳號已被停用（軟刪除），則返回錯誤提示
            return array_merge($credentials, ['active' => false]);
        }

        return $credentials;
    }

    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogin(Request $request) {
        $validator = Validator::make($request->all(), [
            $this->username() => 'required|string',
            'password' => 'required|string',
        ], [], [
            $this->username() => trans('messages.user_name'),
            'password' => trans('messages.password'),
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    protected function sendFailedLoginResponse(Request $request) {
        $user = User::where($this->username(), $request->{$this->username()})->withTrashed()->first();

        if ($user && $user->deleted_at) {
            // 帳號已被停用（軟刪除），顯示相應的錯誤訊息
            throw ValidationException::withMessages([
                $this->username() => [trans('auth.account_inactive')],
            ]);
        }

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }
}
