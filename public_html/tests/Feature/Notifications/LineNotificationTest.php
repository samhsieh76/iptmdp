<?php

namespace Tests\Feature\Notifications;

use App\Models\Abnormal;
use App\Models\County;
use App\Models\HandLotionSensor;
use App\Models\HumanTrafficSensor;
use App\Models\Location;
use App\Models\SmellySensor;
use App\Models\Toilet;
use App\Models\ToiletPaperSensor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Notifications\LineNotification;
use Carbon\Carbon;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use stdClass;
use Tests\TestCase;

class LineNotificationTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected $testToken = 'F6VbvxYGEdpQQd3mkTjbraV7x1hH4P8P4w32nC21qbu';

    public function setUp(): void
    {
        parent::setUp();

        $this->seed([
            'Database\\Seeders\\BaseSeeder',
            // 'Database\\Seeders\\LocationSeeder',
            // 'Database\\Seeders\\ToiletSeeder',
            // 'Database\\Seeders\\SmellySensorSeeder',
            // 'Database\\Seeders\\ToiletPaperSensorSeeder',
            // 'Database\\Seeders\\HandLotionSensorSeeder',
            // 'Database\\Seeders\\HumanTrafficSensorSeeder',
        ]);

        $users    = User::all();
        $counties = County::all();

        $demoData = [
            [
                'location' => '台北車站',
                'toilet' => [
                    'name' => '東三門',
                    'type' => $this->faker->randomElement(range(1, 3)),
                ],
            ],
            [
                'location' => '宜蘭車站',
                'toilet' => [
                    'name' => '客服中心旁',
                    'type' => $this->faker->randomElement(range(1, 3)),
                ],
            ],
            [
                'location' => '日月潭',
                'toilet' => [
                    'name' => '伊達邵碼頭旁',
                    'type' => $this->faker->randomElement(range(1, 3)),
                ],
            ],
            [
                'location' => '台北榮總醫院',
                'toilet' => [
                    'name' => '1F門診旁',
                    'type' => $this->faker->randomElement(range(1, 3)),
                ],
            ]
        ];

        $demoSensors = [
            ToiletPaperSensor::class => [
                '廁紙1',
                'toiletPaper1',
                '廁紙2',
                'toiletPaper2',
            ],
            SmellySensor::class => [
                '氣味1',
                'smelly1',
                '氣味2',
                'smelly2',
            ],
            HandLotionSensor::class => [
                '洗手液1',
                'hand_lotion1',
                '洗手液2',
                'hand_lotion2',
            ],
            HumanTrafficSensor::class => [
                '人流1',
                'human_traffic1',
                '人流2',
                'human_traffic2',
            ],
        ];

        foreach ($demoData as $data) {
            $user   = $users->random();
            $county = $counties->random();

            $location = Location::create([
                'name'              => $data['location'],
                'auth_code'         => $this->faker->regexify('[A-Za-z0-9]{10}'),
                'county_id'         => $county->id,
                'administration_id' => $user->id,
            ]);

            $toilet = Toilet::create([
                'name'        => $data['toilet']['name'],
                'type'        => $data['toilet']['type'],
                'code'        => strtoupper($this->faker->randomLetter()) . $this->faker->randomNumber(9),
                'image'       => $this->faker->imageUrl(),
                'device_key'  => $this->faker->text(50),
                'alert_token' => $this->testToken,
                'location_id' => $location->id,
                'creator_id'  => $user->id,
            ]);

            foreach ($demoSensors as $sensorType => $sensorNames) {
                foreach ($sensorNames as $sensorName) {
                    $sensorType::create([
                        'name' => $sensorName,
                        'toilet_id' => $toilet->id,
                    ]);
                }
            }
        }
    }

    protected function generateNotificationMessage(Toilet $notifiable, $dataObj, string $type = 'notification')
    {
        $message = sprintf("\n貼心提醒您\n\n");

        $message .= sprintf(
            "%s-%s-%s-%s\n\n",
            $notifiable->location->name,
            $notifiable->name,
            trans('messages.toilet_type_options')[$notifiable->type],
            $dataObj->name
        );

        if ($type == 'notification') {
            if ($dataObj instanceof SmellySensor) {
                $message .= sprintf("🐽氣味-氣味已達 %d ppb，請安排人員前往處理。\n", $dataObj->latest_value);
            } elseif ($dataObj instanceof ToiletPaperSensor) {
                $message .= sprintf("🧻廁紙-廁紙剩餘量不足，請安排人員前往補充。\n");
            } elseif ($dataObj instanceof HandLotionSensor) {
                $message .= sprintf("🧴洗手液- 洗手液剩餘量不足，請安排人員前往補充。\n");
            } elseif ($dataObj instanceof HumanTrafficSensor) {
                $message .= sprintf("🚶人流-目前已累計 %d 人。\n", $dataObj->summary_value);
            }
        } else {
            if ($dataObj instanceof SmellySensor) {
                $message .= sprintf("🐽⏫氣味-廁所異味已處理。\n");
            } elseif ($dataObj instanceof ToiletPaperSensor) {
                $message .= sprintf("🧻💯廁紙-廁紙已補充完畢。\n");
            } elseif ($dataObj instanceof HandLotionSensor) {
                $message .= sprintf("🧴👍洗手液-洗手液已補充完畢。\n");
            }
        }

        return $message;
    }

    /**
     * provide data for test message in different sensor and event type
     * sensors: smelly, toilet_paper, hand_lotion, human_traffic
     * events: notification, improvement
     */
    public function notificationDataProvider()
    {
        return [
            ['smellySensors', 'notification'],
            ['smellySensors', 'improvement'],
            ['toiletPaperSensors', 'notification'],
            ['toiletPaperSensors', 'improvement'],
            ['handLotionSensors', 'notification'],
            ['handLotionSensors', 'improvement'],
            ['humanTrafficSensors', 'notification'],
        ];
    }

    /**
     * Test notifictaion message
     * @dataProvider notificationDataProvider
     */
    public function testQueued($sensorName, $type)
    {
        Queue::fake();

        // prepare
        $toilet = Toilet::with(['location', $sensorName])->get()->random();
        $toilet->update([
            'alert_token'        => $this->testToken,
            'notification_start' => '00:00:00',
            'notification_end'   => '18:00:00',
        ]);

        $sensor = $toilet->$sensorName->random();

        $dataObj = new stdClass();
        $dataObj->name            = $sensor->name;
        $dataObj->is_notification = $sensor->is_notification;
        $dataObj->sensorType      = get_class($sensor);
        $dataObj->latest_value    = 30;
        $dataObj->summary_value   = 100;
        $dataObj->trigged_at      = '18:00:00';

        // actions
        $toilet->notify((new LineNotification($dataObj, $type)));

        // expect
        $message = $this->generateNotificationMessage($toilet, $sensor, $type);

        // Message still under modification, remove from assertion temporarily
        Queue::assertPushed(SendQueuedNotifications::class);
    }

    /**
     * Test notifictaion send
     * @dataProvider notificationDataProvider
     */
    public function testSend($sensorName, $type)
    {
        Notification::fake();

        // prepare
        $toilet = Toilet::with([$sensorName])->get()->random();
        $toilet->update([
            'alert_token'        => $this->testToken,
            'notification_start' => '00:00:00',
            'notification_end'   => '18:00:00',
        ]);

        $sensor = $toilet->$sensorName->random();

        $dataObj = new stdClass();
        $dataObj->name            = $sensor->name;
        $dataObj->is_notification = $sensor->is_notification;
        $dataObj->sensorType      = get_class($toilet->$sensorName->random());
        $dataObj->latest_value    = 30;
        $dataObj->summary_value   = 100;
        $dataObj->trigged_at      = '18:00:00';

        // actions
        $toilet->notifyNow((new LineNotification($dataObj, $type)));

        // expect
        Notification::assertSentTo($toilet, LineNotification::class);
    }

    /**
     * Test notifictaion message
     * Will not send when not in timeframe
     * @dataProvider notificationDataProvider
     */
    public function testNoSendOutOfTimeframe($sensorName, $type)
    {
        Notification::fake();

        // prepare
        $toilet = Toilet::with(['location', $sensorName])->get()->random();
        $toilet->update([
            'alert_token'        => $this->testToken,
            'notification_start' => '00:00:00',
            'notification_end'   => '18:00:00',
        ]);

        $sensor = $toilet->$sensorName->random();

        $dataObj = new stdClass();
        $dataObj->name            = $sensor->name;
        $dataObj->is_notification = $sensor->is_notification;
        $dataObj->sensorType      = get_class($sensor);
        $dataObj->latest_value    = 30;
        $dataObj->summary_value   = 100;
        $dataObj->trigged_at      = '18:00:01';

        // actions
        $toilet->notifyNow((new LineNotification($dataObj, $type)));

        // expect
        $message = $this->generateNotificationMessage($toilet, $sensor, $type);

        // Message still under modification, remove from assertion temporarily
        Notification::assertNotSentTo($toilet, LineNotification::class);
    }

    /**
     * Test notifictaion message
     * Will not send when sensor.is_notification = 0
     * @dataProvider notificationDataProvider
     */
    public function testNoSendWhenNoIsNotification($sensorName, $type)
    {
        Notification::fake();

        // prepare
        $toilet = Toilet::with(['location', $sensorName])->get()->random();
        $toilet->update([
            'alert_token'        => $this->testToken,
            'notification_start' => '00:00:00',
            'notification_end'   => '23:59:59',
        ]);

        $sensor = $toilet->$sensorName->random();

        $dataObj = new stdClass();
        $dataObj->name            = $sensor->name;
        $dataObj->is_notification = 0;
        $dataObj->sensorType      = get_class($sensor);
        $dataObj->latest_value    = 30;
        $dataObj->summary_value   = 100;
        $dataObj->trigged_at      = '18:00:00';

        // actions
        $toilet->notifyNow((new LineNotification($dataObj, $type)));

        // expect
        $message = $this->generateNotificationMessage($toilet, $sensor, $type);

        // Message still under modification, remove from assertion temporarily
        Notification::assertNotSentTo($toilet, LineNotification::class);
    }

    /**
     * Test notifictaion message
     *
     *
     * @dataProvider notificationDataProvider
     */
    public function testDelayedQueued($sensorName, $type)
    {
        Queue::fake();

        // prepare
        $toilet = Toilet::with(['location', $sensorName])->get()->random();
        $toilet->update([
            'alert_token'        => $this->testToken,
            'notification_start' => '00:00:00',
            'notification_end'   => '18:00:00',
        ]);

        $sensor = $toilet->$sensorName->random();

        $dataObj = new stdClass();
        $dataObj->name            = $sensor->name;
        $dataObj->is_notification = $sensor->is_notification;
        $dataObj->sensorType      = get_class($sensor);
        $dataObj->sensorId        = $sensor->id;
        $dataObj->latest_value    = 30;
        $dataObj->summary_value   = 100;
        $dataObj->trigged_at      = '18:00:00';

        // actions
        $delayed = true;
        $toilet->notify((new LineNotification($dataObj, $type, $delayed))->delay(1000));

        // expect
        $message = $this->generateNotificationMessage($toilet, $sensor, $type);

        // Message still under modification, remove from assertion temporarily
        Queue::assertPushed(SendQueuedNotifications::class, function ($job) {
            $check = (
                $job->notification->delayed == true
                and $job->notification->delay > 0
            );

            return $check;
        });
    }

    /**
     * Test notifictaion message
     * Will not send when sensor.is_notification = 0
     * @dataProvider notificationDataProvider
     */
    public function testDelayedNoSend($sensorName, $type)
    {
        Notification::fake();

        // prepare
        $toilet = Toilet::with(['location', $sensorName])->get()->random();
        $toilet->update([
            'alert_token'        => $this->testToken,
            'notification_start' => '00:00:00',
            'notification_end'   => '23:59:59',
        ]);

        $sensor = $toilet->$sensorName->random();

        $dataObj = new stdClass();
        $dataObj->name            = $sensor->name;
        $dataObj->is_notification = 1;
        $dataObj->sensorType      = get_class($sensor);
        $dataObj->sensorId        = $sensor->id;
        $dataObj->latest_value    = 30;
        $dataObj->summary_value   = 100;
        $dataObj->trigged_at      = '18:00:00';

        Abnormal::create([
            'toilet_id'        => $toilet->id,
            'triggerable_id'   => $sensor->id,
            'triggerable_type' => get_class($sensor),
            'created_at'       => Carbon::now(config('app.timezone')),
        ]);

        // actions
        $delayed = true;
        $toilet->notifyNow((new LineNotification($dataObj, $type, $delayed)));

        // expect
        $message = $this->generateNotificationMessage($toilet, $sensor, $type);

        // Message still under modification, remove from assertion temporarily
        Notification::assertNotSentTo($toilet, LineNotification::class);
    }
}