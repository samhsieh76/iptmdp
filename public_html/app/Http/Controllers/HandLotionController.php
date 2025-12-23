<?php

namespace App\Http\Controllers;

use stdClass;
use Exception;
use App\Models\Toilet;
use Illuminate\Http\Request;
use App\Models\HandLotionLog;
use App\Utils\ErrorCodeUtils;
use App\Models\HandLotionSensor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\HandLotionRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ItemNotFoundException;
use App\Jobs\ProcessHandLotionLog;
use App\Models\HandLotionDailyReport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class HandLotionController extends Controller {
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->program_name =  "hand_lotion_sensors";
        $this->middleware(function ($request, $next) {
            $this->route_name = "{$this->program_name}";
            return $next($request);
        });
    }

    public function index() {
        return view('backend.sensors.hand_lotion.index');
    }

    public function getResources(Request $request, $toilet_id) {
        $draw = $request->get('draw');

        $fetchLocations = App::make('fetch_locations');
        try {
            $toilet = Toilet::findOrFail($toilet_id);
            $fetchLocations->firstOrFail(function ($location) use ($toilet) {
                return $location->id == $toilet->location_id;
            });
        } catch (Exception $e) {
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

        $date = date('Y-m-d');

        $searchValue = new stdClass();
        $searchValue->toilet_id = $toilet->id;

        $totalRecordswithFilter = HandLotionSensor::select('count(*) as allcount')
            ->Search($searchValue)
            ->count();

        // Fetch records
        $records = HandLotionSensor::select('hand_lotion_sensors.*', 'latest_report.date as report_date', 'latest_report.notification_times')
            ->LatestReport()
            ->Search($searchValue)
            ->skip($start)
            ->take($rowperpage)
            ->orderBy($columnName, $columnSortOrder)->get();

        foreach ($records as $record) {
            if ($record->report_date != $date) {
                $record->notification_times = 0;
            }
            $options = [];
            if (Auth::user()->can("{$this->program_name}.show")) {
                array_push($options, [
                    'label' => trans('messages.history_data'),
                    'url' => route("{$this->program_name}.show", [$toilet->id, $record->id]),
                    'action' => 'show'
                ]);
            }
            if (Auth::user()->can("{$this->program_name}.edit")) {
                array_push($options, [
                    'label' => trans('messages.edit'),
                    'url' => route("{$this->program_name}.edit", [$toilet->id, $record->id]),
                    'action' => 'edit'
                ]);
            }
            if (Auth::user()->can("{$this->program_name}.destroy")) {
                array_push($options, [
                    'label' => trans('messages.delete').trans('messages.sensor'),
                    'url' => route("{$this->program_name}.destroy", [$toilet->id, $record->id]),
                    'action' => 'delete'
                ]);
            }
            $record->options = $options;
        }

        return $this->dataTableJsonResponse($draw, 0, $totalRecordswithFilter, [
            "aaData" => $records
        ]);
    }

    public function show($toilet_id, $id) {
        $fetchLocations = App::make('fetch_locations');
        try {
            $toilet = Toilet::findOrFail($toilet_id);
            $fetchLocations->firstOrFail(function ($location) use ($toilet) {
                return $location->id == $toilet->location_id;
            });
            $sensor = HandLotionSensor::where('toilet_id', $toilet->id)->findOrFail($id);
        } catch (ItemNotFoundException $e) {
            return abort(404);
        } catch (ModelNotFoundException $e) {
            return abort(404);
        }

        return view('backend.sensors.hand_lotion.show', compact('sensor', 'toilet', 'fetchLocations'));
    }

    public function store(HandLotionRequest $request, $toilet_id) {
        $fetchLocations = App::make('fetch_locations');
        try {
            $toilet = Toilet::findOrFail($toilet_id);
            $fetchLocations->firstOrFail(function ($location) use ($toilet) {
                return $location->id == $toilet->location_id;
            });
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }

        $res = new HandLotionSensor;
        $res->toilet_id = $toilet->id;
        $res->name = $request->get('name');
        $res->is_notification = $request->get('is_notification') == 1;

        if ($res->save()) {
            $this->event_log($res->toJson());
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => trans('messages.hand_lotion_sensor') . $res->name . trans('messages.successfully_added')]);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.hand_lotion_sensor') . trans('messages.failure_added'));
    }

    public function edit($toilet_id, $id) {
        $fetchLocations = App::make('fetch_locations');
        try {
            $toilet = Toilet::findOrFail($toilet_id);
            $fetchLocations->firstOrFail(function ($location) use ($toilet) {
                return $location->id == $toilet->location_id;
            });
            $res = HandLotionSensor::where('toilet_id', $toilet->id)->findOrFail($id);
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }

        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, [
            'resource' => $res,
            'actionUrl' => route("{$this->route_name}.update", [$toilet->id, $res->id])
        ]);
    }

    public function update(HandLotionRequest $request, $toilet_id, $id) {
        $fetchLocations = App::make('fetch_locations');
        try {
            $toilet = Toilet::findOrFail($toilet_id);
            $fetchLocations->firstOrFail(function ($location) use ($toilet) {
                return $location->id == $toilet->location_id;
            });
            $res = HandLotionSensor::where('toilet_id', $toilet->id)->findOrFail($id);
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }

        $res->name = $request->get('name');
        $res->is_notification = $request->get('is_notification') == 1;

        if ($res->save()) {
            $this->event_log($res->toJson());
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => trans('messages.hand_lotion_sensor') . $res->name . trans('messages.successfully_updated')]);
        }

        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.hand_lotion_sensor') . $res->name . trans('messages.failure_updated'));
    }

    public function destroy($toilet_id, $id) {
        $fetchLocations = App::make('fetch_locations');
        try {
            $toilet = Toilet::findOrFail($toilet_id);
            $fetchLocations->firstOrFail(function ($location) use ($toilet) {
                return $location->id == $toilet->location_id;
            });
            $res = HandLotionSensor::where('toilet_id', $toilet->id)->findOrFail($id);
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }

        if ($res->delete()) {
            $this->event_log($res->toJson());
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => trans('messages.hand_lotion_sensor') . trans('messages.successfully_deleted')]);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.hand_lotion_sensor') . trans('messages.failure_deleted'));
    }

    public function toggleNotification(Request $request, $toilet_id, $id) {
        $validator = Validator::make($request->all(), [
            'is_notification' => 'required|boolean'
        ], [], [
            'is_notification' => trans('messages.is_notification')
        ]);
        if ($validator->fails()) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, $validator->errors()->first());
        }
        $fetchLocations = App::make('fetch_locations');
        try {
            $toilet = Toilet::findOrFail($toilet_id);
            $fetchLocations->firstOrFail(function ($location) use ($toilet) {
                return $location->id == $toilet->location_id;
            });
            $res = HandLotionSensor::where('toilet_id', $toilet->id)->findOrFail($id);
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }

        $res->is_notification = $request->get('is_notification');
        if ($res->save()) {
            $this->event_log($res->toJson());
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => trans('messages.hand_lotion_sensor') . $res->name . trans('messages.successfully_updated')]);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.hand_lotion_sensor') . trans('messages.failure_deleted'));
    }

    public function receiveSensorData(Request $request) {
        $validated = $request->only([
            'data',
            'timestamp',
            'signature',
        ]);

        $response     = ['message' => 'Success!'];
        $responseCode = 200;

        $date = Carbon::now(config('app.timezone'));

        DB::beginTransaction();
        try {
            // foreach data to insert
            foreach ($validated['data'] as $data) {
                $log = HandLotionLog::create([
                    'hand_lotion_sensor_id' => $data['id'],
                    'raw_data'              => $data['data'],
                ]);

                ProcessHandLotionLog
                    ::dispatch($log)
                    ->onQueue('hand_lotion')
                    ->afterCommit();

                // modify report.is_active if necessary
                $report = HandLotionDailyReport
                    ::firstOrNew([
                        'date'                  => $date->format('Y-m-d'),
                        'hand_lotion_sensor_id' => $log->hand_lotion_sensor_id,
                    ]);
                $report->is_active = 1;
                $report->save();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            $responseCode = 500;
            $response     = [
                'message' => 'Oops!',
                'errors'  => $e->getMessage(),
            ];
        }

        return response()->json($response, $responseCode);
    }
}