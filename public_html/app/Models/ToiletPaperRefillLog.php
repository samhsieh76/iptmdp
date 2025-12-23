<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class ToiletPaperRefillLog extends MyModel
{
    use HasFactory;

    protected $fillable = [
        'toilet_paper_sensor_id',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y/m/d H:i:s',
        'updated_at' => 'datetime:Y/m/d H:i:s'
    ];
}
