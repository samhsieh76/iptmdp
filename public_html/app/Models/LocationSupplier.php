<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class LocationSupplier extends MyModel {
    use HasFactory;

    const STATUS_NO_PERMISSION = 0;
    const STATUS_PERMISSION = 1;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y/m/d'
    ];

    /**
     * Get the supplier
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function supplier() {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the location
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function location() {
        return $this->belongsTo(Location::class);
    }

    public function scopeUserAuthorizedLocations($query, $user_id) {
        return $query
        ->join('locations', 'locations.id', 'location_suppliers.location_id')
        ->select('locations.id', 'locations.name', 'locations.address', 'locations.auth_code')
            ->where('location_suppliers.status', self::STATUS_PERMISSION)
            ->where('location_suppliers.supplier_id', $user_id)
            ->with('location');
    }

    /**
     * search keyword
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param object $searchValue
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $searchValue) {
        if (!empty($searchValue->location)) {
            $query->where("locations.name", 'like', '%' . escape_str($searchValue->location) . '%');
        }
        if (isset($searchValue->status) && $searchValue->status !== null) {
            $query->where("{$this->getTable()}.status", '=', $searchValue->status);
        }
        return $query;
    }

    public function scopeInnerJoinLocation($query) {
        $query->join('locations', function ($join) {
            $join->on("{$this->getTable()}.location_id", '=', 'locations.id');
        });

        return $query;
    }

    public function scopeInnerJoinUser($query) {
        $query->join('users', function ($join) {
            $join->on("{$this->getTable()}.supplier_id", '=', 'users.id');
        });

        return $query;
    }
}
