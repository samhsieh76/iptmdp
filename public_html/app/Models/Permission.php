<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;


class Permission extends MyModel {
    use HasFactory;

    protected $fillable = [
        'program_id', 'action_id'
    ];

    /**
     * The roles that belong to the Permission
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles() {
        return $this->belongsToMany(Role::class, 'role_permission', 'permission_id', 'role_id')->withTimestamps();
    }

    /**
     * Get the program that owns the Permission
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function program() {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the action that owns the Permission
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function action() {
        return $this->belongsTo(Action::class);
    }

    /**
     * Get the permission's display name.
     *
     * @return string
     */
    public function getDisplayNameAttribute() {
        return $this->program->display_name . '/' . $this->action->display_name;
    }

    /**
     * Get the permission's name.
     *
     * @return string
     */
    public function getNameAttribute() {
        return $this->program->name . '.' . $this->action->name;
    }

    public function scopePermissionByName($query, $program, $action) {
        $program = Program::where('name', '=', $program)->first();
        $action = Action::where('name', '=', $action)->first();
        if ($program && $action) {
            $query->where('program_id', '=', $program->id)->where('action_id', '=', $action->id);
            return $query->first();
        }
        return $query->whereNull('id')->first();
    }
}
