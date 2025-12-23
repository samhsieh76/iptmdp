<?php

namespace App\Http\Controllers;

use stdClass;
use App\Models\User;
use App\Models\County;
use App\Models\Location;
use Illuminate\Http\Request;
use App\Utils\ErrorCodeUtils;
use App\Models\LocationSupplier;
use App\Services\LocationService;
use App\Mail\RequestLocationEmail;
use App\Models\LocationAuditRecord;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\LocationRequest;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class LocationController extends Controller {
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->program_name =  "locations";
        $this->middleware(function ($request, $next) {
            $this->route_name = "{$this->program_name}";
            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
     */
    public function index() {
        $countyOptions = County::pluck('name', 'id');
        $userOptions = User::where('users.id', '!=', 1)->join('roles', function ($join) {
            $join->on('roles.id', '=', 'users.role_id')
                ->where('roles.group_id', '=', 3);
        })->select('users.id', 'users.name')->get();
        return view('backend.locations.index', compact('countyOptions', 'userOptions'));
    }

    public function getResources(Request $request) {
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length"); // Rows display per page

        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');

        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc

        $searchValue = new stdClass();
        $searchValue->name = $request->get('name');
        $searchValue->county_id = $request->get('county_id');
        $searchValue->address = $request->get('address');

        $totalRecordswithFilter = Location::select('count(*) as allcount')
            ->Search($searchValue)
            ->count();

        // Fetch records
        $records = Location::Search($searchValue)
            ->InnerJoinCounty()
            ->orderBy($columnName, $columnSortOrder)
            ->select('locations.*', 'counties.name as county');

        $guardName = Auth::getDefaultDriver(); // 獲取guard
        $esmsConfig = config('esms');
        if ($guardName == $esmsConfig['guard']) {
            $user = Auth::guard('esms')->user();
            if ($user->auth_level != $esmsConfig['auth_levels']['all_areas']) {
                $records->where('county_id', '=', $user->county_id);
            }
        } else {
            //  如果是供應商
            if (Auth::user()->can('locations.request_permission') && !Auth::user()->isSuperUser()) {
                $records->join('location_suppliers', function ($join) {
                    $join->on('locations.id', '=', 'location_suppliers.location_id')->where('location_suppliers.status', '=', LocationSupplier::STATUS_PERMISSION);
                    $join->where('location_suppliers.supplier_id', '=', Auth::user()->id);
                });
            } else if (!Auth::user()->can('locations.full')) { //  不是SuperUser要找下級所有的場域
                $user_parents = User::where('parent_id', '=', Auth::user()->id)->pluck('id')->toArray();
                $administrations = array_merge([Auth::user()->id], $user_parents);
                if (count($user_parents) != 0) {
                    $users = User::whereIn('parent_id', $user_parents)->pluck('id')->toArray();
                    $administrations = array_merge($administrations, $users);
                }
                $records->whereIn('locations.administration_id', $administrations);
            }
        }
        $records = $records
            ->skip($start)
            ->take($rowperpage)
            ->get();

        foreach ($records as $record) {
            $options = [];

            if (Auth::user()->can('location_sensors.index')) {
                array_push($options, [
                    'label' => trans('messages.view_data'),
                    'url' => route('location_sensors.index', $record->id),
                    'action' => 'data'
                ]);
            }
            if (Auth::user()->can('toilets.index')) {
                array_push($options, [
                    'label' => trans('messages.toilet_list'),
                    'url' => route('toilets.index', $record->id),
                    'action' => 'show'
                ]);
            }
            if (Auth::user()->can("{$this->program_name}.edit")) {
                array_push($options, [
                    'label' => trans('messages.edit'),
                    'url' => route("{$this->program_name}.edit", $record->id),
                    'action' => 'edit'
                ]);
            }
            if (Auth::user()->can("{$this->program_name}.destroy")) {
                array_push($options, [
                    'label' => trans('messages.delete') . trans('messages.location'),
                    'url' => route("{$this->program_name}.destroy", $record->id),
                    'action' => 'delete'
                ]);
            }
            $record->options = $options;
        }
        return $this->dataTableJsonResponse($draw, 0, $totalRecordswithFilter, [
            "aaData" => $records
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(LocationRequest $request) {
        $res = new Location;
        $res->county_id = $request->get('county_id');

        //  檢查該管轄者是否已有管轄區域
        $administration_id = $request->get('administration_id');
        $check_location = Location::where('administration_id', '=', $administration_id)->first();
        if ($check_location != null) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.location_administration_conflict'));
        }
        $res->administration_id = $administration_id;

        $res->name = $request->get('name');
        $res->address = $request->get('address');
        $res->longitude = $request->get('longitude');
        $res->latitude = $request->get('latitude');
        $res->business_hours = $request->get('business_hours');
        if (!empty($request['image'])) {
            $image_path = $this->upload($request['image'], 'locations');
            if ($image_path) {
                $res->image = $image_path;
            }
        }

        if ($res->generateAuthCode()) {
            $this->event_log($res->toJson());
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => trans('messages.location') . $res->name . trans('messages.successfully_added')]);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.location') . trans('messages.failure_added'));
    }

    /**
     * get the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        try {
            $resource = Location::findOrFail($id);
            $resource->image = site_image($resource->image);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, [
            'resource' => $resource,
            'actionUrl' => route("{$this->route_name}.update", $resource->id)
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(LocationService $locationService, LocationRequest $request, $id) {
        try {
            $locationService->validateUserLocationAccess($id);
            $res = Location::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }

        $res->county_id = $request->get('county_id');

        //  檢查該管轄者是否已有管轄區域
        $administration_id = $request->get('administration_id');
        if ($administration_id != $res->administration_id) { //  先判斷是否有更改管轄者
            $check_location = Location::where('administration_id', '=', $administration_id)->first();
            if ($check_location != null) {
                return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.location_administration_conflict'));
            }
            $res->administration_id = $administration_id;
        }

        $res->name = $request->get('name');
        $res->address = $request->get('address');
        $res->longitude = $request->get('longitude');
        $res->latitude = $request->get('latitude');
        $res->business_hours = $request->get('business_hours');
        if (!empty($request['image'])) {
            $image_path = $this->upload($request['image'], 'locations');
            if ($image_path) {
                $res->image = $image_path;
            }
        }

        if ($res->save()) {
            $this->event_log($res->toJson());
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => trans('messages.location') . $res->name . trans('messages.successfully_updated')]);
        }

        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.location') . $res->name . trans('messages.failure_updated'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(LocationService $locationService, $id) {
        try {
            $res = Location::findOrFail($id);
            $locationService->validateUserLocationAccess($res->id);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }
        $name = $res->name;

        if ($res->delete()) {
            $this->event_log($res->toJson());
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => trans('messages.location') . $name . trans('messages.successfully_deleted')]);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.location') . $name . trans('messages.failure_deleted'));
    }

    public function requestPermission(Request $request) {
        $auth_code = $request->get('request_location');
        try {
            $res = Location::where('auth_code', '=', $auth_code)->firstOrFail();
            $administrator = $res->administrator;
            if ($administrator == null) {
                throw new ModelNotFoundException("Administrator Not Found");
            }
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.location_auth_code_error'));
        }

        $user_id = Auth::user()->id;
        $location_id = $res->id;
        $record = LocationAuditRecord::where('location_id', '=', $location_id)
            ->where('supplier_id', '=', $user_id)
            ->where('status', '!=', LocationAuditRecord::STATUS_REJECT)
            ->orderBy('created_at', 'desc')
            ->first();

        // 上次拒絕 上次同意但授權被刪除要新增請求授權紀錄
        $add_new = true;
        if ($record != null && $record->status == LocationAuditRecord::STATUS_ACCEPT) {
            $location_supplier = LocationSupplier::where('location_id', '=', $location_id)->where('supplier_id', '=', $user_id)->first();
            // 上次同意且授權還在 不用新增請求授權紀錄 直接返回已授權
            if ($location_supplier != null) {
                $add_new = false;
                return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => trans('messages.already_has_permission', ['name' => $res->name])]);
            }
        } else if ($record != null && $record->status == LocationAuditRecord::STATUS_WAITING) {
            // 上次還未審核 不用新增請求授權紀錄
            $add_new = false;
        }
        if ($add_new) {
            $record = new LocationAuditRecord;
            $record->location_id = $res->id;
            $record->supplier_id = $user_id;
            if (!$record->generateToken()) {
                return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.failure_request', ['name' => $res->name]));
            }
            $this->event_log($record->toJson());
        }

        if ($this->sendRequestLocationEmailTo($administrator, $res, $record)) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => trans('messages.successfully_request', ['name' => $res->name])]);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.failure_request', ['name' => $res->name]));
    }

    /**
     * 發送請求授權Email
     *
     * @param \App\Models\User $administrator
     * @param \App\Models\Location $location
     * @param \App\Models\LocationAuditRecord $record
     * @return void
     */
    private function sendRequestLocationEmailTo($administrator, $location, $record) {
        try {
            Mail::to($administrator->email)->send(new RequestLocationEmail($administrator, $location, $record));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function accept(LocationService $locationService, Request $request) {
        $token = $request->get('token');
        $accept = $request->get('accept');
        $auditor_id = $request->get('auditor_id');
        try {
            $record = LocationAuditRecord::where('token', '=', $token)->firstOrFail();
            $locationService->validateUserLocationAccess($record->location_id);
            $user = User::find($record->supplier_id);
            $location = Location::findOrFail($record->location_id);
        } catch (ModelNotFoundException $e) {
            return view('emails.request_location_failure');
        } catch (ItemNotFoundException $e) {
            return view('emails.request_location_failure');
        }

        if ($record->status != LocationAuditRecord::STATUS_WAITING) {
            return view('emails.request_location_result', compact('record', 'location', 'user'));
        }

        if ($accept) {   //  accept
            $record->status = LocationAuditRecord::STATUS_ACCEPT;
            //  同意才新增一筆場域供應商
            $location_supplier = LocationSupplier::where('location_id', '=', $record->location_id)->where('supplier_id', '=', $record->supplier_id)->first();
            if ($location_supplier == null) {
                $location_supplier = new LocationSupplier;
                $location_supplier->location_id = $record->location_id;
                $location_supplier->supplier_id = $record->supplier_id;
            }
            $location_supplier->status = LocationSupplier::STATUS_PERMISSION;
            if (!$location_supplier->save()) {
                return view('emails.request_location_failure');
            }
        } else {    //  reject
            $record->status = LocationAuditRecord::STATUS_REJECT;
        }
        $record->auditor_id = $auditor_id;

        if (!$record->save()) {
            return view('emails.request_location_failure');
        }
        return view('emails.request_location_result', compact('record', 'location', 'user'));
    }
}
