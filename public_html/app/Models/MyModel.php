<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class MyModel extends Model {
    public function serializeDate(DateTimeInterface $date): string {
        return $date->format('Y-m-d H:i:s');
    }
}
