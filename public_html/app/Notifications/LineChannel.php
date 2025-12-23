<?php

namespace App\Notifications;

use App\Notifications\LineNotification;
use Phattarachai\LineNotify\Facade\Line;

class LineChannel
{
    /**
     * Create a new channel instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  App\Notifications\LineNotification  $notification
     * @return void
     */
    public function send($notifiable, LineNotification $notification)
    {
        $message = $notification->toLine($notifiable);

        $token = $notifiable->alert_token;
        if ($token !== null) {
            Line::setToken($token)->send($message);
        }
    }
}
