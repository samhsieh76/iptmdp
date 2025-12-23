<?php

namespace App\Notifications;

use App\Models\Abnormal;
use App\Models\HandLotionSensor;
use App\Models\HumanTrafficSensor;
use App\Models\SmellySensor;
use App\Models\ToiletPaperSensor;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LineNotification extends Notification implements ShouldQueue {
    use Queueable;

    public $type    = '';
    public $dataObj = null;
    public $delayed = false;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($dataObj = null, $type = 'notification', $delayed = false) {
        $this->onConnection('database');
        $this->afterCommit();

        $this->dataObj = $dataObj;
        $this->type    = $type;
        $this->delayed = $delayed;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable) {
        return [LineChannel::class];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable) {
        return [
            //
        ];
    }

    /**
     * Get the line representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toLine($notifiable) {
        /* $area = sprintf(
            "%s%s%s",
            $notifiable->name,
            trans('messages.toilet_type_options')[$notifiable->type],
            $this->dataObj->name
        ); */

        $message = sprintf(
            // "\n%s所在區域\n\n%s %s %s\n\n",
            "\n %s所在區域\n\n %s區域：%s\n %s類型：%s\n %s位置：%s\n\n",
            $this->type == 'notification' ? "提醒您，" : "",
            $this->emoji('1000A7'),
            $notifiable->name ?? '',
            $this->emoji('1000A7'),
            trans('messages.toilet_type_options')[$notifiable->type] ?? '',
            $this->emoji('1000A7'),
            $this->dataObj->name ?? ''
        );

        if ($this->type == 'notification') {
            switch ($this->dataObj->sensorType) {
                case SmellySensor::class:
                    $message .= sprintf(" 目前異味濃度：%d ppb\n 觸發警戒濃度：%d ppb\n\n", $this->dataObj->latest_value, $this->dataObj->max_value);
                    $message .= sprintf(" %s異味濃度已超標，請安排人員前往了解。", $this->emoji('100085'));
                    break;
                case ToiletPaperSensor::class:
                    $message .= sprintf(" %s目前廁紙量已用盡，請安排人員前往補充。", $this->emoji('100085'));
                    break;
                case HandLotionSensor::class:
                    $message .= sprintf(" %s目前洗手液剩餘量不足，請安排人員前往補充。", $this->emoji('100085'));
                    break;
                case HumanTrafficSensor::class:
                    $message .= sprintf(" %s今日人流總量為: %d 人次。", $this->emoji('100035'), $this->dataObj->summary_value);
                    break;
            }
        } else {
            switch ($this->dataObj->sensorType) {
                case SmellySensor::class:
                    $message .= sprintf(" %s廁所異味已處理。", $this->emoji('100033'));
                    break;
                case ToiletPaperSensor::class:
                    $message .= sprintf(" %s廁紙量補充完畢。", $this->emoji('100033'));
                    break;
                case HandLotionSensor::class:
                    $message .= sprintf(" %s洗手液已補充完畢。", $this->emoji('100033'));
                    break;
            }
        }

        return $message;
    }

    /**
     * Determine if the notification should be sent.
     *
     * @param  mixed  $notifiable
     * @param  string  $channel
     * @return bool
     */
    public function shouldSend($notifiable, $channel)
    {
        $triggedAt = Carbon::parse($this->dataObj->trigged_at, config('app.timezone'));
        $start     = Carbon::createFromFormat('H:i:s', $notifiable->notification_start, config('app.timezone'));
        $end       = Carbon::createFromFormat('H:i:s', $notifiable->notification_end, config('app.timezone'));

        $canSend = ($triggedAt->between($start, $end) and $this->dataObj->is_notification);

        if ($this->delayed) {
            // check abnormal exist
            $abnormal = Abnormal
                ::where('triggerable_id', $this->dataObj->sensorId)
                ->where('triggerable_type', $this->dataObj->sensorType)
                ->where('is_improved', '<>', 1)
                ->first();

            // can send if no abnormal
            $canSend = ($canSend and is_null($abnormal));
        }

        return $canSend;
    }

    //Emoji服務功能
    private function emoji($code) {
        $bin = hex2bin(str_repeat('0', 8 - strlen($code)) . $code);
        $emoticon =  mb_convert_encoding($bin, 'UTF-8', 'UTF-32BE');
        return $emoticon;
    }
}
