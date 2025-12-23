<?php
namespace App\Utils;

final class ErrorCodeUtils {
    const SUCCESS = 200;
    const CREATED = 201;
    const NO_CONTENT = 204;
    const BAD_REQUEST = 400;
    const TOKEN_INVALID = 401;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const CONFLICT = 409;
    const GONE = 410;
    const RANGE_NOT_SATISFIABLE = 416;
    const UNPROCESSABLE_ENTITY = 422;
    const FAILURE = 500;

    /**
     * 輸出
     *
     * @param int $code
     * @param array $data
     * @param string|array $error
     * @param integer $code
     * @return \Illuminate\Http\JsonResponse
     */
    public static function jsonResponse($error_code, $data = null, $errors = null, $code = 200) {
        $result['code'] = $error_code;
        if ($data !== null) {
            $result['data'] = $data;
        }
        if ($errors !== null) {
            $result['errors'] = $errors;
        }

        return response()->json(
            $result,
            $code,
            ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
}