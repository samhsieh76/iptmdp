<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use App\Models\Location;
use Illuminate\Http\Request;
use App\Models\LocationSupplier;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ItemNotFoundException;

class DataFetchMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next) {
        $path = $request->path();

        $guardName = Auth::getDefaultDriver(); // 獲取guard
        $esmsConfig = config('esms');
        if ($guardName == $esmsConfig['guard']) {
            $user = Auth::guard($esmsConfig['guard'])->user();
            App::singleton('fetch_locations', function () use($user, $esmsConfig) {
                $query = Location::select('id', 'name', 'image', 'county_id');
                if ($user->auth_level != $esmsConfig['auth_levels']['all_areas']) {
                    $query->where('county_id', '=', $user->county_id);
                }
                return $query->get();
            });
            $bind_locations = [];
        } else {
            $currentUserId = Auth::user()->id;
            App::singleton('fetch_locations', function () use($currentUserId) {
                $user = User::where('id', $currentUserId)->with('children')->first();
                $locations = collect();
                if (Auth::user()->can('locations.full')) {
                    $locations = Location::select('id', 'name', 'image', 'county_id')->get();
                } else {
                    $this->getNestedChildrenLocations($locations, [$user]);
                    $locations = $locations->merge(LocationSupplier::UserAuthorizedLocations(Auth::user()->id)->get());
                }
                return $locations;
            });
            $bind_locations = Auth::user()->locations()->select('id', 'name', 'image', 'address', 'auth_code')->get();
        }
        $locations = App::make('fetch_locations');

        View::share('bind_locations', $bind_locations);
        View::share('fetch_locations', $locations);
        View::share('navbar_menus', $this->getMenus($path, $locations));

        return $next($request);
    }

    private function getMenus($path, $locations) {
        $menus = [];
        if (Auth::user()->can('users.index')) {
            array_push($menus, [
                'name' => trans('messages.user_management'),
                'path' => route('users.index'),
                'active' => preg_match('/^users\/*\S*/', $path)
            ]);
        }

        if (Auth::user()->can('locations.index') || Auth::user()->can('toilets.index') && count($locations) > 0) {
            array_push($menus, [
                'name' => trans('messages.backend'),
                'path' => Auth::user()->can('locations.index') ? route('locations.index') : route('toilets.index', [$locations[0]->id]),
                'active' => preg_match('/^(locations|toilets|sensors)\/*\S*/', $path)
            ]);
        }

        if (Auth::user()->can('api_and_serves.index')) {
            array_push($menus, [
                'name' => trans('messages.api_and_serve'),
                'path' => route('api_and_serves.index'),
                'active' => preg_match('/^api_and_serves\/*\S*/', $path)
            ]);
        }

        array_push($menus, [
            'name' => trans('messages.frontend'),
            'path' => route('dashboard'),
            'active' => false
        ]);
        return $menus;
    }

    private function getNestedChildrenLocations(&$locations, $users) {
        foreach ($users as $user) {
            $children = $user->children;
            if ($children->isNotEmpty()) {
                $this->getNestedChildrenLocations($locations, $children);
            }
            if (count($user->locations) > 0) {
                $locations = $locations->merge($user->locations()->select('id', 'name', 'image', 'county_id')->get());
            }
        }
    }
}