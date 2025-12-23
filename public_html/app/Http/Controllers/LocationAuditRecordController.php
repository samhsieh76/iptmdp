<?php

namespace App\Http\Controllers;

use stdClass;
use Illuminate\Http\Request;
use App\Utils\ErrorCodeUtils;
use App\Models\LocationAuditRecord;
use Illuminate\Support\Facades\Auth;

class LocationAuditRecordController extends Controller {
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->program_name =  "location_audit_records";
        $this->middleware(function ($request, $next) {
            $this->route_name = "{$this->program_name}";
            return $next($request);
        });
    }

    public function getResources(Request $request) {
        $draw = $request->get('draw');

        /* $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');

        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc */

        $searchValue = new stdClass();
        //  如果是供應商
        if(Auth::user()->can('locations.request_permission') /* && !Auth::user()->isSuperUser() */) {
            $searchValue->supplier_id = Auth::user()->id;
        }


        /* $totalRecords = Location::select('count(*) as allcount')->count();
        $totalRecordswithFilter = Location::select('count(*) as allcount')
            // ->Search($searchValue)
            ->count(); */

        // Fetch records
        $records = LocationAuditRecord::Search($searchValue)
            ->orderBy('created_at', 'desc')
            ->select('status', 'created_at', 'location_id')
            ->get();

        foreach ($records as $record) {
            $record->location_name = $record->location->name;
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, $records);
    }
}
