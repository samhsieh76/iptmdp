<?php

namespace App\Http\Controllers;

use DateTime;
use stdClass;
use Exception;
use Carbon\Carbon;
use App\Models\Toilet;
use App\Models\SmellyLog;
use App\Models\SmellySensor;
use App\Models\HandLotionLog;
use App\Utils\ErrorCodeUtils;
use App\Jobs\ProcessSmellyLog;
use App\Models\TemperatureLog;
use App\Models\ToiletPaperLog;
use App\Models\HumanTrafficLog;
use App\Models\HandLotionSensor;
use App\Models\SmellyDailyReport;
use App\Models\TemperatureSensor;
use App\Models\ToiletPaperSensor;
use App\Models\HumanTrafficSensor;
use App\Jobs\ProcessHandLotionLog;
use App\Jobs\ProcessTemperatureLog;
use App\Jobs\ProcessToiletPaperLog;
use App\Jobs\ProcessHumanTrafficLog;
use App\Jobs\ProcessRelativeHumidityLog;
use App\Models\RelativeHumidityLog;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\HandLotionDailyReport;
use App\Models\TemperatureDailyReport;
use App\Models\ToiletPaperDailyReport;
use App\Models\HumanTrafficDailyReport;
use App\Models\RelativeHumidityDailyReport;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend as ChartLegend;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\ItemNotFoundException;


