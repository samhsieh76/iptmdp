<?php

namespace Database\Seeders;

use App\Models\Action;
use App\Models\Program;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder {
    private $programs = [
        ['name' => 'programs', 'display_name' => '程式代碼', 'actions' => ['index', 'create', 'edit', 'destroy']],
        ['name' => 'actions', 'display_name' => '行為', 'actions' => ['index', 'create', 'edit', 'destroy']],
        ['name' => 'roles', 'display_name' => '角色', 'actions' => ['index', 'create', 'edit', 'destroy', 'permission']],
        ['name' => 'users', 'display_name' => '帳號管理', 'actions' => ['index', 'create', 'edit', 'destroy', 'role', 'password', 'restore']],
        ['name' => 'locations', 'display_name' => '場域', 'actions' => ['index', 'create', 'edit', 'destroy', 'full', 'request_permission', 'accept']],
        ['name' => 'toilets', 'display_name' => '廁所', 'actions' => ['index', 'create', 'edit', 'destroy', 'full', 'show']],
        ['name' => 'toilet_paper_sensors', 'display_name' => '廁紙感測器', 'actions' => ['index', 'create', 'edit', 'show', 'destroy', 'toggle_notification', 'send_notification']],
        ['name' => 'smelly_sensors', 'display_name' => '氣味感測器', 'actions' => ['index', 'create', 'edit', 'show', 'destroy', 'toggle_notification', 'send_notification']],
        ['name' => 'human_traffic_sensors', 'display_name' => '人流感測器', 'actions' => ['index', 'create', 'edit', 'show', 'destroy', 'toggle_notification']],
        ['name' => 'hand_lotion_sensors', 'display_name' => '洗手液感測器', 'actions' => ['index', 'create', 'edit', 'show', 'destroy', 'toggle_notification', 'send_notification']],
        ['name' => 'relative_humidity_sensors', 'display_name' => '濕度感測器', 'actions' => ['index', 'create', 'edit', 'show', 'destroy']],
        ['name' => 'temperature_sensors', 'display_name' => '溫度感測器', 'actions' => ['index', 'create', 'edit', 'show', 'destroy']],
        ['name' => 'toilet_paper_logs', 'display_name' => '廁紙感測器', 'actions' => ['index', 'create', 'edit', 'show', 'destroy', 'download']],
        ['name' => 'smelly_logs', 'display_name' => '氣味感測數據', 'actions' => ['index', 'create', 'edit', 'show', 'destroy', 'download']],
        ['name' => 'human_traffic_logs', 'display_name' => '人流感測數據', 'actions' => ['index', 'create', 'edit', 'show', 'destroy', 'download']],
        ['name' => 'hand_lotion_logs', 'display_name' => '洗手液感測數據', 'actions' => ['index', 'create', 'edit', 'show', 'destroy', 'download']],
        ['name' => 'relative_humidity_logs', 'display_name' => '濕度感測數據', 'actions' => ['index', 'create', 'edit', 'show', 'destroy', 'download']],
        ['name' => 'temperature_logs', 'display_name' => '溫度感測數據', 'actions' => ['index', 'create', 'edit', 'show', 'destroy', 'download']],
        ['name' => 'api_and_serves', 'display_name' => 'API與服務', 'actions' => ['index', 'edit', 'destroy']],
        ['name' => 'location_audit_records', 'display_name' => '場域請求授權紀錄', 'actions' => ['index']],
        ['name' => 'location_sensors', 'display_name' => '場域感測器總覽', 'actions' => ['index']],
        ['name' => 'others', 'display_name' => '其他', 'actions' => ['required_parent']],
    ];

    private $actions = [
        ['name' => 'index', 'display_name' => '瀏覽'],
        ['name' => 'create', 'display_name' => '新增'],
        ['name' => 'edit', 'display_name' => '編輯'],
        ['name' => 'destroy', 'display_name' => '刪除'],
        ['name' => 'restore', 'display_name' => '恢復'],
        ['name' => 'show', 'display_name' => '詳細資料'],
        ['name' => 'full', 'display_name' => '查看所有'],
        ['name' => 'permission', 'display_name' => '權限'],
        ['name' => 'password', 'display_name' => '編輯密碼'],
        ['name' => 'role', 'display_name' => '修改身份'],
        ['name' => 'order', 'display_name' => '排序'],
        ['name' => 'download', 'display_name' => '下載'],
        ['name' => 'required_parent', 'display_name' => '需要上級'],
        ['name' => 'request_permission', 'display_name' => '請求授權'],
        ['name' => 'accept', 'display_name' => '授權'],
        ['name' => 'toggle_notification', 'display_name' => '開關通報'],
        // ['name' => 'send_notification', 'display_name' => '手動發送通知'],
    ];
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        Action::insert(array_map(function ($action) {
            return $action + [
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now()
            ];
        }, $this->actions));

        $actions = Action::get();
        foreach ($this->programs as $element) {
            $program = Program::create(['display_name' => $element['display_name'], 'name' => $element['name']]);
            $program_actions = $actions->whereIn('name', $element['actions'])->pluck('id')->toArray();
            $program->actions()->sync(array_map('intval', $program_actions));
        }
    }
}
