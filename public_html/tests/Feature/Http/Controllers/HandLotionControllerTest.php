<?php

namespace Tests\Feature\Http\Controllers;

use App\Jobs\ProcessHandLotionLog;
use App\Models\HandLotionDailyReport;
use App\Models\HandLotionLog;
use App\Models\LocationSupplier;
use App\Models\Toilet;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class HandLotionControllerTest extends TestCase
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
        $toilet  = Toilet::all()->random();
        $key     = $toilet->device_key;
        $sensors = $toilet->handLotionSensors->random(2);

        $data = [
            [
                'id'   => $sensors[0]->id,
                'data' => $this->faker->randomElement([0, 1]),
            ],
            [
                'id'   => $sensors[1]->id,
                'data' => $this->faker->randomElement([0, 1]),
            ],
        ];
        // $dataStr   = json_encode($data);
        // $signature = hash_hmac('SHA256', $dataStr . $now, $key);

        $input = [
            'data'      => $data,
            'signature' => $key,
        ];

        // action
        $response = $this->postJson('/api/hand_lotion/receive_sensor_data', $input);

        // assertions
        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Success!'
            ]);

        // assertions of data
        foreach ($input['data'] as $data) {
            $log = HandLotionLog
                ::where('hand_lotion_sensor_id', '=', $data['id'])
                ->where('raw_data', '=', $data['data'])
                ->first();

            $this->assertNotNull($log);
        }

        Queue::assertPushedOn('hand_lotion', ProcessHandLotionLog::class);
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
        $toilet  = Toilet::all()->random();
        $key     = $toilet->device_key;
        $sensors = $toilet->handLotionSensors->random(2);

        $data = [
            [
                'id'   => $sensors[0]->id,
                'data' => $this->faker->randomElement([0, 1]),
            ],
            [
                'id'   => $sensors[1]->id,
                'data' => $this->faker->randomElement([0, 1]),
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

        foreach ($sensors as $sensor) {
            $report = HandLotionDailyReport
                ::firstOrNew([
                    'date'                  => $date->format('Y-m-d'),
                    'hand_lotion_sensor_id' => $sensor->id
                ]);

            $report->is_active = 0;

            $report->save();
        }

        // action
        $response = $this->postJson('/api/hand_lotion/receive_sensor_data', $input);

        // assertions
        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Success!'
            ]);

        // assertions of data

        foreach ($input['data'] as $data) {
            $log = HandLotionLog
                ::where('hand_lotion_sensor_id', '=', $data['id'])
                ->where('raw_data', '=', $data['data'])
                ->first();

            $this->assertNotNull($log);

            // recovery from inactive
            $sensor = $log->sensor;

            $report = HandLotionDailyReport
                ::where('hand_lotion_sensor_id', $sensor->id)
                ->where('date', $date->format('Y-m-d'))
                ->first();

            $this->assertEquals(1, $report->is_active);
        }

        Queue::assertPushedOn('hand_lotion', ProcessHandLotionLog::class);
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
        $toilet  = Toilet::all()->random();
        $key     = $toilet->device_key;
        $sensors = $toilet->handLotionSensors->random(2);

        $data = [
            [
                'id'   => $sensors[0]->id,
                'data' => $this->faker->randomElement([0, 1]),
            ],
            [
                'id'   => $sensors[1]->id,
                'data' => $this->faker->randomElement([0, 1]),
            ],
        ];

        $signature = 'ABadSignature';

        $input = [
            'data'      => $data,
            'signature' => $signature,
        ];

        // action
        $response = $this->postJson('/api/hand_lotion/receive_sensor_data', $input);

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
        $response = $this->postJson('/api/hand_lotion/receive_sensor_data', $input);

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
        $toilet  = Toilet::all()->random();
        $key     = $toilet->device_key;
        $sensors = $toilet->handLotionSensors->random(2);

        $data = [
            [
                'id'   => $sensors[0]->id,
                'data' => $this->faker->randomElement([0, 1]),
            ],
            [
                'id'   => $sensors[1]->id,
                'data' => $this->faker->randomElement([0, 1]),
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
        $response = $this->postJson('/api/hand_lotion/receive_sensor_data', $input);

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
