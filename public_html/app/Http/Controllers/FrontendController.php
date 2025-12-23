<?php

namespace App\Http\Controllers;

use App\Models\Abnormal;
use Carbon\Carbon;
use App\Models\Region;
use App\Models\Toilet;
use Illuminate\Http\Request;
use App\Utils\ErrorCodeUtils;
use App\Models\HandLotionSensor;
use App\Models\TemperatureSensor;
use App\Models\ToiletPaperSensor;
use App\Services\LocationService;
use App\Models\HumanTrafficSensor;
use Illuminate\Support\Facades\DB;
use App\Models\HandLotionRefillLog;
use Illuminate\Support\Facades\App;
use App\Models\ToiletPaperRefillLog;
use Illuminate\Support\Facades\Auth;
use App\Models\HumanTrafficDailyReport;
use App\Models\LocationDailyReport;
use App\Models\SmellySensor;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class FrontendController extends Controller {

    public function index() {
        $fetchLocations = App::make('fetch_locations');
        $regions = Region::orderBy('id', 'desc')->get();

        return view('frontend.index', compact('fetchLocations', 'regions'));
    }

    public function getLocations() {
        $fetchLocations = App::make('fetch_locations');
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, $fetchLocations);
    }

    public function getLocationByCounty($county_id) {
        $fetchLocations = App::make('fetch_locations');
        $filteredLocations = $fetchLocations->where('county_id', $county_id)->values();

        foreach ($filteredLocations as $item) {
            $item->image = $item->image ? site_image($item->image) : asset("assets/images/empty_image.png");
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, $filteredLocations);
    }

    public function getToilets(LocationService $locationService, $locationId) {
        try {
            $locationService->validateUserLocationAccess($locationId);
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }
        $query = Toilet::select('id', 'name', 'image', 'type', 'creator_id', 'code')->where('location_id', $locationId);
        if (!Auth::user()->can('toilets.full')) {
            $query->where('toilets.creator_id', '=', Auth::user()->id);
        }
        $records = $query->get();
        foreach ($records as $item) {
            $item->image = $item->image ? site_image($item->image) : asset("assets/images/empty_image.png");
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, $records);
    }

    public function getOperationalScore(Request $request) {
        $fetchLocations = App::make('fetch_locations');
        $countyId = $request->get('county_id');
        $date = $request->get('date') ?? date('Y-m-d');
        $monthStart = date('Y-m-01', strtotime($date));
        $monthEnd = date('Y-m-t', strtotime($date));

        $query = LocationDailyReport::whereBetween('location_daily_reports.date', [$monthStart, $monthEnd])->where('total_sensor_count', '>', 0);
        // 不可查看所有場域 或 根據縣市搜尋
        if (!Auth::user()->can('locations.full') || $countyId) {
            $fetchLocations = App::make('fetch_locations');
            $locationIds = ($countyId ? $fetchLocations->where('county_id', $countyId) : $fetchLocations)
                ->pluck('id')
                ->values();

            $query->whereIn('location_daily_reports.location_id', $locationIds);
        }
        if (!Auth::user()->can('toilets.full')) {
            $query->join('toilets', function ($join) {
                $join->on('toilets.id', '=', 'location_daily_reports.toilet_id')->where('toilets.creator_id', '=', Auth::user()->id);
            });
        }
        // 另有張表紀錄各縣市的每日營運分數
        $monthlyRecords = $query
            ->select('date', DB::Raw('sum(location_daily_reports.total_sensor_count) as total_count'), DB::Raw('sum(acting_sensor_count) as filter_count'))
            ->orderBy('date', 'asc')
            ->groupBy('date')
            ->get();

        $currentDateRecord = $monthlyRecords->where('date', $date)->first();

        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, [
            'total_count' => $currentDateRecord ? $currentDateRecord->total_count : 0,
            'filter_count' => $currentDateRecord ? $currentDateRecord->filter_count : 0,
            'monthly_records' => $monthlyRecords
        ]);
    }

    public function getProcessScore(Request $request) {
        $date = $request->get('date') ?? date('Y-m-d');
        $startDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();

        $countyId = $request->get('county_id');
        $query = Abnormal::whereBetween('abnormals.created_at', [$startDate, $endDate]);

        // 不可查看所有場域 或 根據縣市搜尋
        if (!Auth::user()->can('locations.full') || $countyId) {
            $fetchLocations = App::make('fetch_locations');
            $locationIds = ($countyId ? $fetchLocations->where('county_id', $countyId) : $fetchLocations)
                ->pluck('id')
                ->values();

            $query->join('toilets', function ($join) use ($locationIds) {
                $join->on('toilets.id', 'abnormals.toilet_id')->whereIn('toilets.location_id', $locationIds);
                if (!Auth::user()->can('toilets.full')) {
                    $join->where('toilets.creator_id', '=', Auth::user()->id);
                }
            });
        }
        $records = $query->select('abnormals.is_improved', 'abnormals.improved_at', 'abnormals.improve_efficient', 'abnormals.created_at')->get();

        if ($records->count() <= 0) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, [
                'average_processing' => null
            ]);
        }
        $accumulatedEfficient = 0;
        foreach ($records as $record) {
            if ($record->is_improved) {
                $accumulatedEfficient += (int)$record->improve_efficient;
            } else {
                $createdAt = $record->created_at;
                $seconds = max(0, $createdAt->diffInSeconds('now', false));
                $accumulatedEfficient += $seconds;
            }
        }
        $average_processing = round(($accumulatedEfficient / 60) / $records->count());
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, [
            'average_processing' => $average_processing
        ]);
    }


    public function getHumanTafficData(LocationService $locationService, Request $request) {
        $date = $request->get('date');
        $startDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();
        $dayOfWeek = Carbon::createFromFormat('Y-m-d', $date)->dayOfWeek;

        $records = [];
        try {
            if ($request->filled('toilet_id')) {
                $toiletId = $request->get('toilet_id');
                $toilet = Toilet::findOrFail($toiletId);
                $locationService->validateUserLocationAccess($toilet->location_id);
                $records = $this->getToiletTrafficDataByDate([$toilet->id], $startDate, $endDate);
                $dailyAverage = $this->getToiletTrafficDailyAverage([$toilet->id], $date);
            } else if ($request->filled('location_id')) {
                $locationId = $request->get('location_id');
                $locationService->validateUserLocationAccess($locationId);
                $records = $this->getLocationTrafficDataByDate($locationId, $startDate, $endDate);
                $dailyAverage = $this->getLocationTrafficDailyAverage($locationId, $date);
            } else {
                return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE);
            }
            $accumulatedCount = $records['accumulatedCount'];
            $overAverage = false;
            if (($dayOfWeek >= 1 && $dayOfWeek <= 5 && $accumulatedCount > $dailyAverage['averageWeekday']) || ($dayOfWeek >= 6 && $accumulatedCount > $dailyAverage['averageWeekend'])) {
                $overAverage = true;
            }
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, $records + ['overAverage' => $overAverage] + $dailyAverage);
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }
    }

    public function getLocationTrafficDataByDate($locationId, $startDate, $endDate) {
        $query = Toilet::select('id', 'name', 'image', 'type', 'creator_id')->where('location_id', $locationId);
        if (!Auth::user()->can('toilets.full')) {
            $query->where('toilets.creator_id', '=', Auth::user()->id);
        }
        $toilets = $query->pluck('id')->values();
        return $this->getToiletTrafficDataByDate($toilets, $startDate, $endDate);
    }

    public function getToiletTrafficDataByDate($toilets, $startDate, $endDate) {
        $logs = HumanTrafficSensor::whereIn('toilet_id', $toilets)->select('logs.value', DB::Raw("DATE_FORMAT(logs.created_at,'%H') as hour"))->where('logs.value', '!=', -1)->JoinLogByDate($startDate, $endDate)->orderBy('hour', 'asc')->get();
        $accumulatedCount = $logs->sum('value');
        $grouped = $logs->groupBy('hour');
        $latest_data = $grouped->count() > 0 ? $grouped->last()->sum('value') : null;
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
        return ['accumulatedCount' => $accumulatedCount, 'data' => $result, 'latest_data' => $latest_data];
    }

    private function getLocationTrafficDailyAverage($locationId, $date) {
        $query = Toilet::select('id', 'name', 'image', 'type', 'creator_id')->where('location_id', $locationId);
        if (!Auth::user()->can('toilets.full')) {
            $query->where('toilets.creator_id', '=', Auth::user()->id);
        }
        $toilets = $query->pluck('id')->values();
        return $this->getToiletTrafficDailyAverage($toilets, $date);
    }

    private function getToiletTrafficDailyAverage($toilets, $date) {
        $startOfMonth = Carbon::createFromFormat('Y-m-d', $date)->subDays(30);
        $endOfMonth = Carbon::createFromFormat('Y-m-d', $date);

        $records = DB::table('human_traffic_daily_reports')
            ->join('human_traffic_sensors', function ($join) use ($toilets, $startOfMonth, $endOfMonth) {
                $join->on('human_traffic_daily_reports.human_traffic_sensor_id', '=', 'human_traffic_sensors.id')
                    ->whereIn('human_traffic_sensors.toilet_id', $toilets)
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

    public function getToiletMonthlyTrafficData(LocationService $locationService, Request $request) {
        $date = $request->get('date') ?? date('Y-m-d');
        $monthStart = date('Y-m-01', strtotime($date));
        $monthEnd = date('Y-m-t', strtotime($date));
        $toiletId = $request->get('toilet_id');
        $records = [];
        try {
            $toilet = Toilet::findOrFail($toiletId);
            $locationService->validateUserLocationAccess($toilet->location_id);
            $records = HumanTrafficDailyReport::join('human_traffic_sensors', function ($join) use ($toilet, $monthStart, $monthEnd) {
                $join->on('human_traffic_daily_reports.human_traffic_sensor_id', 'human_traffic_sensors.id')
                    ->where('human_traffic_sensors.toilet_id', '=', $toilet->id)
                    ->whereBetween('human_traffic_daily_reports.date', [$monthStart, $monthEnd]);
            })->groupBy('date')
                ->orderBy('date', 'asc')
                ->select(DB::Raw('SUM(human_traffic_daily_reports.summary_value) as summary_value'), 'date')->get();

            $accumulatedCount = $records->count() > 0 ? $records->sum('summary_value') : null;
            $currentDateRecord = $records->where('date', $date)->first();
            $count = $currentDateRecord ? $currentDateRecord->summary_value : 0;

            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, [
                'accumulatedCount' => $accumulatedCount,
                'data' => $records,
                'count' => (int)$count,
                'start' => $monthStart,
                'end' => $monthEnd,
            ]);
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }
    }

    public function getToiletPaperData(LocationService $locationService, Request $request) {
        $date = $request->get('date') ?? date('Y-m-d');
        $monthStart = date('Y-m-01', strtotime($date));
        $monthEnd = date('Y-m-t', strtotime($date));
        $toiletId = $request->get('toilet_id');
        $records = [];
        try {
            $toilet = Toilet::findOrFail($toiletId);
            $locationService->validateUserLocationAccess($toilet->location_id);
            $records = ToiletPaperSensor::where('toilet_id', $toiletId)->select(DB::Raw('round(avg(report.average_value)) as average'), 'report.date')
                ->orderBy('report.date', 'asc')
                ->groupBy('report.date')
                ->JoinReportByDateRange($monthStart, $monthEnd)
                ->get();

            $currentDateRecord = $records->where('date', $date)->first();
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }

        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, [
            'average' => $currentDateRecord ? $currentDateRecord->average : null,
            'monthly_records' => $records
        ]);
    }

    public function getToiletPaperRefillData(LocationService $locationService, Request $request) {
        $date = $request->get('date') ?? date('Y-m-d');
        $startDate = Carbon::createFromFormat('Y-m-d', $date)->subMonths(3)->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();

        $toiletId = $request->get('toilet_id');
        try {
            $toilet = Toilet::findOrFail($toiletId);
            $locationService->validateUserLocationAccess($toilet->location_id);
            $sensors = ToiletPaperSensor::where('toilet_id', '=', $toiletId)->select('id')->pluck('id')->values();
            $records = ToiletPaperRefillLog::whereIn('toilet_paper_sensor_id', $sensors)
                ->join('toilet_paper_sensors', function ($join) use ($toiletId, $startDate, $endDate) {
                    $join->on('toilet_paper_sensors.id', '=', 'toilet_paper_refill_logs.toilet_paper_sensor_id')
                        ->where('toilet_id', '=', $toiletId)
                        ->whereBetween('toilet_paper_refill_logs.created_at', [$startDate, $endDate]);
                })
                ->select('toilet_paper_sensors.name as sensor_name', DB::Raw("DATE_FORMAT(toilet_paper_refill_logs.created_at, '%Y-%m-%d %H:%i:%s') as datetime"))
                ->orderBy('toilet_paper_refill_logs.created_at', 'desc')
                ->get();
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }

        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, $records);
    }

    public function getHandLotionData(LocationService $locationService, Request $request) {
        $date = $request->get('date');
        try {
            $toiletId = $request->get('toilet_id');
            $toilet = Toilet::findOrFail($toiletId);
            $locationService->validateUserLocationAccess($toilet->location_id);

            $res = HandLotionSensor::where('toilet_id', $toiletId)->select('report.value', 'hand_lotion_sensors.id', 'hand_lotion_sensors.name')->JoinReportByDate($date)->get();
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
        })->toArray();

        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['empty_count' => $empty, 'full_count' => $full, 'empty_sensors' => $emptySensors]);
    }

    public function getHandLotionRefillData(LocationService $locationService, Request $request) {
        $date = $request->get('date') ?? date('Y-m-d');
        $startDate = Carbon::createFromFormat('Y-m-d', $date)->subMonths(3)->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();

        $toiletId = $request->get('toilet_id');
        try {
            $toilet = Toilet::findOrFail($toiletId);
            $locationService->validateUserLocationAccess($toilet->location_id);
            $sensors = HandLotionSensor::where('toilet_id', '=', $toiletId)->select('id')->pluck('id')->values();
            $records = HandLotionRefillLog::whereIn('hand_lotion_sensor_id', $sensors)
                ->join('hand_lotion_sensors', function ($join) use ($toiletId, $startDate, $endDate) {
                    $join->on('hand_lotion_sensors.id', '=', 'hand_lotion_refill_logs.hand_lotion_sensor_id')
                        ->where('toilet_id', '=', $toiletId)
                        ->whereBetween('hand_lotion_refill_logs.created_at', [$startDate, $endDate]);
                })
                ->select('hand_lotion_sensors.name as sensor_name', DB::Raw("DATE_FORMAT(hand_lotion_refill_logs.created_at, '%Y-%m-%d %H:%i:%s') as datetime"))
                ->orderBy('hand_lotion_refill_logs.created_at', 'desc')
                ->get();
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }

        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, $records);
    }

    public function getSmellyData(LocationService $locationService, Request $request) {
        $date = $request->get('date') ?? date('Y-m-d');
        $monthStart = date('Y-m-01', strtotime($date));
        $monthEnd = date('Y-m-t', strtotime($date));
        $toiletId = $request->get('toilet_id');
        $records = [];
        try {
            $toilet = Toilet::findOrFail($toiletId);
            $locationService->validateUserLocationAccess($toilet->location_id);

            $records = SmellySensor::where('toilet_id', $toiletId)->select(DB::Raw('round(avg(report.average_value)) as average'), 'report.date')
                ->orderBy('report.date', 'asc')
                ->groupBy('report.date')
                ->JoinReportByDateRange($monthStart, $monthEnd)
                ->get();
            foreach ($records as $record) {
                $record->average = max(0, min(100, $record->average));
            }
            $currentDateRecord = $records->where('date', $date)->first();
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }

        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, [
            'average' => $currentDateRecord ? $currentDateRecord->average : null,
            'monthly_records' => $records
        ]);
    }

    public function getTempHumidityData(LocationService $locationService, Request $request) {
        $date = $request->get('date') ?? date('Y-m-d');
        try {
            $toiletId = $request->get('toilet_id');
            $toilet = Toilet::findOrFail($toiletId);
            $locationService->validateUserLocationAccess($toilet->location_id);

            $temp_res = TemperatureSensor::where('toilet_id', $toiletId)->select('report.average_value')->JoinTempReportByDate($date)->get();
            $humidity_res = TemperatureSensor::where('toilet_id', $toiletId)->select('report.average_value')->JoinHumidityReportByDate($date)->get();
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

    public function getAbnormalData(LocationService $locationService, Request $request) {
        $date = $request->get('date') ?? date('Y-m-d');
        $startDate = Carbon::createFromFormat('Y-m-d', $date)->subMonths(3)->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();
        $abnormal_type_options = trans('messages.abnormal_type_options');

        $records = [];
        try {
            $toiletId = $request->get('toilet_id');
            $toilet = Toilet::findOrFail($toiletId);
            $locationService->validateUserLocationAccess($toilet->location_id);

            $records = Abnormal::where('toilet_id', $toiletId)
                ->where('is_improved', '=', Abnormal::TYPE_ABNORMAL)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select('triggerable_id', 'triggerable_type', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($records as $record) {
                $record->sensor_name = $record->triggerable->name;
                $record->abnormal_type = $abnormal_type_options[$record->triggerable_type];
            }
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }

        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, $records);
    }

    public function getImproveData(LocationService $locationService, Request $request) {
        $date = $request->get('date') ?? date('Y-m-d');
        $startDate = Carbon::createFromFormat('Y-m-d', $date)->subMonths(3)->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();
        $abnormal_type_options = trans('messages.abnormal_type_options');

        $records = [];
        try {
            $toiletId = $request->get('toilet_id');
            $toilet = Toilet::findOrFail($toiletId);
            $locationService->validateUserLocationAccess($toilet->location_id);

            $records = Abnormal::where('toilet_id', $toiletId)
                ->where('is_improved', '=', Abnormal::TYPE_IMPROVE)
                ->whereBetween('improved_at', [$startDate, $endDate])
                ->select('triggerable_id', 'triggerable_type', 'improved_at', 'improve_efficient')
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($records as $record) {
                $record->efficient = $this->formatSecondsToHoursAndMinutes($record->improve_efficient);
                $record->sensor_name = $record->triggerable->name;
                $record->abnormal_type = $abnormal_type_options[$record->triggerable_type];
            }
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }

        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, $records);
    }

    private function formatSecondsToHoursAndMinutes($seconds) {
        $minutes = round($seconds / 60);
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        $formattedTime = "{$hours}時{$remainingMinutes}分";
        return $formattedTime;
    }
}
