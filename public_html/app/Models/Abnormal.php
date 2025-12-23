<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class Abnormal extends MyModel {
    use HasFactory;

    protected $fillable = [
        'toilet_id',
        'triggerable_id',
        'triggerable_type',
        'created_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y/m/d H:i:s',
        'updated_at' => 'datetime:Y/m/d H:i:s',
        'improved_at' => 'datetime:Y/m/d H:i:s'
    ];

    const TYPE_ABNORMAL = 0;
    const TYPE_IMPROVE = 1;

    public function triggerable()
    {
        return $this->morphTo();
    }
}
