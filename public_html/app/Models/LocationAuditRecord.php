<?php

namespace App\Models;

use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class LocationAuditRecord extends MyModel {
    use HasFactory;

    const STATUS_WAITING = 0;
    const STATUS_ACCEPT = 1;
    const STATUS_REJECT = 2;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y/m/d'
    ];

    /**
     * Get the location
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function location() {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the supplier
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function supplier() {
        return $this->belongsTo(User::class);
    }

    /**
     * search keyword
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param object $searchValue
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $searchValue) {
        if (!empty($searchValue->supplier_id)) {
            $query->where("{$this->getTable()}.supplier_id", '=', $searchValue->supplier_id);
        }
        return $query;
    }

    public function generateToken() {
        $a=0;
        while ($a <= 5) {
            try{
                $this->token = Str::random(32);
                $this->save();
                return $this->token;
            }
            catch(\Exception $e){
                $a++;
            }
        }
        return false;
    }
}
