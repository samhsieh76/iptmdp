<?php

use App\Http\Controllers\Api\TestDataImportController;
use App\Http\Controllers\HandLotionController;
use App\Http\Controllers\HumanTrafficController;
use App\Http\Controllers\RelativeHumidityController;
use App\Http\Controllers\SmellyController;
use App\Http\Controllers\TemperatureController;
use App\Http\Controllers\ToiletPaperController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('sensor.upload')->group(function () {
    Route::post('toilet_paper/receive_sensor_data', [ToiletPaperController::class, 'receiveSensorData']);
    Route::post('smelly/receive_sensor_data', [SmellyController::class, 'receiveSensorData']);
    Route::post('human_traffic/receive_sensor_data', [HumanTrafficController::class, 'receiveSensorData']);
    Route::post('hand_lotion/receive_sensor_data', [HandLotionController::class, 'receiveSensorData']);
    Route::post('temperature/receive_sensor_data', [TemperatureController::class, 'receiveSensorData']);
    Route::post('relative_humidity/receive_sensor_data', [RelativeHumidityController::class, 'receiveSensorData']);
});

Route::post('test-data/import-file', [TestDataImportController::class, 'importTestDataByFile']);
Route::post('test-data/import', [TestDataImportController::class, 'importTestData']);
