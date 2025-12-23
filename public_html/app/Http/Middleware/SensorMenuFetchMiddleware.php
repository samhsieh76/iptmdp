<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Toilet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SensorMenuFetchMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next) {
        $path = $request->path();
        $locations = App::make('fetch_locations');
        $toilet_id = $request->route('toilet');
        $location_id = $request->route('location');
        try {
            $toilet = Toilet::findOrFail($toilet_id);
            if (!Auth::user()->can('toilets.full') && $toilet->creator_id != Auth::user()->id) {
                return abort(403);
            }
            $location_id = $toilet_id ? $toilet->location_id : $request->route('location');
            $location = $locations->firstOrFail(function ($location) use ($location_id) {
                return $location->id == $location_id;
            });
            $sensor_menus = $this->getMenus($path, $toilet);
        } catch (ModelNotFoundException $e) {
            return abort(404);
        } catch (ItemNotFoundException $e) {
            $sensor_menus = [];
        }
        View::share('toilet', $toilet);
        View::share('location', $location);
        View::share('sensor_menus', $sensor_menus);

        App::singleton('sensor_menus', function () use($sensor_menus) {
            return $sensor_menus;
        });
        return $next($request);
    }

    private function getMenus($path, $toilet) {
        $sensor_menus = [];
        $sensors = [
            'toilet_paper',
            'smelly',
            'human_traffic',
            'hand_lotion',
            'temperature',
            'relative_humidity'
        ];
        foreach ($sensors as $sensor) {
            if (Auth::user()->can("{$sensor}_sensors.index") || Auth::user()->can("{$sensor}_sensors.show")) {
                array_push($sensor_menus, [
                    'icon' => $sensor,
                    'name' => "{$sensor}_sensors",
                    'path' => route("{$sensor}_sensors.index", [$toilet->id]),
                    'active' => preg_match("/{$sensor}_sensors\/*\S*/", $path) == 1? true: false,
                    'children' => $this->getToiletSensorMenus($path, $sensor, $toilet)
                ]);
            }
        }
        return $sensor_menus;
    }

    private function getToiletSensorMenus($path, $sensor, $toilet) {
        if (!Auth::user()->can("{$sensor}_sensors.show")) {
            return [];
        }
        $table = ($sensor != "relative_humidity")?"{$sensor}_sensors":"temperature_sensors";
        $sensors = DB::table($table)->where('toilet_id', '=', $toilet->id)->select('id', 'name')->get();
        foreach ($sensors as $item) {
            $item->active = preg_match("/{$sensor}_sensors\/{$item->id}\/*\S*/", $path) == 1? true: false;
            $item->path = route("{$sensor}_sensors.show", [$toilet->id, $item->id]);
        }
        return $sensors;
    }
}