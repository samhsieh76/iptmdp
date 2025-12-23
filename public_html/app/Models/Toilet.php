<?php

namespace App\Models;

use App\Observers\ToiletObserver;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Toilet extends MyModel {
    use HasFactory;
    use Notifiable;

    const TYPE_MALE = 1;
    const TYPE_FEMALE = 2;
    const TYPE_BARRIER_FREE = 3;
    const TYPE_PARENT_CHILD = 4;

    protected $fillable = [
        'alert_token',
        'notification_start',
        'notification_end',
        'location_id',
        'creator_id',
        'image',
        'device_key',
        'code',
        'name',
        'type',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y/m/d H:i:s'
    ];

    protected static function boot() {
        parent::boot();
        self::observe(ToiletObserver::class);
    }

    /**
     * search keyword
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param object $searchValue
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $searchValue) {
        if (!empty($searchValue->code)) {
            $query->where("{$this->getTable()}.code", 'like', '%' . escape_str($searchValue->code) . '%');
        }
        if (!empty($searchValue->name)) {
            $query->where("{$this->getTable()}.name", 'like', '%' . escape_str($searchValue->name) . '%');
        }
        if (!empty($searchValue->type)) {
            $query->where("{$this->getTable()}.type", '=', $searchValue->type);
        }
        if (!empty($searchValue->location_id)) {
            $query->where("{$this->getTable()}.location_id", '=', $searchValue->location_id);
        }
        return $query;
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function toiletPaperSensors()
    {
        return $this->hasMany(ToiletPaperSensor::class);
    }

    public function smellySensors()
    {
        return $this->hasMany(SmellySensor::class);
    }

    public function humanTrafficSensors()
    {
        return $this->hasMany(HumanTrafficSensor::class);
    }

    public function handLotionSensors()
    {
        return $this->hasMany(HandLotionSensor::class);
    }

    public function temperatureSensors()
    {
        return $this->hasMany(TemperatureSensor::class);
    }
}
