<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable {
    use HasFactory, Notifiable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
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
    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime:Y/m/d H:i:s'
    ];

    /**
     * 對密碼加密
     *
     * @param string $password
     * @return void
     */
    public function setPasswordAttribute($password) {
        $this->attributes['password'] = bcrypt($password);
    }


    /**
     * Get the role that owns the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role() {
        return $this->belongsTo(Role::class);
    }

    public function children() {
        return $this->hasMany(User::class, 'parent_id');
    }

    public function locations() {
        return $this->hasMany(Location::class, 'administration_id');
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

    /**
     * 是否是Super User
     *
     * @return boolean
     */
    public function isSuperUser() {
        return $this->id == 1;
    }

    /**
     * 限定 users 條件
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param boolean $withTrashed
     * @return * @param  \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCondition($query, $withTrashed) {
        if ($withTrashed) {
            $query->withTrashed();
        }
        $query->where('users.id', '!=', 1);
        return $query;
    }

    public function scopeInnerJoinRole($query) {
        $query->join('roles', function ($join) {
            $join->on("{$this->getTable()}.role_id", '=', 'roles.id');
        });

        return $query;
    }

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
        if (!empty($searchValue->username)) {
            $query->where("{$this->getTable()}.username", 'like', '%' . escape_str($searchValue->username) . '%');
        }
        if (!empty($searchValue->role_id)) {
            $query->where("{$this->getTable()}.role_id", '=', $searchValue->role_id);
        }
        return $query;
    }

    public function scopeAvailableParent($query, $role) {
        $query->join('roles', function ($join) use ($role) {
            $join->on('roles.id', '=', 'users.role_id')
                ->where('roles.group_id', '=', $role->group_id)
                ->where('roles.level', '=', $role->level + 1);
        });
        return $query;
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

    public function serializeDate(DateTimeInterface $date): string {
        return $date->format('Y-m-d H:i:s');
    }
}
