<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class County extends MyModel {
    use HasFactory;

    /**
     * Get the region that owns the County
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function region() {
        return $this->belongsTo(Region::class);
    }
}
