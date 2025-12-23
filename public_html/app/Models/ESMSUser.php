<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class ESMSUser extends Authenticatable {
    protected $primaryKey = 'name';
    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'auth_level', 'county_id', 'avatar'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * 是否是Super User
     *
     * @return boolean
     */
    public function isSuperUser() {
        return false;
    }

    /**
     * 檢查 user 權限
     *
     * @param int $permission_id
     * @return boolean
     */
    public function HasPermission($permission_id) {
        $role = $this->role;
        if ($role != null && $role->HasPermission($permission_id)) return true;
        return false;
    }

    public function getRoleAttribute() {
        if ($this->auth_level == config('esms.auth_levels.all_areas')) {
            return Role::where('level', 3)->first();
        }
        return Role::where('level', 2)->first();
    }

    public function manageableSensors() {
        $manageable_sensors = [];
        $sensors = [
            'toilet_paper',
            'smelly',
            'human_traffic',
            'hand_lotion',
            'temperature',
            'relative_humidity'
        ];
        foreach ($sensors as $sensor) {
            if ($this->can("{$sensor}_sensors.index")) {
                array_push($manageable_sensors, $sensor);
            }
        }
        return $manageable_sensors;
    }
}
