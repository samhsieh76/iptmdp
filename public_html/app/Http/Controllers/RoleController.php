<?php

namespace App\Http\Controllers;

use stdClass;
use App\Models\Role;
use App\Models\Program;
use App\Models\RoleGroup;
use Illuminate\Http\Request;
use App\Utils\ErrorCodeUtils;
use App\Http\Requests\RoleRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RoleController extends Controller {
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->program_name =  "roles";
        $this->middleware(function ($request, $next) {
            $this->route_name = "{$this->program_name}";
            return $next($request);
        });
    }

    public function index() {
        $roleGroupOptions = RoleGroup::select('id', 'name')->get();
        return view('backend.roles.index', compact('roleGroupOptions'));
    }


    /**
     * Get a listing of the resource
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
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
        
        $totalRecordswithFilter = Role::select('count(*) as allcount')
            ->Search($searchValue)
            ->count();

        // Fetch records
        $records = Role::select('roles.id', 'roles.name', 'roles.level', 'role_groups.name as role_group')
            ->Search($searchValue)
            ->leftJoin('role_groups', 'role_groups.id', 'roles.group_id')
            ->orderBy($columnName, $columnSortOrder)
            ->skip($start)
            ->take($rowperpage)
            ->get();

        foreach ($records as $record) {
            $options = [];
            if (Auth::user()->can("{$this->program_name}.edit")) {
                array_push($options, [
                    'label' => trans('messages.edit'),
                    'url' => route("{$this->program_name}.edit", $record->id),
                    'action' => 'edit'
                ]);
            }
            if (Auth::user()->can("{$this->program_name}.destroy")) {
                array_push($options, [
                    'label' => trans('messages.delete'),
                    'url' => route("{$this->program_name}.destroy", $record->id),
                    'action' => 'delete'
                ]);
            }
            if (Auth::user()->can("{$this->program_name}.permission")) {
                array_push($options, [
                    'label' => trans('messages.permission'),
                    'url' => route("{$this->program_name}.permission.index", $record->id),
                    'action' => 'permission'
                ]);
            }
            $record->options = $options;
        }

        return $this->dataTableJsonResponse($draw, $totalRecordswithFilter, 0, [
            "aaData" => $records
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(RoleRequest $request) {
        $res = new Role;
        $res->name = $request->get('name');
        $res->group_id = $request->get('group_id');
        $res->level = $request->get('level');
        if ($res->save()) {
            $this->event_log($res->toJson());
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => trans('messages.role').$res->name.trans('messages.successfully_added')]);
        }

        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.role') . trans('messages.failure_added'));
    }

    /**
     * get the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        try {
            $resource = Role::findOrFail($id);
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
    public function update(RoleRequest $request, $id) {
        try {
            $res = Role::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }
        $res->name = $request->get('name');
        $res->group_id = $request->get('group_id');
        $res->level = $request->get('level');
        if ($res->save()) {
            $this->event_log($res->toJson());
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => trans('messages.role').$res->name.trans('messages.successfully_updated')]);
        }

        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.role').$res->name.trans('messages.failure_updated'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $res = Role::findOrFail($id);
        if ($res->delete()) {
            $this->event_log($res->toJson());
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => trans('messages.role') . trans('messages.successfully_deleted')]);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.role') . trans('messages.failure_deleted'));
    }

    /**
     * Display the specified resource permission.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function permission($id) {
        $role = Role::findOrFail($id);
        $programs = Program::get();
        $programs->each(function ($item, $key) {
            $item->actions = $item->actions->map(function ($action) {
                $action->permission_id = $action->pivot->id;
                return $action;
            });
        });
        $role_permissions = $role->permissions->pluck('id')->toArray();
        return view('backend.roles.permission', compact('role', 'programs', 'role_permissions'));
    }

    public function update_permission(Request $request, $id) {
        $res = Role::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'permissions' => 'nullable|array'
        ], [], [
            'permissions' => trans('messages.permission')
        ]);

        if ($validator->fails()) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::UNPROCESSABLE_ENTITY, null, $validator->errors());
        }
        $permissions = $request->get('permissions');
        if ($permissions != null) {
            $permissions = array_map('intval', $permissions);
            if (is_array($permissions)) {
                $res->permissions()->sync($permissions);
            }
        } else {
            $res->permissions()->detach();
        }
        $this->event_log(json_encode(['role_id' => $res->id, 'name' => $res->name, 'permissions' => $permissions]));
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => trans('messages.permission') . trans('messages.successfully_updated')]);
    }
}
