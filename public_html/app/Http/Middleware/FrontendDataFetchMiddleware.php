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

class FrontendDataFetchMiddleware {
    public $with;
    public $selectColumns;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next) {
        $currentUserId = Auth::user()->id;
        $this->selectColumns = ['locations.id', 'locations.county_id', 'locations.name', 'locations.image', 'locations.business_hours', 'locations.administration_id'];
        $this->with =  [
            'county' => function ($query) {
                $query->select('id', 'code', 'name', 'region_id');
            },
            'administrator' => function ($query) {
                $query->select('id', 'name');
            }
        ];
        $guardName = Auth::getDefaultDriver(); // 獲取guard
        $esmsConfig = config('esms');
        if ($guardName == $esmsConfig['guard']) {
            $user = Auth::guard($esmsConfig['guard'])->user();
            App::singleton('fetch_locations', function () use($user, $esmsConfig) {
                $query = Location::select($this->selectColumns)->with($this->with);
                if ($user->auth_level != $esmsConfig['auth_levels']['all_areas']) {
                    $query->where('county_id', '=', $user->county_id);
                }
                return $query->get();
            });
        } else {
            App::singleton('fetch_locations', function () use ($currentUserId) {
                $user = User::where('id', $currentUserId)->with('children')->first();
                $locations = collect();

                if (Auth::user()->can('locations.full')) {
                    $locations = Location::select($this->selectColumns)->with($this->with)->get();
                } else {
                    $this->getNestedChildrenLocations($locations, [$user]);
                    $userAuthorizedLocations = Location::UserAuthorized(Auth::user()->id)->select($this->selectColumns)->with($this->with)->get();
                    $locations = $locations->merge($userAuthorizedLocations);
                }
                // 無廁所仍然可以顯示
                /* $fetch_locations = $locations->filter(function ($location) {
                    return $location->toilets->count() > 0;
                }); */
                return $locations;
            });
        }
        $locations = App::make('fetch_locations');
        View::share('fetch_locations', $locations);
        return $next($request);
    }

    private function getNestedChildrenLocations(&$locations, $users) {
        foreach ($users as $user) {
            $children = $user->children;
            if ($children->isNotEmpty()) {
                $this->getNestedChildrenLocations($locations, $children);
            }
            if (count($user->locations) > 0) {
                $userLocations = $user->locations()->select($this->selectColumns)->with($this->with)->get();
                $locations = $locations->merge($userLocations);
            }
        }
    }
}
