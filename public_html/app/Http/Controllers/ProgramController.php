<?php

namespace App\Http\Controllers;

use stdClass;
use App\Models\Action;
use App\Models\Program;
use Illuminate\Http\Request;
use App\Utils\ErrorCodeUtils;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\ProgramRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProgramController extends Controller {
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->program_name =  "programs";
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
        $actionOptions = Action::get();
        return view('backend.programs.index', compact('actionOptions'));
    }

    public function getResources(Request $request) {
        $draw = $request->get('draw');

        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');

        $columnIndex = $columnIndex_arr[0]['column']; // Column index
        $columnName = $columnName_arr[$columnIndex]['data']; // Column name
        $columnSortOrder = $order_arr[0]['dir']; // asc or desc

        $searchValue = new stdClass();
        $searchValue->name = $request->get('name');
        $searchValue->display_name = $request->get('display_name');

        $start = $request->get("start");
        $rowperpage = $request->get("length"); // Rows display per page

        $totalRecordswithFilter = Program::select('count(*) as allcount')
            ->Search($searchValue)
            ->count();

        // Fetch records
        $records = Program::Search($searchValue)
            ->skip($start)
            ->take($rowperpage)
            ->orderBy($columnName, $columnSortOrder)
            ->get();

        foreach ($records as $record) {
            $options = [];

            if(Auth::user()->can("{$this->program_name}.edit")) {
                array_push($options, [
                    'label' => trans('messages.edit'),
                    'url' => route("{$this->program_name}.edit", $record->id),
                    'action' => 'edit'
                ]);
            }
            if(Auth::user()->can("{$this->program_name}.destroy")) {
                array_push($options, [
                    'label' => trans('messages.delete'),
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
    public function store(ProgramRequest $request) {
        $res = new Program;
        $res->name = $request->get('name');
        $res->display_name = $request->get('display_name');

        if($res->save()) {
            $actions = $request->get('actions');
            if($actions != null) {
                $actions = array_map('intval', $actions);
                if(is_array($actions)) {
                    $res->actions()->sync($actions);
                }
            } else {
                $res->actions()->detach();
            }

            $this->event_log($res->toJson());
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => trans('messages.program') . $res->display_name . trans('messages.successfully_added')]);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.program') . trans('messages.failure_added'));
    }

    /**
     * get the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        try {
            $resource = Program::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }
        $resource->actions = $resource->actions->pluck('id');

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
    public function update(ProgramRequest $request, $id) {
        try {
            $res = Program::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }

        $res->name = $request->get('name');
        $res->display_name = $request->get('display_name');

        if($res->save()) {
            $actions = $request->get('actions');
            if($actions != null) {
                $actions = array_map('intval', $actions);
                if(is_array($actions)) {
                    $res->actions()->sync($actions);
                }
            } else {
                $res->actions()->detach();
            }

            $this->event_log($res->toJson());
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => trans('messages.program') . $res->display_name . trans('messages.successfully_updated')]);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.program') . $res->display_name . trans('messages.failure_updated'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id) {
        try {
            $res = Program::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }
        $display_name = $res->display_name;

        if($res->delete()) {
            $this->event_log($res->toJson());
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => trans('messages.program') . $display_name . trans('messages.successfully_deleted')]);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.program') . $display_name . trans('messages.failure_deleted'));
    }
}
