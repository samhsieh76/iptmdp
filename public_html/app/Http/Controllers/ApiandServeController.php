<?php

namespace App\Http\Controllers;

use DateTime;
use stdClass;
use Illuminate\Http\Request;
use App\Utils\ErrorCodeUtils;
use App\Models\LocationSupplier;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\LocationSupplierRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApiandServeController extends Controller {
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->program_name =  "api_and_serves";
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
        return view('backend.api_and_serves.index');
    }

    public function getResources(Request $request) {
        $fetchLocations = App::make('fetch_locations');
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowperpage = $request->get("length"); // Rows display per page

        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');

        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc
        switch ($columnName) {
            case "location.name":
                $columnName = "locations.name";
                break;
            case "supplier.name":
                $columnName = "users.name";
                break;
            default:
                break;
        }

        $searchValue = new stdClass();
        $searchValue->location = $request->get('location');
        $searchValue->status = $request->get('status');

        // $totalRecords = LocationSupplier::select('count(*) as allcount')->count();
        $totalRecordswithFilter = LocationSupplier::select('count(*) as allcount')
            ->InnerJoinLocation()
            ->Search($searchValue)
            ->count();

        // Fetch records
        $records = LocationSupplier::Search($searchValue)
            ->InnerJoinLocation()
            ->InnerJoinUser()
            ->orderBy($columnName, $columnSortOrder)
            ->select('location_suppliers.*');
        //  如果是供應商
        if (Auth::user()->can('locations.request_permission') && !Auth::user()->isSuperUser()) {
            $records->where('location_suppliers.supplier_id', '=', Auth::user()->id);
        } else {
            $records->whereIn('locations.id', $fetchLocations->pluck('id')->values());
        }
        $records = $records
            ->skip($start)
            ->take($rowperpage)
            ->get();

        foreach ($records as $record) {
            $options = [];
            $record->created_at = $record->created_at ? (new DateTime($record->created_at))->format('Y/m/d') : null;
            $record->location;
            $record->supplier;

            if (Auth::user()->can("{$this->program_name}.edit")) {
                $record->edit_url = route("{$this->program_name}.update", $record->id);
            }

            if (Auth::user()->can("{$this->program_name}.destroy")) {
                array_push($options, [
                    'label' => trans('messages.delete').trans('messages.authorized'),
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updatePermission(LocationSupplierRequest $request) {
        $id = $request->get('id');
        try {
            $res = LocationSupplier::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }

        $res->status = $request->get('checked');
        $location = $res->location->name;

        if ($res->save()) {
            $this->event_log($res->toJson());
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => $location . trans('messages.location_supplier') . trans('messages.successfully_updated')]);
        }

        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, $location . trans('messages.location_supplier') . trans('messages.failure_updated'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        try {
            $res = LocationSupplier::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }
        $location = $res->location->name;

        if ($res->delete()) {
            $this->event_log($res->toJson());
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => $location . trans('messages.location_supplier') . trans('messages.successfully_deleted')]);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, $location . trans('messages.location_supplier') . trans('messages.failure_deleted'));
    }

    public function download() {

        $filePath = public_path('storage/files/toilet_api_doc.pdf');
        $encodedFilename = rawurlencode('toilet_api_doc');

        if (file_exists($filePath)) {
            $headers = [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename*=UTF-8\'\'' . $encodedFilename . '.pdf'
            ];
            return response()->file($filePath, $headers);
        } else {
            return response()->json(['message' => 'File not found.'], 404);
        }
    }
}
