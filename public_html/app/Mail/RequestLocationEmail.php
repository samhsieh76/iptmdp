<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class RequestLocationEmail extends Mailable {
    use Queueable, SerializesModels;

    public $user;
    public $administrator;
    public $location;
    public $record;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($administrator, $location, $record) {
        $this->user = Auth::user();
        $this->administrator = $administrator;
        $this->location = $location;
        $this->record = $record;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build() {
        return $this->subject(trans('emails.request_subject'))->markdown('emails.request_location', [
            'user' => $this->user,
            'administrator' => $this->administrator,
            'location' => $this->location,
            'record' => $this->record
        ]);
    }
}
