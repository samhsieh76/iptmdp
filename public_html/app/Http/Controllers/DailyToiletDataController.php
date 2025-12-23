<?php

namespace App\Http\Controllers;

use App\Models\HandLotionSensor;
use Exception;
use Carbon\Carbon;
use App\Models\Toilet;
use Illuminate\Http\Request;
use App\Utils\ErrorCodeUtils;
use App\Models\ToiletPaperSensor;
use App\Models\HumanTrafficSensor;
use App\Models\SmellySensor;
use App\Models\TemperatureSensor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use App\Models\ToiletPaperDailyReport;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class DailyToiletDataController extends Controller {
    //

    public function getToiletPaperDataByDay(Request $request, $toilet_id) {
        $fetchLocations = App::make('fetch_locations');
        $date = $request->get('date');
        try {
            $toilet = Toilet::findOrFail($toilet_id);
            $fetchLocations->firstOrFail(function ($location) use ($toilet) {
                return $location->id == $toilet->location_id;
            });
            $res = ToiletPaperSensor::where('toilet_id', $toilet_id)->select('report.average_value')->JoinReportByDate($date)->get();
            $average = $res->avg('average_value');
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['average_value' => $average ? round($average, 2) : null]);
    }

    public function getHumanTrafficDataByDay(Request $request, $toilet_id) {
        $fetchLocations = App::make('fetch_locations');
        $date = $request->get('date');

        $dayOfWeek = Carbon::createFromFormat('Y-m-d', $date)->dayOfWeek;
        $startDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();
        try {
            $toilet = Toilet::findOrFail($toilet_id);
            $fetchLocations->firstOrFail(function ($location) use ($toilet) {
                return $location->id == $toilet->location_id;
            });
            $logs = HumanTrafficSensor::where('toilet_id', $toilet_id)->select('logs.value', DB::Raw("DATE_FORMAT(logs.created_at,'%H') as hour"))->where('logs.value', '!=', -1)->JoinLogByDate($startDate, $endDate)->orderBy('hour', 'asc')->get();
            $accumulatedCount = $logs->sum('value');
            $grouped = $logs->groupBy('hour');
            $latest_data = $grouped->count() > 0 ? $grouped->last()->sum('value') : null;
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }
        // 補滿 0~24 的範圍
        $fullRange = range(0, 23);
        // 轉換資料並補滿缺少的部分
        $result = array_map(function ($hour) use ($grouped) {
            $hour = str_pad($hour, 2, '0', STR_PAD_LEFT);
            return isset($grouped[$hour]) ? [
                'hour' => $grouped[$hour]->first()['hour'], // group by key
                'total_num' => $grouped[$hour]->sum('value')
            ] : [
                "hour" => $hour,
                "total_num" => 0
            ];
        }, $fullRange);

        // 轉換為索引式陣列
        $result = array_values($result);
        $dailyAverage = $this->getHumanTrafficDailyAverage($date, $toilet_id);
        $overAverage = false;
        if (($dayOfWeek >= 1 && $dayOfWeek <= 5 && $accumulatedCount > $dailyAverage['averageWeekday']) || ($dayOfWeek >= 6 && $accumulatedCount > $dailyAverage['averageWeekend'])) {
            $overAverage = true;
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, $dailyAverage + [
            'accumulatedCount' => $accumulatedCount,
            'data' => $result,
            'overAverage' => $overAverage,
            'latest_data' => $latest_data
        ]);
    }

    public function getHumanTrafficDailyAverage($date, $toilet_id) {
        $startOfMonth = Carbon::createFromFormat('Y-m-d', $date)->subDays(30);
        $endOfMonth = Carbon::createFromFormat('Y-m-d', $date);

        $records = DB::table('human_traffic_daily_reports')
            ->join('human_traffic_sensors', function ($join) use ($toilet_id, $startOfMonth, $endOfMonth) {
                $join->on('human_traffic_daily_reports.human_traffic_sensor_id', '=', 'human_traffic_sensors.id')
                    ->where('human_traffic_sensors.toilet_id', '=', $toilet_id)
                    ->whereBetween('human_traffic_daily_reports.date', [$startOfMonth, $endOfMonth]);
            })
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->select(
                DB::raw('DATE(date) as day'),
                DB::raw('SUM(summary_value) as total_value'),
                DB::raw('CASE
                WHEN DAYOFWEEK(date) IN (2, 3, 4, 5, 6) THEN "Weekday"
                WHEN DAYOFWEEK(date) IN (1, 7) THEN "Weekend"
                END AS day_type')
            )
            ->get();

        $weekdayTotal = 0;
        $weekendTotal = 0;
        $weekdayCount = 0;
        $weekendCount = 0;

        foreach ($records as $record) {
            if ($record->day_type === 'Weekday') {
                $weekdayTotal += $record->total_value;
                $weekdayCount++;
            } elseif ($record->day_type === 'Weekend') {
                $weekendTotal += $record->total_value;
                $weekendCount++;
            }
        }

        $averageWeekday = ($weekdayCount > 0) ? ($weekdayTotal / $weekdayCount) : 0;
        $averageWeekend = ($weekendCount > 0) ? ($weekendTotal / $weekendCount) : 0;

        return [
            'averageWeekday' => round($averageWeekday, 0),
            'averageWeekend' => round($averageWeekend, 0),
        ];
    }

    public function getSmellyDataByDay(Request $request, $toilet_id) {
        $fetchLocations = App::make('fetch_locations');
        $date = $request->get('date');
        try {
            $toilet = Toilet::findOrFail($toilet_id);
            $fetchLocations->firstOrFail(function ($location) use ($toilet) {
                return $location->id == $toilet->location_id;
            });
            //$res = SmellySensor::where('toilet_id', $toilet_id)->select('smelly_sensors.min_value', 'smelly_sensors.max_value', 'report.average_value')->JoinReportByDate($date)->get();
            $res = SmellySensor::where('toilet_id', $toilet_id)->select('report.average_value')->JoinReportByDate($date)->get();
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }
        $average = $res->map(function ($item) {
            return max(0, min(100, $item->average_value));
        })->avg();
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['average_value' => $average]);
    }

    public function getHandLotionDataByDay(Request $request, $toilet_id) {
        $fetchLocations = App::make('fetch_locations');
        $date = $request->get('date');
        try {
            $toilet = Toilet::findOrFail($toilet_id);
            $fetchLocations->firstOrFail(function ($location) use ($toilet) {
                return $location->id == $toilet->location_id;
            });
            $res = HandLotionSensor::where('toilet_id', $toilet_id)->select('report.value', 'hand_lotion_sensors.id', 'hand_lotion_sensors.name')->JoinReportByDate($date)->get();
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }

        $empty = $res->filter(function ($item) {
            return $item->value === 0;
        })->count();

        $full = $res->filter(function ($item) {
            return $item->value > 0;
        })->count();

        $emptySensors = $res->filter(function ($item) {
            return $item->value === 0;
        });

        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['empty_count' => $empty, 'full_count' => $full, 'empty_sensors' => $emptySensors]);
    }

    public function getTempHumidityDataByDay(Request $request, $toilet_id) {
        $fetchLocations = App::make('fetch_locations');
        $date = $request->get('date');
        try {
            $toilet = Toilet::findOrFail($toilet_id);
            $fetchLocations->firstOrFail(function ($location) use ($toilet) {
                return $location->id == $toilet->location_id;
            });
            $temp_res = TemperatureSensor::where('toilet_id', $toilet_id)->select('report.average_value')->JoinTempReportByDate($date)->get();
            $humidity_res = TemperatureSensor::where('toilet_id', $toilet_id)->select('report.average_value')->JoinHumidityReportByDate($date)->get();
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }

        $temp_average_value = $temp_res->avg('average_value');
        $humidity_average_value = $humidity_res->avg('average_value');


        if ($temp_average_value != null) {
            $temp_average_value =  round($temp_average_value);
        }
        if ($humidity_average_value != null) {
            $humidity_average_value =  round($humidity_average_value);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['temp_average_value' => $temp_average_value, 'humidity_average_value' => $humidity_average_value]);
    }
}
