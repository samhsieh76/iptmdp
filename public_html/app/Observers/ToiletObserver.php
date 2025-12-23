<?php

namespace App\Observers;

use App\Models\Toilet;
use Illuminate\Support\Str;

class ToiletObserver {
    public function creating(Toilet $toilet) {
        $toilet->device_key = Str::random(16);
    }
}