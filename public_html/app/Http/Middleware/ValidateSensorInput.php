<?php

namespace App\Http\Middleware;

use App\Models\HandLotionSensor;
use App\Models\HumanTrafficSensor;
use App\Models\LocationSupplier;
use App\Models\SmellySensor;
use App\Models\TemperatureSensor;
use App\Models\ToiletPaperSensor;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ValidateSensorInput
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {

            // get sensor by url
            $url = $request->url();

            $validate = $request->validate([
                'data'        => 'required|array',
                'data.*.id'   => 'required',
                'data.*.data' => 'required',
                'signature'   => 'required|string',
            ]);

            $validated = $request->only([
                'data',
                'signature',
            ]);

            if (strpos($url, 'toilet_paper/receive_sensor_data') !== false) {
                $sensor = ToiletPaperSensor::findOrFail($validated['data'][0]['id']);
            } elseif (strpos($url, 'smelly/receive_sensor_data') !== false) {
                $sensor = SmellySensor::findOrFail($validated['data'][0]['id']);
            } elseif (strpos($url, 'human_traffic/receive_sensor_data') !== false) {
                $sensor = HumanTrafficSensor::findOrFail($validated['data'][0]['id']);
            } elseif (strpos($url, 'hand_lotion/receive_sensor_data') !== false) {
                $sensor = HandLotionSensor::findOrFail($validated['data'][0]['id']);
            } elseif (strpos($url, 'temperature/receive_sensor_data') !== false) {
                $sensor = TemperatureSensor::findOrFail($validated['data'][0]['id']);
            } elseif (strpos($url, 'relative_humidity/receive_sensor_data') !== false) {
                $sensor = TemperatureSensor::findOrFail($validated['data'][0]['id']);
            }

            $toilet = $sensor->toilet;

            $key = $toilet->device_key;

            // check signature
            // $dataStr = json_encode($validated['data']);
            // $signature = hash_hmac('SHA256', $dataStr . $validated['timestamp'], $key);
            // if ($signature !== $validate['signature']) {
            if ($key !== $validate['signature']) {
                throw new Exception('Invalid Signature.');
            }

            // check permission
            $userId     = $toilet->creator_id;
            $locationId = $toilet->location_id;

            $permissionRecord = LocationSupplier
                ::where('location_id', '=', $locationId)
                ->where('supplier_id', '=', $userId)
                ->first();

            if (
                is_null($permissionRecord)
                or ($permissionRecord->status != LocationSupplier::STATUS_PERMISSION)
            ) {
                throw new Exception('Invalid Permission.');
            }
        } catch (ValidationException $e) {
            $response = [
                'message' => 'Oops!',
                'errors'  => $e->errors(),
            ];

            return response()->json($response, 500);
        } catch (\Exception $e) {
            $response = [
                'message' => 'Oops!',
                'errors'  => $e->getMessage()
            ];

            return response()->json($response, 500);
        }

        return $next($request);
    }
}
