<?php

use App\Models\User;
use App\Models\Location;
use App\Mail\RequestLocationEmail;
use App\Models\LocationAuditRecord;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\Api\ESMSController;
use App\Http\Controllers\FrontendController;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/contact', function () {
    return view('contact');
})->name('contact');

Route::get('/choose', function () {
    return view('choose');
})->name('choose');

Route::get('/mailable', function () {
    $record = LocationAuditRecord::find(1);
    $location = Location::find($record->location_id);
    $administrator = User::find($location->administration_id);
    return new RequestLocationEmail($administrator, $location, $record);
});

Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm']);
Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login'])->name('login');
Route::post('/logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

Route::get('esms/login/test', [ESMSController::class, function (Request $request) {
    $host = $request->getHost();
    $ip = gethostbyname($host);
    return view('esms_test_page', compact('host', 'ip'));
}]);
Route::get('esms/login', [ESMSController::class, 'login'])->name('esms.login');
Route::get('esms/region/{code}', [ESMSController::class, 'getRegion'])->name('esms.region');
// 測試用
/* Route::get('esms/encrypted', [ESMSController::class, 'encrypted']);
Route::get('esms/decrypted', [ESMSController::class, 'decrypted']); */

Route::middleware(['auth:web,esms', 'frontend.data.fetch'])->group(function () {
    Route::get('/dashboard', [FrontendController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/locations', [FrontendController::class, 'getLocations'])->name('dashboard.index.locations');
    Route::get('/dashboard/{county}/locations', [FrontendController::class, 'getLocationByCounty'])->name('dashboard.index.county_locations');
    Route::get('/dashboard/{location}/toilets', [FrontendController::class, 'getToilets'])->name('dashboard.index.toilets');

    Route::get('/dashboard/operational_score', [FrontendController::class, 'getOperationalScore'])->name('dashboard.index.operational_score');
    Route::get('/dashboard/process_score', [FrontendController::class, 'getProcessScore'])->name('dashboard.index.process_score');
    Route::get('/dashboard/toilet_paper_data', [FrontendController::class, 'getToiletPaperData'])->name('dashboard.index.toilet_paper_data');
    Route::get('/dashboard/toilet_paper_refill_data', [FrontendController::class, 'getToiletPaperRefillData'])->name('dashboard.index.toilet_paper_refill_data');
    Route::get('/dashboard/human_traffic_daily_data', [FrontendController::class, 'getHumanTafficData'])->name('dashboard.index.human_traffic_daily_data');
    Route::get('/dashboard/human_traffic_monthly_data', [FrontendController::class, 'getToiletMonthlyTrafficData'])->name('dashboard.index.human_traffic_monthly_data');
    Route::get('/dashboard/hand_lotion_data', [FrontendController::class, 'getHandLotionData'])->name('dashboard.index.hand_lotion_data');
    Route::get('/dashboard/hand_lotion_refill_data', [FrontendController::class, 'getHandLotionRefillData'])->name('dashboard.index.hand_lotion_refill_data');
    Route::get('/dashboard/smelly_data', [FrontendController::class, 'getSmellyData'])->name('dashboard.index.smelly_data');
    Route::get('/dashboard/temp_humidity_data', [FrontendController::class, 'getTempHumidityData'])->name('dashboard.index.temp_humidity_data');
    Route::get('/dashboard/abnormal_data', [FrontendController::class, 'getAbnormalData'])->name('dashboard.index.abnormal_data');
    Route::get('/dashboard/improve_data', [FrontendController::class, 'getImproveData'])->name('dashboard.index.improve_data');
});

Route::middleware(['auth:web,esms', 'data.fetch'])->group(function () {

    Route::get('/home', [\App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/daily_toilet/{toilet}/toilet_paper', [\App\Http\Controllers\DailyToiletDataController::class, 'getToiletPaperDataByDay'])->name('daily.toilet_paper_data');
    Route::get('/daily_toilet/{toilet}/human_traffic', [\App\Http\Controllers\DailyToiletDataController::class, 'getHumanTrafficDataByDay'])->name('daily.human_traffic_data');
    Route::get('/daily_toilet/{toilet}/smelly', [\App\Http\Controllers\DailyToiletDataController::class, 'getSmellyDataByDay'])->name('daily.smelly_data');
    Route::get('/daily_toilet/{toilet}/hand_lotion', [\App\Http\Controllers\DailyToiletDataController::class, 'getHandLotionDataByDay'])->name('daily.hand_lotion_data');
    Route::get('/daily_toilet/{toilet}/temp_humidity', [\App\Http\Controllers\DailyToiletDataController::class, 'getTempHumidityDataByDay'])->name('daily.temp_humidity_data');

    Route::middleware(['user.permission'])->group(function () {
        Route::resource('roles', \App\Http\Controllers\RoleController::class);
        Route::get('/roles_data', [\App\Http\Controllers\RoleController::class, 'getResources'])->name('roles.index.data');
        Route::get('/roles/{id}/permission', [\App\Http\Controllers\RoleController::class, 'permission'])->name('roles.permission.index');
        Route::put('/roles/{id}/permission', [\App\Http\Controllers\RoleController::class, 'update_permission'])->name('roles.permission.update');

        Route::resource('users', \App\Http\Controllers\UserController::class);
        Route::put('/users/{users}/password', [\App\Http\Controllers\UserController::class, 'password'])->name('users.password');
        Route::put('/user/password', [\App\Http\Controllers\UserController::class, 'selfPassword'])->name('users.password.self');
        Route::get('/users_data', [\App\Http\Controllers\UserController::class, 'getResources'])->name('users.index.data');
        Route::get('/roles/{roles}/available_parents', [\App\Http\Controllers\UserController::class, 'getAvailableParent'])->name('users.index.parents');
        Route::delete('users/{user}/restore', [\App\Http\Controllers\UserController::class, 'restore'])->name('users.restore');

        Route::resource('locations', \App\Http\Controllers\LocationController::class);
        Route::get('/locations_data', [\App\Http\Controllers\LocationController::class, 'getResources'])->name('locations.index.data');
        Route::put('/location/request_permission', [\App\Http\Controllers\LocationController::class, 'requestPermission'])->name('locations.request_permission');
        Route::get('/location/request_permission/accept', [\App\Http\Controllers\LocationController::class, 'accept'])->name('locations.accept');
        Route::get('locations/{location}/toilets', [\App\Http\Controllers\ToiletController::class, 'index'])->name('toilets.index');
        Route::get('locations/{location}/toilets/{toilet}', [\App\Http\Controllers\ToiletController::class, 'show'])->name('toilets.show');
        Route::get('locations/{location}/toilet_data', [\App\Http\Controllers\ToiletController::class, 'getResources'])->name('toilets.index.data');
        Route::post('locations/{location}/toilets', [\App\Http\Controllers\ToiletController::class, 'store'])->name('toilets.store');
        Route::get('locations/{location}/toilets/{toilet}/edit', [\App\Http\Controllers\ToiletController::class, 'edit'])->name('toilets.edit');
        Route::put('locations/{location}/toilets/{toilet}', [\App\Http\Controllers\ToiletController::class, 'update'])->name('toilets.update');
        Route::delete('locations/{location}/toilets/{toilet}', [\App\Http\Controllers\ToiletController::class, 'destroy'])->name('toilets.destroy');

        Route::resource('actions', \App\Http\Controllers\ActionController::class);
        Route::get('/actions_data', [\App\Http\Controllers\ActionController::class, 'getResources'])->name('actions.index.data');

        Route::resource('programs', \App\Http\Controllers\ProgramController::class);
        Route::get('/programs_data', [\App\Http\Controllers\ProgramController::class, 'getResources'])->name('programs.index.data');

        Route::resource('api_and_serves', \App\Http\Controllers\ApiandServeController::class);
        Route::get('/api_and_serves_data', [\App\Http\Controllers\ApiandServeController::class, 'getResources'])->name('api_and_serves.index.data');
        Route::get('/api_and_serves_download', [\App\Http\Controllers\ApiandServeController::class, 'download'])->name('api_and_serves.download');
        Route::post('/api_and_serves/permission', [\App\Http\Controllers\ApiandServeController::class, 'updatePermission'])->name('api_and_serves.update.permission');

        Route::get('/location_audit_records_data', [\App\Http\Controllers\LocationAuditRecordController::class, 'getResources'])->name('location_audit_records.index.data');

        $sensorRoutes = [
            // 人流感測
            'human_traffic' => \App\Http\Controllers\HumanTrafficController::class,
            // 洗手液感測
            'hand_lotion' => \App\Http\Controllers\HandLotionController::class,
            // 廁紙感測
            'toilet_paper' => \App\Http\Controllers\ToiletPaperController::class,
            // 氣味感測
            'smelly' => \App\Http\Controllers\SmellyController::class,
            // 溫度感測
            'temperature' => \App\Http\Controllers\TemperatureController::class,
            // 濕度感測
            'relative_humidity' => \App\Http\Controllers\RelativeHumidityController::class,
        ];

        foreach ($sensorRoutes as $sensor => $controller) {
            Route::prefix("locations/{location}/sensors")->group(function () use ($sensor, $controller) {
                $controller = \App\Http\Controllers\LocationSensorController::class;
                Route::get("", [$controller, 'index'])->name("location_sensors.index");
                Route::get("{$sensor}_sensor_data", [$controller, 'getResources'])->name("location_sensors.index.{$sensor}_sensors");
            });
        }


        Route::middleware(['sensor_menu.fetch'])->group(function () use ($sensorRoutes) {

            foreach ($sensorRoutes as $sensor => $controller) {
                Route::prefix("toilets/{toilet}/{$sensor}_sensors")->group(function () use ($sensor, $controller) {
                    Route::get('', [$controller, 'index'])->name("{$sensor}_sensors.index");
                    Route::get('sensor_data', [$controller, 'getResources'])->name("{$sensor}_sensors.index.data");
                    Route::get('{sensor}', [$controller, 'show'])->name("{$sensor}_sensors.show");
                    Route::post('', [$controller, 'store'])->name("{$sensor}_sensors.store");
                    Route::get('{sensor}/edit', [$controller, 'edit'])->name("{$sensor}_sensors.edit");
                    Route::put('{sensor}', [$controller, 'update'])->name("{$sensor}_sensors.update");
                    Route::delete('{sensor}', [$controller, 'destroy'])->name("{$sensor}_sensors.destroy");
                    // 溫濕度沒有通報
                    if (!in_array($sensor, ['temperature', 'relative_humidity'])) {
                        Route::put('{sensor}/toggleNotification', [$controller, 'toggleNotification'])->name("{$sensor}_sensors.toggle_notification");
                    }

                    // 溫濕度、人流沒有手動通報
                    /* if (!in_array($sensor, ['temperature', 'relative_humidity', 'human_traffic'])) {
                        Route::post('{sensor}/sendNotification', [$controller, 'sendNotification'])->name("{$sensor}_sensors.send_notification");
                    } */
                });

                Route::prefix("toilets/{toilet}/sensors/{sensor}/{$sensor}_logs")->group(function () use ($sensor) {
                    $log_controller = \App\Http\Controllers\SensorLogController::class;
                    Route::get('log_data', [$log_controller, 'getResources'])->name("{$sensor}_logs.index.data");
                    Route::get('chart_data', [$log_controller, 'getAllLogByDateRange'])->name("{$sensor}_logs.index.chart_data");
                    Route::post('', [$log_controller, 'store'])->name("{$sensor}_logs.store");
                    Route::get('{log}/edit', [$log_controller, 'edit'])->name("{$sensor}_logs.edit");
                    Route::put('{log}', [$log_controller, 'update'])->name("{$sensor}_logs.update");
                    Route::delete('{log}', [$log_controller, 'destroy'])->name("{$sensor}_logs.destroy");
                    Route::get('download', [$log_controller, 'download'])->name("{$sensor}_logs.download");
                });
            }
        });
    });
});
