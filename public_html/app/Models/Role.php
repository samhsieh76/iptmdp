<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class Role extends MyModel {
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    /**
     * The permissions that belong to the Role
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions() {
        return $this->belongsToMany(Permission::class, 'role_permission', 'role_id', 'permission_id')->withTimestamps();
    }

    /**
     * 檢查role權限
     *
     * @param int $permission_id
     * @return boolean
     */
    public function HasPermission($permission_id) {
        return in_array($permission_id, $this->permissions->pluck('id')->toArray());
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
        return $query;
    }

    public function manual() {
        return $this->hasOne(RoleManual::class);
    }
}
