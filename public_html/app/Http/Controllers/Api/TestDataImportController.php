<?php

namespace App\Http\Controllers\Api;

use DateTime;
use Exception;
use App\Models\SmellyLog;
use App\Models\SmellySensor;
use Illuminate\Http\Request;
use App\Models\HandLotionLog;
use App\Utils\ErrorCodeUtils;
use App\Models\TemperatureLog;
use App\Models\ToiletPaperLog;
use App\Models\HumanTrafficLog;
use App\Models\HandLotionSensor;
use App\Models\TemperatureSensor;
use App\Models\ToiletPaperSensor;
use App\Models\HumanTrafficSensor;
use Illuminate\Support\Facades\DB;
use App\Models\RelativeHumidityLog;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TestDataImportController extends Controller {

    public $log_models;
    public $sensor_models;

    public function __construct() {
        $this->log_models = [
            'toilet_paper' => ToiletPaperLog::class,
            'smelly' => SmellyLog::class,
            'human_traffic' => HumanTrafficLog::class,
            'hand_lotion' => HandLotionLog::class,
            'temperature' => TemperatureLog::class,
            'relative_humidity' => RelativeHumidityLog::class,
        ];

        $this->sensor_models = [
            'toilet_paper' => ToiletPaperSensor::class,
            'smelly' => SmellySensor::class,
            'human_traffic' => HumanTrafficSensor::class,
            'hand_lotion' => HandLotionSensor::class,
            'temperature' => TemperatureSensor::class,
            'relative_humidity' => TemperatureSensor::class,
        ];
    }
    //
    public function importTestData(Request $request) {
        $validated = $request->only([
            'id',
            'data',
            'signature',
            'type'
        ]);

        $validator = Validator::make($validated, [
            'id' => 'required|numeric',
            'data' => 'required|array',
            'data.*.data' => 'required|numeric',
            'data.*.timestamp' => 'required|date_format:Y-m-d H:i:s',
            'signature' => 'required',
            'type' => "required|in:" . implode(',', array_keys($this->log_models))
        ]);
        if ($validator->fails()) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::BAD_REQUEST, null, $validator->errors());
        }
        try {
            if (!$this->validatedSensor($validated['type'], $validated['id'], $validated['signature'])) {
                return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::TOKEN_INVALID);
            }
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }
        try {
            [$dateGrouped, $resp] = $this->arrangeImportData($validated['data']);
            $this->importData($validated['type'], $validated['id'], $dateGrouped, $resp);
        } catch (\Exception $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, $e->getMessage());
        }
        return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS);
    }

    public function importTestDataByFile(Request $request) {
        $validated = $request->only([
            'id',
            'signature',
            'type',
            'file'
        ]);

        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric',
            'signature' => 'required',
            'type' => "required|in:" . implode(',', array_keys($this->log_models)),
            'file' => 'required|mimes:csv,txt',
        ]);

        if ($validator->fails()) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::BAD_REQUEST, null, $validator->errors());
        }
        try {
            if (!$this->validatedSensor($validated['type'], $validated['id'], $validated['signature'])) {
                return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::TOKEN_INVALID);
            }
        } catch (ModelNotFoundException $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::NOT_FOUND);
        }

        if (!$request->hasFile('file')) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, 'File not found');
        }
        $file = $request->file('file');
        $realPath = $file->getRealPath();

        try {
            $reader = IOFactory::createReaderForFile($realPath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($realPath);
            $worksheet = $spreadsheet->getActiveSheet();

            $dataArray = [];
            foreach ($worksheet->getRowIterator() as $index => $row) {
                $rowData = [];
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getValue();
                }
                $dataArray[] = $rowData;
            }
            try {
                [$dateGrouped, $resp] = $this->arrangeImportFileData($dataArray);
                $this->importData($validated['type'], $validated['id'], $dateGrouped, $resp);
            } catch (\Exception $e) {
                return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, $e->getMessage());
            }
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::SUCCESS);
        } catch (\Exception $e) {
            return ErrorCodeUtils::jsonResponse(ErrorCodeUtils::FAILURE, null, $e->getMessage());
        }
    }

    private function runLogAndDailyReportProcess($type, $date) {
        if ($date == date('Y-m-d')) {
            return;
        }
        Artisan::call(sprintf("LogProcess:%s", $type), ['--date' => $date]);
        Artisan::call(sprintf("DailyReportProcess:%s", $type), ['--date' => $date]);
        Artisan::call("DailyReportProcess:Location", ['--date' => $date]);
    }

    private function validatedSensor($type, $id, $signature) {
        $model = $this->sensor_models[$type];
        $sensor = (new $model)->findOrFail($id);
        $key = $sensor->toilet->device_key;
        if ($key !== $signature) {
            return false;
        }
        return true;
    }

    private function arrangeImportFileData($dataArray) {
        $columns = array_shift($dataArray);
        $dateIndex = array_search('Date', $columns);
        $timeIndex = array_search('Time', $columns);
        $dataIndex = array_search('Data', $columns);

        if ($dateIndex === false || $timeIndex === false || $dataIndex === false) {
            throw new Exception("Data Arrange Error");
        }
        $dateGrouped = array_reduce($dataArray, function ($carry, $item) use ($dateIndex) {
            $date = $item[$dateIndex];
            if (!in_array($date, $carry)) {
                $carry[] = $date;
            }
            return $carry;
        }, []);

        $resp = [];
        foreach ($dataArray as $rowData) {
            $resp[] = [
                'raw_data'               => $rowData[$dataIndex],
                'created_at'            => "{$rowData[$dateIndex]} {$rowData[$timeIndex]}"
            ];
        }
        return [$dateGrouped, $resp];
    }

    private function arrangeImportData($dataArray) {
        $dateGrouped = array_reduce($dataArray, function ($carry, $item) {
            $datetime = $item['timestamp'];
            $date = (new DateTime($datetime))->format('Y-m-d');
            if (!in_array($date, $carry)) {
                $carry[] = $date;
            }
            return $carry;
        }, []);

        $resp = [];
        foreach ($dataArray as $rowData) {
            $resp[] = [
                'raw_data'               => $rowData['data'],
                'created_at'            => $rowData['timestamp']
            ];
        }
        return [$dateGrouped, $resp];
    }

    private function importData($type, $sensor_id, $dateGrouped, $dataArray) {
        $now = \Carbon\Carbon::now();
        DB::beginTransaction();
        try {
            ($this->log_models[$type])::insert(array_map(function ($item) use ($type, $sensor_id, $now) {
                return $item + ["{$type}_sensor_id" => $sensor_id, 'updated_at' => $now];
            }, $dataArray));

            foreach ($dateGrouped as $date) {
                $this->runLogAndDailyReportProcess($this->convertToCamelCase($type), $date);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new Exception($e->getMessage());
        }
    }

    private function convertToCamelCase($string) {
        $string = str_replace('_', ' ', $string);
        $string = ucwords($string);
        $string = str_replace(' ', '', $string);
        return $string;
    }
}
