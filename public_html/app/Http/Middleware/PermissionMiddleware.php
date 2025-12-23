<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next) {

        $route = $request->route()->getName();
        $route = str_replace("update", "edit", $route);
        $route = str_replace("store", "create", $route);
        $route = str_replace("recreate", "restore", $route);

        $route_explode = explode('.', $route);
        if (count($route_explode) >= 2) {
            $can = implode(".", [$route_explode[0], $route_explode[1]]);
        }

        // 如果使用者不是super user 且沒有權限
        if (!Auth::user()->isSuperUser() && !Auth::user()->can($can)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response(['code' => 403]);
            }
            return abort('403');
        }
        return $next($request);
    }
}
