<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\RoleGroup;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        $role_groups = [
            'admin' => [
                'roles' => [
                    ['name' => '系統管理者', 'level' => -1]
                ]
            ],
            'develop' => [
                'roles' => [
                    ['name' => '供應商', 'level' => 0]
                ]
            ],
            'user' => [
                'roles' => [
                    ['name' => '環境部', 'level' => 3],
                    ['name' => '地方環保局', 'level' => 2],
                    ['name' => '場域', 'level' => 1]
                ]
            ]
        ];
        foreach ($role_groups as $key => $element) {
            $role_group = RoleGroup::create([
                'name' => $key
            ]);
            foreach ($element['roles'] as $role) {
                Role::create($role+['group_id' => $role_group->id]);
            }
        }
    }
}
