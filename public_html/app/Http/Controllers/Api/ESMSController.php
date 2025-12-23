<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\County;
use App\Models\Location;
use Illuminate\Http\Request;
use App\Utils\ErrorCodeUtils;
use InvalidArgumentException;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ESMSController extends Controller {
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
        // $this->middleware('guest:esms')->except('logout');
    }

    public function getRegion($regionCode) {
        try {
            $county = County::where('code', $regionCode)->firstOrFail();
            $locations = Location::where('county_id', '=', $county->id)->get();
            if ($locations->count() <= 0) {
                return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NO_CONTENT);
            }
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (Exception $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, [
            'name' => $county->name,
            'region_code' => $county->code
        ]);
    }

    public function login(Request $request) {
        try {
            $allowedDomains = config('esms.allowed_domains'); // 可接受的域名列表
            $currentDomain = parse_url($request->headers->get('Referer'), PHP_URL_HOST); // 當前請求域名
            $currentIp = gethostbyname($currentDomain);
            if (!in_array($currentDomain, $allowedDomains) && !in_array($currentIp, $allowedDomains)) {
                throw new UnauthorizedHttpException("Unauthorized", "Domain: {$currentDomain}<br>IP: {$currentIp}");
            }
            /* $encryptedData = $request->get('data');
            $data = $this->decrypted($encryptedData);
            if (!$data) {
                return abort(400);
            } */
            $data = $request->all();
            $validator = Validator::make($data, [
                'auth_level' => 'required|in:1,2',
                'region_code' => 'nullable|required_if:auth_level,2|exists:counties,code',
                'name' => 'required|max:200',
            ]);

            if ($validator->fails()) {
                throw new InvalidArgumentException('Invalid request data');
            }
            $authLevel = $data['auth_level'];
            $regionCode = $data['region_code'] ?? null;
            $name = $data['name'];

            $user = $this->attemptLogin($authLevel, $regionCode, $name);

            if ($user) {
                $encryptedUserData = Crypt::encryptString(json_encode(['user' => $user]));
                $request->session()->put('esms_session', $encryptedUserData);
                return redirect()->intended('/dashboard');
            }
            throw new NotFoundHttpException('User not found');
        } catch (UnauthorizedHttpException $e) {
            return abort(401, $e->getMessage()); // Handle UnauthorizedHttpException and return 401 status
        } catch (InvalidArgumentException $e) {
            return abort(400); // Handle InvalidArgumentException and return 400 status
        } catch (NotFoundHttpException $e) {
            return abort(404); // Handle NotFoundHttpException and return 404 status
        } catch (Exception $e) {
            return abort(500); // Handle other exceptions and return 500 status
        }
    }

    protected function attemptLogin($authLevel, $regionCode, $name) {
        $attemptData = [
            'auth_level' => $authLevel,
            'name' => $name,
        ];
        $esmsConfig = config('esms');
        if ($authLevel != $esmsConfig['auth_levels']['all_areas']) {
            $county = County::where('code', $regionCode)->first();
            if ($county) {
                $attemptData['county_id'] = $county->id;
            } else {
                return null;
            }
        }
        if (Auth::guard($esmsConfig['guard'])->attempt($attemptData)) {
            Auth::guard('web')->logout();
            return Auth::guard($esmsConfig['guard'])->user();
        }
        return null;
    }

    public function encrypted() {
        $rootPath = base_path();
        $publicKeyPath = $rootPath . '/public_key.pem';
        // 服務器公鑰
        $publicKey = file_get_contents($publicKeyPath);

        // 要加密的数据
        $data = json_encode([
            'auth_level' => 1,
            'region_code' => 'G',
            'name' => 'TEST'
        ]);

        // 使用公鑰進行加密
        if (openssl_public_encrypt($data, $encryptedData, $publicKey, OPENSSL_PKCS1_OAEP_PADDING)) {
            // 加密成功
            $base64EncryptedData = base64_encode($encryptedData);
            echo $base64EncryptedData;
        } else {
            echo '加密失敗';
        }
    }

    public function decrypted($encryptedData) {
        $rootPath = base_path();
        $privateKeyPath = $rootPath . '/private_key.pem';
        // 讀取服務器的私鑰
        $privateKey = openssl_pkey_get_private('file://' . $privateKeyPath, config('esms.passphrase'));
        // 解密数据
        if (openssl_private_decrypt(base64_decode($encryptedData), $decryptedData, $privateKey, OPENSSL_PKCS1_OAEP_PADDING)) {
            // 關閉私鑰資源
            openssl_free_key($privateKey);
            return json_decode($decryptedData, true);
        } else {
            return false;
        }
    }
}