class SensorLogController extends Controller {
    protected $sensor_type;
    protected $model;
    protected $sensor_model;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware(function ($request, $next) {
            $route = $request->route()->getName();
            $route_explode = explode('.', $route);
            $this->program_name = $route_explode[0];
            $this->route_name = "{$this->program_name}";

            $sensor_type = [
                'toilet_paper_logs' => 'ToiletPaper',
                'smelly_logs' => 'Smelly',
                'human_traffic_logs' => 'HumanTraffic',
                'hand_lotion_logs' => 'HandLotion',
                'temperature_logs' => 'Temperature',
                'relative_humidity_logs' => 'RelativeHumidity'
            ];

            $models = [
                'ToiletPaper' => ToiletPaperLog::class,
                'Smelly' => SmellyLog::class,
                'HumanTraffic' => HumanTrafficLog::class,
                'HandLotion' => HandLotionLog::class,
                'Temperature' => TemperatureLog::class,
                'RelativeHumidity' => RelativeHumidityLog::class,
            ];

            $sensor_models = [
                'ToiletPaper' => ToiletPaperSensor::class,
                'Smelly' => SmellySensor::class,
                'HumanTraffic' => HumanTrafficSensor::class,
                'HandLotion' => HandLotionSensor::class,
                'Temperature' => TemperatureSensor::class,
                'RelativeHumidity' => TemperatureSensor::class,
            ];

            if (!array_key_exists($this->program_name, $sensor_type)) {
                return abort(404);
            }
            $this->sensor_type = $sensor_type[$this->program_name];
            $this->model = $models[$this->sensor_type];
            $this->sensor_model = $sensor_models[$this->sensor_type];
            return $next($request);
        });
    }

    public function getResources(Request $request, $toilet_id, $sensor_id) {
        $draw = $request->get('draw');

        $fetchLocations = App::make('fetch_locations');
        try {
            $toilet = Toilet::findOrFail($toilet_id);
            $sensor = $this->sensor_model::findOrFail($sensor_id);
            $fetchLocations->firstOrFail(function ($location) use ($toilet) {
                return $location->id == $toilet->location_id;
            });

            $columnIndex_arr = $request->get('order');
            $columnName_arr = $request->get('columns');
            $order_arr = $request->get('order');

            $start = $request->get("start");
            $rowperpage = $request->get("length"); // Rows display per page

            $columnIndex = $columnIndex_arr[0]['column']; // Column index
            $columnName = $columnName_arr[$columnIndex]['data']; // Column name
            $columnSortOrder = $order_arr[0]['dir']; // asc or desc

            $startDate = date('Y-m-d H:i:s', $request->get('start_date') / 1000);
            $endDate = date('Y-m-d H:i:s', $request->get('end_date') / 1000);

            $searchValue = new stdClass();
            $searchValue->sensor_id = $sensor_id;
            $searchValue->start_date = $startDate;
            $searchValue->end_date = $endDate;

            // @todo 檢查日期時間--
            if ($searchValue->start_date == null || $searchValue->end_date == null) {
                // return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::BAD_REQUEST, null, trans('messages.required_date'));
                return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, []);
            }
            if (date('Y-m-d H:i:s', strtotime($startDate . "+6 months")) < date('Y-m-d H:i:s', strtotime($endDate . "+1 second"))) {
                return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::BAD_REQUEST, null, trans('messages.date_range_exceed'));
            }

            $totalRecordswithFilter = $this->model::select('count(*) as allcount')
                ->Search($searchValue)
                ->count();

            // Fetch records
            $records = $this->model::select('id', 'raw_data', 'value', 'created_at')
                ->Search($searchValue)
                ->skip($start)
                ->take($rowperpage)
                ->orderBy($columnName, $columnSortOrder)
                ->get();

            foreach ($records as $record) {
                $options = [];
                // 已廢棄刪除
                /* if (Auth::user()->can("{$this->program_name}.edit")) {
                    array_push($options, [
                        'label' => trans('messages.edit'),
                        'url' => route("{$this->program_name}.edit", [$toilet_id, $sensor_id, $record->id]),
                        'action' => 'edit'
                    ]);
                } */
                if (Auth::user()->can("{$this->program_name}.destroy")) {
                    array_push($options, [
                        'label' => trans('messages.delete'),
                        'url' => route("{$this->program_name}.destroy", [$toilet_id, $sensor_id, $record->id]),
                        'action' => 'delete'
                    ]);
                }
                $record->delete_options = $options;
            }
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->dataTableJsonResponse($draw, 0, 0, [
                "aaData" => []
            ]);
        }

        return $this->dataTableJsonResponse($draw, 0, $totalRecordswithFilter, [
            "aaData" => $records
        ]);
    }

    public function store(Request $request, $toilet_id, $sensor_id) {
        $validator_errors = $this->validator($request);
        if ($validator_errors) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::BAD_REQUEST, null, $validator_errors);
        }

        $fetchLocations = App::make('fetch_locations');
        try {
            $toilet = Toilet::findOrFail($toilet_id);
            $sensor = $this->sensor_model::findOrFail($sensor_id);
            $fetchLocations->firstOrFail(function ($location) use ($toilet) {
                return $location->id == $toilet->location_id;
            });
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }

        $log = new $this->model;
        switch ($this->program_name) {
            case 'hand_lotion_logs':
                $log->hand_lotion_sensor_id = $sensor_id;
                break;
            case 'human_traffic_logs':
                $log->human_traffic_sensor_id = $sensor_id;
                break;
            case 'relative_humidity_logs':
                $log->relative_humidity_sensor_id = $sensor_id;
                break;
            case 'smelly_logs':
                $log->smelly_sensor_id = $sensor_id;
                break;
            case 'temperature_logs':
                $log->temperature_sensor_id = $sensor_id;
                break;
            case 'toilet_paper_logs':
                $log->toilet_paper_sensor_id = $sensor_id;
                break;
            default:
                break;
        }
        $log->raw_data = $request->get('raw_data');
        if ($log->save()) {
            $this->event_log($log->toJson());
            $this->runLogAndDailyReportProcess($this->sensor_type, $log, (new DateTime($log->created_at))->format('Y-m-d'));
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => $sensor->name . trans('messages.sensor_log') . trans('messages.successfully_added')]);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.sensor_log') . trans('messages.failure_added'));
    }

    public function edit($toilet_id, $sensor_id, $log_id) {
        $fetchLocations = App::make('fetch_locations');
        try {
            $toilet = Toilet::findOrFail($toilet_id);
            $sensor = $this->sensor_model::findOrFail($sensor_id);
            $log = $this->model::findOrFail($log_id);
            $fetchLocations->firstOrFail(function ($location) use ($toilet) {
                return $location->id == $toilet->location_id;
            });
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }

        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, [
            'resource' => $log,
            'actionUrl' => route("{$this->program_name}.update", [$toilet_id, $sensor_id, $log_id])
        ]);
    }

    public function update(Request $request, $toilet_id, $sensor_id, $log_id) {
        $validator_errors = $this->validator($request);
        if ($validator_errors) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::BAD_REQUEST, null, $validator_errors);
        }

        $fetchLocations = App::make('fetch_locations');
        try {
            $toilet = Toilet::findOrFail($toilet_id);
            $sensor = $this->sensor_model::findOrFail($sensor_id);
            $log = $this->model::findOrFail($log_id);
            $fetchLocations->firstOrFail(function ($location) use ($toilet) {
                return $location->id == $toilet->location_id;
            });
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }

        $log->raw_data = $request->get('raw_data');
        $log->value = -1;
        if ($log->save()) {
            $this->event_log($log->toJson());
            $this->runLogAndDailyReportProcess($this->sensor_type, $log, (new DateTime($log->created_at))->format('Y-m-d'));
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => $sensor->name . trans('messages.sensor_log') . trans('messages.successfully_updated')]);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, trans('messages.sensor_log') . trans('messages.failure_updated'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($toilet_id, $sensor_id, $log_id) {
        $fetchLocations = App::make('fetch_locations');
        try {
            $toilet = Toilet::findOrFail($toilet_id);
            $sensor = $this->sensor_model::findOrFail($sensor_id);
            $log = $this->model::findOrFail($log_id);
            $fetchLocations->firstOrFail(function ($location) use ($toilet) {
                return $location->id == $toilet->location_id;
            });
        } catch (ItemNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }

        if ($log->delete()) {
            $this->event_log($log->toJson());
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, ['messages' => $sensor->name . trans('messages.sensor_log') . trans('messages.successfully_deleted')]);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, $sensor->name . trans('messages.sensor_log') . trans('messages.failure_deleted'));
    }

    public function getAllLogByDateRange(Request $request, $toilet_id, $sensor_id) {
        $fetchLocations = App::make('fetch_locations');
        try {
            $toilet = Toilet::findOrFail($toilet_id);
            $sensor = $this->sensor_model::findOrFail($sensor_id);
            $fetchLocations->firstOrFail(function ($location) use ($toilet) {
                return $location->id == $toilet->location_id;
            });

            $startDate = date('Y-m-d H:i:s', $request->get('start_date') / 1000);
            $endDate = date('Y-m-d H:i:s', $request->get('end_date') / 1000);

            $searchValue = new stdClass();
            $searchValue->sensor_id = $sensor_id;
            $searchValue->start_date = $startDate;
            $searchValue->end_date = $endDate;
            // @todo 檢查日期時間--
            if ($searchValue->start_date == null || $searchValue->end_date == null) {
                // return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::BAD_REQUEST, null, trans('messages.required_date'));
                return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, []);
            }
            if (date('Y-m-d H:i:s', strtotime($startDate . "+6 months")) < date('Y-m-d H:i:s', strtotime($endDate . "+1 second"))) {
                return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::BAD_REQUEST, null, trans('messages.date_range_exceed'));
            }

            // Fetch records
            $records = $this->model::select(
                $this->program_name == 'smelly_logs' ? 'raw_data as value' : 'value',
                DB::Raw("DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as datetime")
            )->where('value', '!=', -1)
                ->Search($searchValue)
                ->orderBy('created_at', 'asc')
                ->get();
            $records = $records->map(function ($item) {
                return [$item->datetime, $item->value];
            });
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, $records);
        } catch (Exception $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, null, 500);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, null, 500);
    }

    public function downloadCSV(Request $request, $toilet_id, $sensor_id) {
        $fetchLocations = App::make('fetch_locations');
        try {
            $toilet = Toilet::findOrFail($toilet_id);
            $sensor = $this->sensor_model::findOrFail($sensor_id);
            $location = $fetchLocations->firstOrFail(function ($location) use ($toilet) {
                return $location->id == $toilet->location_id;
            });

            $startDate = date('Y-m-d H:i:s', $request->get('start_date') / 1000);
            $endDate = date('Y-m-d H:i:s', $request->get('end_date') / 1000);

            $searchValue = new stdClass();
            $searchValue->sensor_id = $sensor_id;
            $searchValue->start_date = $startDate;
            $searchValue->end_date = $endDate;

            // @todo 檢查日期時間--
            if ($searchValue->start_date == null || $searchValue->end_date == null) {
                // return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::BAD_REQUEST, null, trans('messages.required_date'));
                return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, []);
            }
            // dd(date('Y-m-d H:i:s', strtotime($startDate . "+6 months")),  date('Y-m-d H:i:s', strtotime($endDate . "+1 second")));
            if (date('Y-m-d H:i:s', strtotime($startDate . "+6 months")) < date('Y-m-d H:i:s', strtotime($endDate . "+1 second"))) {
                return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::BAD_REQUEST, null, trans('messages.date_range_exceed'));
            }

            // Fetch records
            $records = $this->model::select(
                'id',
                'raw_data',
                'value',
                DB::Raw("DATE_FORMAT(created_at,'%Y-%m-%d') as date"),
                DB::Raw("DATE_FORMAT(created_at,'%H:%i:%s') as time")
            )->where('value', '!=', -1)
                ->Search($searchValue)
                ->orderBy('created_at', 'asc')
                ->get();


            $filename = trans("messages.{$this->program_name}") . "_{$location->name}_{$toilet->name}_" . trans('messages.toilet_type_options')[$toilet->type] . "_{$sensor->name}";
            $filename .= "_{$startDate}";
            if ($startDate != $endDate) {
                $filename .= "_{$endDate}";
            }
            $spreadsheet = $this->arrangeExportRecords($records);

            $writer = new Csv($spreadsheet);

            $encodedFilename = rawurlencode($filename);
            header('Content-Disposition: attachment; filename*=UTF-8\'\'' . $encodedFilename . '.csv');
            header('Content-Type: text/csv; charset=UTF-8');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
            return;
        } catch (Exception $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, null, 500);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, null, 500);
    }

    public function download(Request $request, $toilet_id, $sensor_id) {
        $fetchLocations = App::make('fetch_locations');
        try {
            $toilet = Toilet::findOrFail($toilet_id);
            $sensor = $this->sensor_model::findOrFail($sensor_id);
            $location = $fetchLocations->firstOrFail(function ($location) use ($toilet) {
                return $location->id == $toilet->location_id;
            });

            $startDate = date('Y-m-d H:i:s', $request->get('start_date') / 1000);
            $endDate = date('Y-m-d H:i:s', $request->get('end_date') / 1000);

            $searchValue = new stdClass();
            $searchValue->sensor_id = $sensor_id;
            $searchValue->start_date = $startDate;
            $searchValue->end_date = $endDate;

            // @todo 檢查日期時間--
            if ($searchValue->start_date == null || $searchValue->end_date == null) {
                // return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::BAD_REQUEST, null, trans('messages.required_date'));
                return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS, []);
            }
            // dd(date('Y-m-d H:i:s', strtotime($startDate . "+6 months")),  date('Y-m-d H:i:s', strtotime($endDate . "+1 second")));
            if (date('Y-m-d H:i:s', strtotime($startDate . "+6 months")) < date('Y-m-d H:i:s', strtotime($endDate . "+1 second"))) {
                return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::BAD_REQUEST, null, trans('messages.date_range_exceed'));
            }

            // Fetch records
            $records = $this->model::select(
                'id',
                'raw_data',
                'value',
                DB::Raw("DATE_FORMAT(created_at,'%Y-%m-%d') as date"),
                DB::Raw("DATE_FORMAT(created_at,'%H:%i:%s') as time")
            )->where('value', '!=', -1)
                ->Search($searchValue)
                ->orderBy('created_at', 'asc')
                ->get();
            if ($records->count() <= 0) {
                return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, "無資料", 500);
            }
            $area = "{$location->name}_{$toilet->name}_" . trans('messages.toilet_type_options')[$toilet->type] . "_{$sensor->name}";
            $filename = trans("messages.{$this->program_name}") . "_{$area}";

            $filename .= "_{$startDate}";
            if ($startDate != $endDate) {
                $filename .= "_{$endDate}";
            }
            $spreadsheet = $this->arrangeExportRecordWithCharts($records, $area, $startDate, $endDate);

            $writer = new Xlsx($spreadsheet);
            $writer->setIncludeCharts(true);

            $encodedFilename = rawurlencode($filename);
            header('Content-Disposition: attachment; filename*=UTF-8\'\'' . $encodedFilename . '.xlsx');
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=UTF-8');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
            return;
        } catch (Exception $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, null, 500);
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, null, 500);
    }

    private function validator(Request $request) {
        $input = $request->only('raw_data');
        switch ($this->program_name) {
            case 'hand_lotion_logs':
                $rule = 'required|boolean';
                break;
            case 'human_traffic_logs':
                $rule = 'required|numeric';
                break;
            case 'relative_humidity_logs':
            case 'smelly_logs':
            case 'temperature_logs':
            case 'toilet_paper_logs':
            default:
                $rule = 'required|numeric|min:0|max:100';
                break;
        }

        $validator = Validator::make($input, [
            'raw_data' => $rule,
        ], [], [
            'raw_data' => trans('messages.sensor_log_raw_data'),
        ]);
        if ($validator->fails()) {
            return $validator->errors();
        }
        return null;
    }

    /**
     * Arrange Records To Spreadsheet
     *
     * @param array $sort_records
     * @return Spreadsheet
     */
    private function arrangeExportRecords($records) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $i = 1;
        $baseRow = 2;

        switch ($this->program_name) {
            case 'hand_lotion_logs':
            case 'relative_humidity_logs':
            case 'temperature_logs':
                $headers = ['Date', 'Time', 'Raw Data'];
                foreach ($records as $k => $record) {
                    $current_column = 'A';
                    $i = $k + $baseRow;
                    $sheet->setCellValue($current_column++ . $i, $record->date);
                    $sheet->setCellValue($current_column++ . $i, $record->time);
                    $sheet->setCellValue($current_column++ . $i, $record->raw_data);
                }
                break;
            case 'human_traffic_logs':
            case 'smelly_logs':
            case 'toilet_paper_logs':
            default:
                $headers = ['Date', 'Time', 'Raw Data', 'Value'];
                foreach ($records as $k => $record) {
                    $current_column = 'A';
                    $i = $k + $baseRow;
                    $sheet->setCellValue($current_column++ . $i, $record->date);
                    $sheet->setCellValue($current_column++ . $i, $record->time);
                    $sheet->setCellValue($current_column++ . $i, $record->raw_data);
                    $sheet->setCellValue($current_column++ . $i, $record->value);
                }
                break;
        }

        // CSV 設定寬度無效
        $current_column = 'A';
        for ($i = 0; $i < count($headers); $current_column++, $i++) {
            $sheet->setCellValue($current_column . "1", $headers[$i]);
            // $sheet->getColumnDimension($current_column)->setWidth(40);
        }
        return $spreadsheet;
    }

    private function arrangeExportRecordWithCharts($records, $area, $startDate, $endDate) {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        $rowNum = 2;
        $baseRow = 2;

        switch ($this->program_name) {
            case 'hand_lotion_logs':
            case 'relative_humidity_logs':
            case 'temperature_logs':
                $headers = ['DateTime', 'Raw Data'];
                foreach ($records as $k => $record) {
                    $current_column = 'A';
                    $worksheet
                        ->setCellValue($current_column++ . $rowNum, "{$record->date} {$record->time}")
                        ->setCellValue($current_column++ . $rowNum, $record->raw_data);
                    $rowNum++;
                }
                break;
            case 'human_traffic_logs':
            case 'smelly_logs':
            case 'toilet_paper_logs':
            default:
                $headers = ['DateTime', 'Raw Data', 'Value'];
                foreach ($records as $k => $record) {
                    $current_column = 'A';
                    $worksheet->setCellValue($current_column++ . $rowNum, "{$record->date} {$record->time}")
                        ->setCellValue($current_column++ . $rowNum, $record->raw_data)
                        ->setCellValue($current_column++ . $rowNum, $record->value);
                    $rowNum++;
                }
                break;
        }

        $current_column = 'A';
        for ($i = 0; $i < count($headers); $current_column++, $i++) {
            $worksheet->setCellValue($current_column . "1", $headers[$i]);
            $worksheet->getColumnDimension($current_column)->setWidth(40);
        }
        // Set the X-Axis Labels
        $xAxisTickValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, 'Worksheet!$A$2:$A$' . (string)($rowNum - 1), null, $rowNum - 1),
        ];
        // Set the Data values for each data series we want to plot
        $dataSeriesValues = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, 'Worksheet!$B$2:$B$' . (string)($rowNum - 1), null, $rowNum - 1),
        ];
        [$paraDataLabel, $paraYAxisLabel] = $this->getParaLabel($this->program_name);
        $dataSeriesLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, null, null, 1, [$paraDataLabel]),
        ];

        $plotType = DataSeries::TYPE_LINECHART;
        if (in_array($this->program_name, ['human_traffic_logs', 'hand_lotion_logs'])) {
            $plotType = DataSeries::TYPE_BARCHART;
        }
        // 添加數據系列用於折線圖
        $dataSeries = new DataSeries(
            $plotType, // plotType
            DataSeries::GROUPING_STACKED, // plotGrouping
            range(0, count($dataSeriesValues) - 1), // plotOrder
            $dataSeriesLabels, // plotLabel
            $xAxisTickValues, // plotCategory
            $dataSeriesValues        // plotValues
        );
        $title = new Title("報表區域：{$area}\n搜尋日期：{$startDate}~{$endDate}");
        // Set the chart legend
        $legend = new ChartLegend(ChartLegend::POSITION_TOPRIGHT, null, false);
        $plotArea = new PlotArea(null, [$dataSeries]);
        $yAxisLabel = new Title($paraYAxisLabel);

        // 添加折線圖
        $chart = new Chart(
            'LineChart',
            $title,
            $legend,
            $plotArea,
            true,
            'gap', // displayBlanksAs
            null, // xAxisLabel
            $yAxisLabel  // yAxisLabel
        );

        $chart->setTopLeftPosition('E1');
        $chart->setBottomRightPosition('S30'); // 圖表位置和尺寸

        $worksheet->addChart($chart);

        return $spreadsheet;
    }

    private function getParaLabel($module) {
        switch ($module) {
            case 'hand_lotion_logs':
                return ['單位:1 = 充足，0 = 不足', '即時補充情形'];
            case 'relative_humidity_logs':
                return ['單位:%', '濕度'];
            case 'temperature_logs':
                return ['單位:˚C', '溫度'];
            case 'human_traffic_logs':
                return ['單位:人', '單位時間人流'];
            case 'smelly_logs':
                return ['單位:ppb', '氣味濃度'];
            case 'toilet_paper_logs':
                return ['單位:cm', '量測用量距離'];
            default:
                return ['', ''];
        }
    }

    private function runLogAndDailyReportProcess($type, $log, $date) {
        /*  Artisan::call(sprintf("LogProcess:%s", $type), ['--date' => $date]);
        Artisan::call(sprintf("DailyReportProcess:%s", $type), ['--date' => $date]);
        Artisan::call("DailyReportProcess:Location", ['--date' => $date]); */
        $processLogClass = [
            'ToiletPaper' => ProcessToiletPaperLog::class,
            'Smelly' => ProcessSmellyLog::class,
            'HumanTraffic' => ProcessHumanTrafficLog::class,
            'HandLotion' => ProcessHandLotionLog::class,
            'Temperature' => ProcessTemperatureLog::class,
            'RelativeHumidity' => ProcessRelativeHumidityLog::class,
        ];
        $dailyReportClass = [
            'ToiletPaper' => ToiletPaperDailyReport::class,
            'Smelly' => SmellyDailyReport::class,
            'HumanTraffic' => HumanTrafficDailyReport::class,
            'HandLotion' => HandLotionDailyReport::class,
            'Temperature' => TemperatureDailyReport::class,
            'RelativeHumidity' => RelativeHumidityDailyReport::class,
        ];
        try {
            $date = Carbon::now(config('app.timezone'));
            $processLogClass[$type]
                ::dispatch($log)
                ->onQueue('hand_lotion')
                ->afterCommit();

            // modify report.is_active if necessary
            $report = $dailyReportClass[$type]
                ::firstOrNew([
                    'date'                  => $date->format('Y-m-d'),
                    'hand_lotion_sensor_id' => $log->hand_lotion_sensor_id,
                ]);
            $report->is_active = 1;
            $report->save();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
