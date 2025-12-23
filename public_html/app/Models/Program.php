<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class Program extends MyModel {
    use HasFactory;

    /**
     * The actions that belong to the Program
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function actions() {
        return $this->belongsToMany(Action::class, 'permissions', 'program_id', 'action_id')
            ->withTimestamps()
            ->withPivot('id', 'id');
    }

    /**
     * 限定 Programs 搜尋範圍
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param object $searchValue
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $searchValue) {
        if (!empty($searchValue->name)) {
            $query->where("{$this->getTable()}.name", 'like', '%' . escape_str($searchValue->name) . '%');
        }
        if (!empty($searchValue->display_name)) {
            $query->where("{$this->getTable()}.display_name", 'like', '%' . escape_str($searchValue->display_name) . '%');
        }
        return $query;
    }
}
