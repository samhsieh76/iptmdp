<?php

namespace Tests\Feature\Http\Controllers;

use App\Jobs\ProcessTemperatureLog;
use App\Models\LocationSupplier;
use App\Models\TemperatureDailyReport;
use App\Models\TemperatureLog;
use App\Models\Toilet;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TemperatureControllerTest extends TestCase
{
    use WithFaker;
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed([
            'Database\\Seeders\\BaseSeeder',
            'Database\\Seeders\\DatabaseSeeder',
        ]);
    }

    /**
     * Insert data when receive data from sensors.
     *
     * @return void
     */
    public function test_receive_sensor_data()
    {
        Queue::fake();

        // prepare input
        $toilet = Toilet::all()->random();
        $key    = $toilet->device_key;
        $sensor = $toilet->temperatureSensors->random();

        $data = [
            [
                'id'   => $sensor->id,
                'data' => $this->faker->randomFloat(2, 0.0, 100.0),
            ],
        ];
        // $dataStr   = json_encode($data);
        // $signature = hash_hmac('SHA256', $dataStr . $now, $key);

        $input = [
            'data'      => $data,
            'signature' => $key,
        ];

        // action
        $response = $this->postJson('/api/temperature/receive_sensor_data', $input);

        // assertions
        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Success!'
            ]);

        // assertions of data
        foreach ($input['data'] as $data) {
            $log = TemperatureLog
                ::where('temperature_sensor_id', '=', $data['id'])
                ->where('raw_data', '=', $data['data'])
                ->first();

            $this->assertNotNull($log);
        }

        Queue::assertPushedOn('temperature', ProcessTemperatureLog::class);
    }

    /**
     * Insert data when receive data from sensors.
     * After update, recover dailyReport.is_active status
     *
     * @return void
     */
    public function test_receive_sensor_data_and_recover_active()
    {
        Queue::fake();

        // prepare input
        $toilet = Toilet::all()->random();
        $key    = $toilet->device_key;
        $sensor = $toilet->temperatureSensors->random();

        $data = [
            [
                'id'   => $sensor->id,
                'data' => $this->faker->randomFloat(2, 0.0, 100.0),
            ],
        ];
        // $dataStr   = json_encode($data);
        // $signature = hash_hmac('SHA256', $dataStr . $now, $key);

        $input = [
            'data'      => $data,
            'signature' => $key,
        ];

        // prepare inactive sensor data
        $date = Carbon::now(config('app.timezone'));

        $report = TemperatureDailyReport
            ::firstOrNew([
                'date'                  => $date->format('Y-m-d'),
                'temperature_sensor_id' => $sensor->id
            ]);

        $report->is_active = 0;

        $report->save();

        // action
        $response = $this->postJson('/api/temperature/receive_sensor_data', $input);

        // assertions
        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Success!'
            ]);

        // assertions of data
        foreach ($input['data'] as $data) {
            $log = TemperatureLog
                ::where('temperature_sensor_id', '=', $data['id'])
                ->where('raw_data', '=', $data['data'])
                ->first();

            $this->assertNotNull($log);

            // recovery from inactive
            $sensor = $log->sensor;

            $report = TemperatureDailyReport
                ::where('temperature_sensor_id', $sensor->id)
                ->where('date', $date->format('Y-m-d'))
                ->first();

            $this->assertEquals(1, $report->is_active);
        }

        Queue::assertPushedOn('temperature', ProcessTemperatureLog::class);
    }

    /**
     * Insert data when receive data from sensors.
     * Fail on singature validation.
     *
     * @return void
     */
    public function test_receive_sensor_data_with_invalid_signature()
    {
        Queue::fake();

        // prepare input
        $toilet = Toilet::all()->random();
        $key    = $toilet->device_key;
        $sensor = $toilet->temperatureSensors->random();

        $now     = time();
        $data = [
            [
                'id'   => $sensor->id,
                'data' => $this->faker->randomFloat(2, 0.0, 100.0),
            ],
        ];

        $signature = 'ABadSignature';

        $input = [
            'data'      => $data,
            'signature' => $signature,
        ];

        // action
        $response = $this->postJson('/api/temperature/receive_sensor_data', $input);

        // assertions
        $response
            ->assertStatus(500)
            ->assertJson([
                'message' => 'Oops!',
                'errors'  => 'Invalid Signature.'
            ]);

        Queue::assertNothingPushed();
    }

    /**
     * Insert data when receive data from sensors.
     * Fail on parameter validation.
     *
     * @return void
     */
    public function test_receive_sensor_data_with_validation_error()
    {
        Queue::fake();

        // prepare input
        $input = [
        ];

        // action
        $response = $this->postJson('/api/temperature/receive_sensor_data', $input);

        // assertions
        $response
            ->assertStatus(500)
            ->assertJson([
                'message' => 'Oops!',
            ])
            ->assertJsonValidationErrorFor('data')
            ->assertJsonValidationErrorFor('signature');

        Queue::assertNothingPushed();
    }

    /**
     * Insert data when receive data from sensors.
     * Fail on permission denined.
     *
     * @return void
     */
    public function test_receive_sensor_data_without_permission()
    {
        Queue::fake();

        // prepare input
        $toilet = Toilet::all()->random();
        $key    = $toilet->device_key;
        $sensor = $toilet->temperatureSensors->random();

        $data = [
            [
                'id'   => $sensor->id,
                'data' => $this->faker->randomFloat(2, 0.0, 100.0),
            ],
        ];
        // $dataStr   = json_encode($data);
        // $signature = hash_hmac('SHA256', $dataStr . $now, $key);

        $input = [
            'data'      => $data,
            'signature' => $key,
        ];

        // Set permission to reject
        $permissionRecord = LocationSupplier
            ::where('location_id', $toilet->location_id)
            ->where('supplier_id', $toilet->creator_id)
            ->first();

        $permissionRecord->status = LocationSupplier::STATUS_NO_PERMISSION;
        $permissionRecord->save();

        // action
        $response = $this->postJson('/api/temperature/receive_sensor_data', $input);

        // prepare input
        $toilet = Toilet::all()->random();
        $key    = $toilet->device_key;
        $sensor = $toilet->humanTrafficSensors->random();

        // assertions
        $response
            ->assertStatus(500)
            ->assertJson([
                'message' => 'Oops!',
                'errors'  => 'Invalid Permission.'
            ]);

        Queue::assertNothingPushed();
    }
}
