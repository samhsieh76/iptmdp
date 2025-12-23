<?php

namespace App\Http\Controllers;

use id;
use stdClass;
use Exception;
use App\Models\SmellySensor;
use Illuminate\Http\Request;
use App\Models\HandLotionSensor;
use App\Models\TemperatureSensor;
use App\Models\ToiletPaperSensor;
use App\Models\HumanTrafficSensor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ItemNotFoundException;

class LocationSensorController extends Controller {
    protected $model;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->program_name =  "location_sensors";
        $this->middleware(function ($request, $next) {
            $route = $request->route()->getName();
            $route_explode = explode('.', $route);
            if (count($route_explode) == 3) {
                $this->program_name = $route_explode[2];
                $this->route_name = "{$this->program_name}";

                $models = [
                    'toilet_paper_sensors' => ToiletPaperSensor::class,
                    'smelly_sensors' => SmellySensor::class,
                    'human_traffic_sensors' => HumanTrafficSensor::class,
                    'hand_lotion_sensors' => HandLotionSensor::class,
                    'temperature_sensors' => TemperatureSensor::class,
                    'relative_humidity_sensors' => TemperatureSensor::class,
                ];

                if (array_key_exists($this->program_name, $models)) {
                    $this->model = $models[$this->program_name];
                }
            }
            return $next($request);
        });
    }

    public function index(Request $request, $locationId) {

        $fetchLocations = App::make('fetch_locations');
        try {
            $location = $fetchLocations->firstOrFail(function ($location) use ($locationId) {
                return $location->id == $locationId;
            });
        } catch (ItemNotFoundException $e) {
            return abort(404);
        }
        return view('backend.sensors.index', compact('location'));
    }

    public function getResources(Request $request, $locationId) {
        $draw = $request->get('draw');

        $fetchLocations = App::make('fetch_locations');
        try {
            $location = $fetchLocations->firstOrFail(function ($location) use ($locationId) {
                return $location->id == $locationId;
            });

            $columnIndex_arr = $request->get('order');
            $columnName_arr = $request->get('columns');
            $order_arr = $request->get('order');

            $columnIndex = $columnIndex_arr[0]['column']; // Column index
            $columnName = $columnName_arr[$columnIndex]['data']; // Column name
            $columnSortOrder = $order_arr[0]['dir']; // asc or desc

            $searchValue = new stdClass();
            $searchValue->location_id = $location->id;

            $totalRecordswithFilter = $this->model::select('count(*) as allcount')
                ->Search($searchValue)
                ->count();

            $tableName = (new $this->model)->getTable();
            if ($this->program_name == 'human_traffic_sensors') {
                $date = date('Y-m-d');
                $records = $this->model::select("{$tableName}.id", "{$tableName}.name", "toilets.name as toilet_name", "toilets.type as toilet_type", DB::Raw("DATE_FORMAT({$tableName}.latest_updated_at,'%Y/%m/%d %H:%i:%s') as latest_updated_at"), 'report.summary_value as latest_value')
                ->join('toilets', function ($join) use($locationId) {
                    $join->on('toilets.id', '=', (new $this->model)->getTable().".toilet_id")->where('toilets.location_id', '=', $locationId);
                    if (!Auth::user()->can('toilets.full')) {
                        $join->where('toilets.creator_id', '=', Auth::user()->id);
                    }
                })
                ->leftJoin('human_traffic_daily_reports as report', function ($join) use($date) {
                    $join->on('report.human_traffic_sensor_id', '=', (new $this->model)->getTable().".id")->where('report.date', '=', $date);
                })
                ->Search($searchValue)
                ->orderBy($columnName, $columnSortOrder)->get();
            } else {
                $columnQualifier = '';
                if ($this->program_name == 'relative_humidity_sensors') {
                    $columnQualifier = '_humidity';
                }
                // Fetch records
                $records = $this->model::select("{$tableName}.id", "{$tableName}.name", "toilets.name as toilet_name", "toilets.type as toilet_type", "{$tableName}.latest{$columnQualifier}_value as latest_value", "{$tableName}.latest{$columnQualifier}_raw_data as latest_raw_data", DB::Raw("DATE_FORMAT({$tableName}.latest{$columnQualifier}_updated_at,'%Y/%m/%d %H:%i:%s') as latest_updated_at"))
                    ->join('toilets', function ($join) use($locationId) {
                        $join->on('toilets.id', '=', (new $this->model)->getTable().".toilet_id")->where('toilets.location_id', '=', $locationId);
                        if (!Auth::user()->can('toilets.full')) {
                            $join->where('toilets.creator_id', '=', Auth::user()->id);
                        }
                    })
                    ->Search($searchValue)
                    ->orderBy($columnName, $columnSortOrder)->get();
            }

            return $this->dataTableJsonResponse($draw, 0, $totalRecordswithFilter, [
                "aaData" => $records
            ]);
        } catch (Exception $e) {
            return $this->dataTableJsonResponse($draw, 0, 0, [
                "aaData" => []
            ]);
        }
    }
}
