<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class Action extends MyModel {
    use HasFactory;

    /**
     * The programs that belong to the Action
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function programs() {
        return $this->belongsToMany(Program::class, 'permissions', 'action_id', 'program_id')
            ->withTimestamps()
            ->withPivot('id', 'id');
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
        if (!empty($searchValue->display_name)) {
            $query->where("{$this->getTable()}.display_name", 'like', '%' . escape_str($searchValue->display_name) . '%');
        }
        return $query;
    }
}
