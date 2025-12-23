<?php

namespace App\Http\Controllers;

use stdClass;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Utils\ErrorCodeUtils;
use App\Http\Requests\UserRequest;
use App\Events\AccountCreatedEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserController extends Controller {
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->program_name =  "users";
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
        $roleOptions = Role::select('id', 'name', 'level', 'group_id')->get();
        $request_parent_permission = Permission::PermissionByName('others', 'required_parent');
        $roleParentOptions = [];
        foreach ($roleOptions as $key => $role) {
            $role->required_parent = $request_parent_permission ? $role->HasPermission($request_parent_permission->id) : false;
        }
        return view('backend.users.index', compact('roleOptions', 'roleParentOptions'));
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
        $searchValue->username = $request->get('username');
        $searchValue->name = $request->get('name');
        $searchValue->role_id = $request->get('role_id');

        $withTrashed = Auth::user()->can("{$this->program_name}.restore");

        /* $totalRecords = User::select('count(*) as allcount')->Condition($withTrashed)->count();*/
        $totalRecordswithFilter = User::select('count(*) as allcount')
            ->Search($searchValue)
            ->Condition($withTrashed)
            ->count();

        // Fetch records
        $records = User::InnerJoinRole()
            ->Search($searchValue)
            ->orderBy($columnName, $columnSortOrder)
            ->Condition($withTrashed)
            ->skip($start)
            ->take($rowperpage)
            ->select('users.*', 'roles.name as role')
            ->get();

        foreach ($records as $record) {
            $options = [];
            if (!$record->trashed()) {
                if (Auth::user()->can("{$this->program_name}.edit")) {
                    array_push($options, [
                        'label' => trans('messages.edit'),
                        'url' => route("{$this->program_name}.edit", $record->id),
                        'action' => 'edit'
                    ]);
                }
                if (Auth::user()->can("{$this->program_name}.password")) {
                    array_push($options, [
                        'label' => trans('messages.edit_password'),
                        'url' => route("{$this->program_name}.password", $record->id),
                        'action' => 'password'
                    ]);
                }
            }
            if (Auth::user()->can("{$this->program_name}.destroy")) {
                if ($record->trashed()) {
                    if (Auth::user()->can("{$this->program_name}.restore")) {
                        array_push($options, [
                            'label' => trans('messages.restore').trans('messages.user'),
                            'url' => route("{$this->program_name}.restore", $record->id),
                            'action' => 'restore'
                        ]);
                    }
                }
                array_push($options, [
                    'label' =>($record->trashed()?trans('messages.delete'): trans('messages.disabled')).trans('messages.user'),
                    'url' => route("{$this->program_name}.destroy", $record->id),
                    'action' => $record->trashed()?'delete': 'disable',
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
    public function store(UserRequest $request) {
        $res = new User;
        $res->username = $request->get('username');
        $res->password = $request->get('password');
        $res->name = $request->get('name');
        $res->email = $request->get('email');
        $res->phone = $request->get('phone');
        $res->role_id = $request->get('role_id');
        $role = Role::findOrFail($res->role_id);

        $request_parent_permission = Permission::PermissionByName('others', 'required_parent');
        if ($request_parent_permission? $role->HasPermission($request_parent_permission->id) : false) {
            $res->parent_id = $request->get('parent_id');
            try {
                User::AvailableParent($role)->select('users.id', 'users.name')->findOrFail($res->parent_id);
            } catch (ModelNotFoundException $e) {
                return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::UNPROCESSABLE_ENTITY, null, trans('messages.user_parent_error'));
            }
        } else {
            $res->parent_id = null;
        }
        if ($res->save()) {
            $this->event_log($res->toJson());
            event(new AccountCreatedEvent($res, $request->get('password')));
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => trans('messages.user') . $res->name . trans('messages.successfully_added')]);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.user') . trans('messages.failure_added'));
    }

    /**
     * get the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        try {
            $resource = User::findOrFail($id);
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
    public function update(UserRequest $request, $id) {
        try {
            $res = User::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }
        $res->name = $request->get('name');
        $res->email = $request->get('email');
        $res->phone = $request->get('phone');
        $res->role_id = $request->get('role_id');
        try {
            $role = Role::findOrFail($res->role_id);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }

        $request_parent_permission = Permission::PermissionByName('others', 'required_parent');
        if ($request_parent_permission? $role->HasPermission($request_parent_permission->id) : false) {
            $res->parent_id = $request->get('parent_id');
            try {
                User::AvailableParent($role)->select('users.id', 'users.name')->findOrFail($res->parent_id);
            } catch (ModelNotFoundException $e) {
                return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::UNPROCESSABLE_ENTITY, null, trans('messages.user_parent_error'));
            }
        } else {
            $res->parent_id = null;
        }
        if ($res->save()) {
            $this->event_log($res->toJson());
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => trans('messages.user') . $res->name . trans('messages.successfully_updated')]);
        }

        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.user') . $res->name . trans('messages.failure_updated'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        try {
            $res = User::withTrashed()->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }
        $name = $res->name;
        $is_delete = $res->trashed();
        if ($is_delete) {
            // 檢查相關聯資料
            if ($res->locations()->exists() || $res->children()->exists()) {
                return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.user') . $name . "有相關資料");
            }
        }
        if ($is_delete?$res->forceDelete():$res->delete()) {
            $this->event_log($res->toJson());
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => trans('messages.user') . $name . ($is_delete ? trans('messages.successfully_deleted'): trans('messages.successfully_disabled'))]);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.user') . $name . ($is_delete ? trans('messages.failure_deleted'): trans('messages.failure_disabled')));
    }

    /**
     * 恢復使用者
     *
     * @param int $id 使用者id
     * @return void
     */
    public function restore($id) {
        try {
            $res = User::withTrashed()->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }
        if ($res->restore()) {
            $this->event_log($res->toJson());
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => trans('messages.user') . $res->name . trans('messages.successfully_restore')]);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, ['messages' => trans('messages.user') . $res->name . trans('messages.failure_restore')]);
    }

    /**
     * 更新使用者密碼
     *
     * @param Request $request
     * @param int $id 使用者id
     * @return void
     */
    public function password(Request $request, $id) {
        try {
            $res = User::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }
        $input = $request->only('password', 'confirm_password');
        $validator = Validator::make($input, [
            'password' => 'required|string|min:6',
            'confirm_password' => 'required|same:password',
        ], [], [
            'password' => trans('messages.password'),
            'confirm_password' => trans('messages.confirm_password'),
        ]);

        if ($validator->fails()) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::UNPROCESSABLE_ENTITY, null, $validator->errors());
        }
        $res->password = $input['password'];
        if ($res->save()) {
            $this->event_log($res->toJson());
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => trans('messages.user') . $res->name . trans('messages.successfully_updated')]);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, ['messages' => trans('messages.user') . $res->name . trans('messages.failure_updated')]);
    }
    /**
     * 更新個人密碼
     *
     * @param Request $request
     * @return void
     */
    public function selfPassword(Request $request) {
        $user = Auth::user();
        $input = $request->only('old_password', 'new_password', 'confirm_password');
        $validator = Validator::make($input, [
            'old_password' => 'required|max:120',
            'new_password' => 'required|max:120|min:6',
            'confirm_password' => 'required|same:new_password'
        ], [], [
            'old_password' => trans('messages.old_password'),
            'new_password' => trans('messages.password'),
            'confirm_password' => trans('messages.confirm_password'),
        ]);
        if ($validator->fails()) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::UNPROCESSABLE_ENTITY, null, $validator->errors());
        }
        $old_password = $request->input('old_password');
        $new_password = $request->input('new_password');

        if (!Hash::check($old_password, $user->password)) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::UNPROCESSABLE_ENTITY, null, trans('messages.userself_old_password_error'));
        }
        $user->password = $new_password;
        if ($user->save()) {
            $this->event_log($user->toJson());
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => trans('messages.user') . $user->name . trans('messages.successfully_updated')]);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, ['messages' => trans('messages.user') . $user->name . trans('messages.failure_updated')]);
    }

    public function getAvailableParent($id) {
        try {
            $role = Role::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }
        $roleParentOptions = [];
        if ($role->required_parent) {
            $roleParentOptions[$role->id] = User::AvailableParent($role)->select('users.id', 'users.name')->get();
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, User::AvailableParent($role)->select('users.id', 'users.name')->get());
    }
}
