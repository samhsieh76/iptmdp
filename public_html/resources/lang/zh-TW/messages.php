<?php

use App\Models\Toilet;

return [
    "edit" => "編輯資料",
    "delete" => "刪除",
    "restore" => "恢復",
    "permission" => "權限",
    "show" => "查看",
    "disabled" => "停用",
    "user_management" => "帳號管理平台",
    "backend" => "資訊管理後台",
    "frontend" => "進入戰情室",
    "api_and_serve" => "API與服務",
    "successfully_added" => "已成功新增",
    "failure_added" => "新增失敗",
    "successfully_updated" => "已成功更新",
    "failure_updated" => "更新失敗",
    "successfully_deleted" => "已成功刪除",
    "successfully_disabled" => "已成功停用",
    "failure_deleted" => "刪除失敗",
    "failure_disabled" => "停用失敗",
    "successfully_restore" => "已成功恢復",
    "failure_restore" => "恢復失敗",
    "successfully_request" => "已成功向:name發送請求授權",
    "already_has_permission" => ":name已授權",
    "failure_request" => "向:name發送請求授權失敗",
    "failure_restore" => "刪除恢復",
    "failure_downloaded" => "匯出失敗",
    "copyright" => "Copyright@:year環境部環境管理署. All rights reserved",

    "role" => "角色",
    "role_name" => "角色名",
    "role_level" => "Level",
    "role_group" => "角色群組",

    "action" => "行為",
    "action_name" => "名稱",
    "action_display_name" => "顯示名稱",

    "program" => "程式代碼",
    "program_name" => "名稱",
    "program_display_name" => "顯示名稱",
    "program_name_placeholder" => "請輸入名稱",
    "program_display_name_placeholder" => "請輸入顯示名稱",

    "user" => "帳號",
    "username" => "登入帳號",
    "password" => "密碼",
    "old_password" => "舊密碼",
    "confirm_password" => "確認密碼",
    "user_name" => "名稱",
    "user_email" => "Email",
    "user_phone" => "電話",
    "user_role" => "角色",
    "user_parent" => "指導單位",
    "user_parent_error" => "指導單位錯誤",
    "edit_password" => "修改密碼",
    "userself_old_password_error" => '舊密碼錯誤',

    "location" => "場域",
    "location_county" => "縣市",
    "location_administration" => "管轄單位",
    "location_name" => "場域名稱",
    "location_address" => "場域地址",
    "location_longitude" => "經度",
    "location_latitude" => "緯度",
    "location_business_hours" => "營運時間",
    "location_image" => "場域圖片",
    "location_administration_conflict" => "該帳號已有管轄場域",
    "location_auth_code_error" => "場域授權碼錯誤",

    "toilet" => "廁所",
    "toilet_dashboard" => "數據儀表",
    "toilet_list" => "廁所清單",
    "toilet_name" => "位置",
    "toilet_code" => "廁所編號",
    "toilet_type" => "類型",
    "toilet_image" => "廁所圖片",
    "toilet_alert_token" => "通知Token",
    "toilet_type_options" => [
        Toilet::TYPE_MALE => "男廁",
        Toilet::TYPE_FEMALE => "女廁",
        Toilet::TYPE_BARRIER_FREE => "無障礙廁所",
        Toilet::TYPE_PARENT_CHILD => "親子廁所"
    ],
    "toilet_notification_start" => "通知起始時間",
    "toilet_notification_end" => "通知結束時間",

    "location_supplier" => "場域授權",
    "authorized" => "授權",
    "location_supplier_status_options" => [
        0 => "未授權",
        1 => "已授權"
    ],
    "api_and_serve_status" => "是否授權",

    "location_audit_record_status_options" => [
        0 => "待回應",
        1 => "已授權",
        2 => "已拒絕"
    ],

    "sensor_management" => "感測器管理",
    "sensor" => "感測器",
    "toilet_paper_sensor" => "廁紙感測器",
    "smelly_sensor" => "氣味感測器",
    "human_traffic_sensor" => "人流感測器",
    "hand_lotion_sensor" => "洗手液感測器",
    "temperature_sensor" => "溫度感測器",
    "relative_humidity_sensor" => "濕度感測器",

    "toilet_paper_logs" => "廁紙數據",
    "smelly_logs" => "氣味數據",
    "human_traffic_logs" => "人流數據",
    "hand_lotion_logs" => "洗手液數據",
    "temperature_logs" => "溫度數據",
    "relative_humidity_logs" => "濕度數據",

    "min_value" => "最小值",
    "max_value" => "最大值",
    "deviation_value" => "誤差值",
    "critical_value" => "臨界值",
    "sensor_name" => "名稱",
    "sensor_id" => "感測器ID",
    "is_notification" => "通報開關",

    "search_log_options" => [
        "1" => "當日",
        // "2" => "昨日",
        "3" => "當週",
        "4" => "當月",
        "5" => "區間",
    ],
    "history_data" => '歷史數據',

    "sensor_log" => "數據",
    "sensor_log_raw_data" => "上傳資料",

    "required_date" => "請輸入時間區間",
    "date_range_exceed" => "時間區間不得大於6個月",

    "view_data" => "即時資料",

    "abnormal_type_options" => [
        "App\Models\ToiletPaperSensor" => "廁紙剩餘量不足",
        "App\Models\SmellySensor" => "廁所出現異味",
        "App\Models\HandLotionSensor" => "洗手液剩餘量不足"
    ],

    "notification_send_successfully" => "成功發送通知",
    "notification_sensor_not_abnormal" => "當前設備未異常",
    "notification_send_failure" => "發送通知失敗",
    "notification_closed" => "未開啟發送通知",
];
