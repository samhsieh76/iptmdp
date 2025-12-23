<?php

namespace App\Http\Controllers;

use stdClass;
use App\Models\Toilet;
use App\Models\SmellySensor;
use Illuminate\Http\Request;
use App\Utils\ErrorCodeUtils;
use App\Models\HandLotionSensor;
use App\Models\TemperatureSensor;
use App\Models\ToiletPaperSensor;
use App\Models\HumanTrafficSensor;
use Illuminate\Support\Facades\App;
use App\Http\Requests\ToiletRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\RelativeHumiditySensor;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ToiletController extends Controller {
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->program_name =  "toilets";
        $this->middleware(function ($request, $next) {
            $this->route_name = "{$this->program_name}";
            return $next($request);
        });
    }

    public function index($location_id) {
        $fetchLocations = App::make('fetch_locations');
        try {
            $location = $fetchLocations->firstOrFail(function ($location) use ($location_id) {
                return $location->id == $location_id;
            });
        } catch (ItemNotFoundException $e) {
            return abort(404);
        }
        return view('backend.toilets.index', compact('location'));
    }

    /**
     * Get a listing of the resource
     *
     * @param Request $request
     * @param  int  $location_id
     * @return \Illuminate\Http\Response
     */
    public function getResources(Request $request, $location_id) {
        $draw = $request->get('draw');

        $fetchLocations = App::make('fetch_locations');
        try {
            $location = $fetchLocations->firstOrFail(function ($location) use ($location_id) {
                return $location->id == $location_id;
            });
        } catch (ItemNotFoundException $e) {
            return $this->dataTableJsonResponse($draw, 0, 0, [
                "aaData" => []
            ]);
        }

        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');

        $start = $request->get("start");
        $rowperpage = $request->get("length"); // Rows display per page

        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc

        $searchValue = new stdClass();
        $searchValue->code = $request->get('code');
        $searchValue->name = $request->get('name');
        $searchValue->type = $request->get('type');
        $searchValue->location_id = $location->id;

        $totalRecordswithFilter = Toilet::select('count(*) as allcount')
            ->Search($searchValue)
            ->count();

        // Fetch records
        $query = Toilet::select('toilets.id', 'toilets.code', 'toilets.name', 'toilets.type', 'toilets.device_key', 'toilets.created_at')
            ->Search($searchValue)
            ->skip($start)
            ->take($rowperpage)
            ->orderBy($columnName, $columnSortOrder);

        if (!Auth::user()->can('toilets.full')) {
            $query->where('toilets.creator_id', '=', Auth::user()->id);
        }
        $records = $query->get();
        $manageable_sensors = Auth::user()->manageableSensors();
        foreach ($records as $record) {
            $options = [];
            if (count($manageable_sensors) > 0) {
                array_push($options, [
                    'label' => trans('messages.sensor_management'),
                    'url' => route("{$manageable_sensors[0]}_sensors.index", [$record->id]),
                    'action' => 'show'
                ]);
            }
            if (Auth::user()->can('toilets.show')) {
                array_push($options, [
                    'label' => trans('messages.toilet_dashboard'),
                    'url' => route('toilets.show', [$location->id, $record->id]),
                    'action' => 'show'
                ]);
            }
            if (Auth::user()->can("{$this->program_name}.edit")) {
                array_push($options, [
                    'label' => trans('messages.edit'),
                    'url' => route("{$this->program_name}.edit", [$location->id, $record->id]),
                    'action' => 'edit'
                ]);
            }
            if (Auth::user()->can("{$this->program_name}.destroy")) {
                array_push($options, [
                    'label' => trans('messages.delete').trans('messages.toilet'),
                    'url' => route("{$this->program_name}.destroy", [$location->id, $record->id]),
                    'action' => 'delete'
                ]);
            }
            $record->options = $options;
        }

        return $this->dataTableJsonResponse($draw, 0, $totalRecordswithFilter, [
            "aaData" => $records
        ]);
    }

    public function show($location_id, $id) {
        $fetchLocations = App::make('fetch_locations');
        try {
            $location = $fetchLocations->firstOrFail(function ($location) use ($location_id) {
                return $location->id == $location_id;
            });
            $location->image = $location->image ? site_image($location->image) : asset("assets/images/empty_image.png");
        } catch (ItemNotFoundException $e) {
            return abort(404);
        }

        $toilet = Toilet::findOrFail($id);
        $toilet->image = site_image($toilet->image);

        $sensors = [
            'toilet_paper' => ToiletPaperSensor::class,
            'smelly' => SmellySensor::class,
            'human_traffic' => HumanTrafficSensor::class,
            'hand_lotion' => HandLotionSensor::class,
            'temperature' => TemperatureSensor::class,
            'relative_humidity' => RelativeHumiditySensor::class,
        ];

        $sensorCounts = [];

        foreach ($sensors as $sensorName => $sensorClass) {
            $sensors = $sensorClass::where('toilet_id', $toilet->id)->select('id')->get();
            $sensorCounts[$sensorName] = [
                'showUrl' => $sensors->count() > 0 ? route("{$sensorName}_sensors.show", [$toilet->id, $sensors[0]->id]) : null,
                'count' => $sensors->count()
            ];
            if($sensorName == 'relative_humidity') {
                $sensorCounts[$sensorName]['count'] = $sensorCounts['temperature']['count'];
            }
        }

        return view('backend.toilets.show', compact('location', 'toilet', 'sensorCounts'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $location_id
     * @return \Illuminate\Http\Response
     */
    public function store(ToiletRequest $request, $location_id) {
        $fetchLocations = App::make('fetch_locations');
        try {
            $location = $fetchLocations->firstOrFail(function ($location) use ($location_id) {
                return $location->id == $location_id;
            });
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }

        $res = new Toilet;
        $res->location_id = $location->id;
        $res->creator_id = Auth::user()->id;
        $res->type = $request->get('type');
        $res->code = $request->get('code');
        $res->name = $request->get('name');
        $res->alert_token = $request->get('alert_token');
        $res->notification_start = $request->get('notification_start');
        $res->notification_end = $request->get('notification_end');
        if (!empty($request['image'])) {
            $image_path = $this->upload($request['image'], 'toilets');
            if ($image_path) {
                $res->image = $image_path;
            }
        }
        if ($res->save()) {
            $this->event_log($res->toJson());
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => trans('messages.toilet') . $res->name . trans('messages.successfully_added')]);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.toilet') . trans('messages.failure_added'));
    }

    /**
     * get the form for editing the specified resource.
     *
     * @param  int  $location_id
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($location_id, $id) {
        $fetchLocations = App::make('fetch_locations');
        try {
            $location = $fetchLocations->firstOrFail(function ($location) use ($location_id) {
                return $location->id == $location_id;
            });
            $res = Toilet::where('location_id', $location->id)->findOrFail($id);
            $res->image = site_image($res->image);
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }

        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, [
            'resource' => $res,
            'actionUrl' => route("{$this->route_name}.update", [$location->id, $res->id])
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(ToiletRequest $request, $location_id, $id) {
        $fetchLocations = App::make('fetch_locations');
        try {
            $location = $fetchLocations->firstOrFail(function ($location) use ($location_id) {
                return $location->id == $location_id;
            });
            $res = Toilet::where('location_id', $location->id)->findOrFail($id);
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }
        $res->type = $request->get('type');
        $res->code = $request->get('code');
        $res->name = $request->get('name');
        $res->alert_token = $request->get('alert_token');
        $res->notification_start = $request->get('notification_start');
        $res->notification_end = $request->get('notification_end');
        if (!empty($request['image'])) {
            $image_path = $this->upload($request['image'], 'toilets');
            if ($image_path) {
                $res->image = $image_path;
            }
        }
        if ($res->save()) {
            $this->event_log($res->toJson());
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => trans('messages.toilet') . $res->name . trans('messages.successfully_updated')]);
        }

        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.toilet') . $res->name . trans('messages.failure_updated'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($location_id, $id) {
        $fetchLocations = App::make('fetch_locations');
        try {
            $location = $fetchLocations->firstOrFail(function ($location) use ($location_id) {
                return $location->id == $location_id;
            });
            $res = Toilet::where('location_id', $location->id)->findOrFail($id);
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }

        if ($res->delete()) {
            $this->event_log($res->toJson());
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => trans('messages.toilet') . trans('messages.successfully_deleted')]);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.toilet') . trans('messages.failure_deleted'));
    }
}
