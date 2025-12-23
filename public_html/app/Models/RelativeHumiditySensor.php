<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class RelativeHumiditySensor extends MyModel {
    use HasFactory;

    protected $fillable = [
        'toilet_id'
    ];

    /**
     * search keyword
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param object $searchValue
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $searchValue) {
        if (!empty($searchValue->toilet_id)) {
            $query->where("{$this->getTable()}.toilet_id", '=', $searchValue->toilet_id);
        }
        return $query;
    }

    public function toilet() {
        return $this->belongsTo(Toilet::class);
    }

    public function logs() {
        return $this->hasMany(RelativeHumidityLog::class);
    }
}
