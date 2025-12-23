<?php

namespace App\Listeners;

use App\Mail\UserInstructionEmail;
use App\Events\AccountCreatedEvent;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendUserInstructionEmail {
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct() {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(AccountCreatedEvent $event) {
        Mail::to($event->user->email)->queue(
            (new UserInstructionEmail($event->user, $event->password))->onQueue('emails')
        );
    }
}
