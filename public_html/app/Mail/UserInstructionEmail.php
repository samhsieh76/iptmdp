<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class UserInstructionEmail extends Mailable {
    use SerializesModels;
    use Queueable;

    public $user;
    public $password;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, $password) {
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build() {
        $message = $this->subject(trans('emails.user_instruction_subject'))
            ->markdown('emails.user_instruction', [
                'user' => $this->user,
                'password' => $this->password
            ]);

        if ($this->user->role->manual) {
            $manualPath = $this->user->role->manual->manual_path;
            if (Storage::exists($manualPath)) {
                $message->attachFromStorage($manualPath, "使用手冊-{$this->user->role->name}.pptx", [
                    'mime' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'
                ]);
            }
        }

        return $message;
    }
}
