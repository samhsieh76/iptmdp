<?php

namespace App\Models;

use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Location extends MyModel {
    use HasFactory;

    protected $fillable = [
        'name',
        'auth_code',
        'county_id',
        'administration_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y/m/d H:i:s'
    ];

    /**
     * search keyword
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param object $searchValue
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $searchValue) {
        if (!empty($searchValue->name)) {
            $query->where("{$this->getTable()}.name", 'like', '%' . escape_str($searchValue->name) . '%');
        }
        if (!empty($searchValue->county_id)) {
            $query->where("{$this->getTable()}.county_id", '=', $searchValue->county_id);
        }
        if (!empty($searchValue->address)) {
            $query->where("{$this->getTable()}.address", 'like', '%' . escape_str($searchValue->address) . '%');
        }
        return $query;
    }

    public function scopeInnerJoinCounty($query) {
        $query->join('counties', function ($join) {
            $join->on("{$this->getTable()}.county_id", '=', 'counties.id');
        });

        return $query;
    }

    /**
     * Get the administrator that owns the Location
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function administrator() {
        return $this->belongsTo(User::class, 'administration_id');
    }

    public function generateAuthCode() {
        $a = 0;
        while ($a <= 5) {
            try {
                $this->auth_code = Str::random(10);
                $this->save();
                return $this->auth_code;
            } catch (\Exception $e) {
                $a++;
            }
        }
        return false;
    }

    /**
     * Get the county that owns the Location
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function county() {
        return $this->belongsTo(County::class, 'county_id')->with([
            'region' => function ($query) {
                $query->select('id', 'name');
            }
        ]);
    }

    public function scopeUserAuthorized($query, $user_id) {
        return $query->join('location_suppliers', function ($join) use ($user_id) {
            $join->on('locations.id', 'location_suppliers.location_id')
                ->where('supplier_id', '=', $user_id)
                ->where('location_suppliers.status', LocationSupplier::STATUS_PERMISSION);
        })->select('locations.id', 'locations.county_id', 'locations.name', 'locations.address', 'locations.image', 'locations.business_hours', 'locations.administration_id');
    }

    /**
     * Get all of the toilets for the Location
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function toilets() {
        return $this->hasMany(Toilet::class);
    }

    public function dailyReports()
    {
        return $this->hasMany(LocationDailyReport::class);
    }
}
