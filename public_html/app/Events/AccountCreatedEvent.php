<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class AccountCreatedEvent {
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $user;
    public $password;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, $password) {
        $this->user = $user;
        $this->password = $password;
    }
}
