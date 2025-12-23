<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder {
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        $role = Role::where('name', '=', '系統管理者')->first();
        User::create([
            'role_id'  => $role->id,
            'username' => 'admin',
            'name' => 'SYSCODE',
            'email' => 'info@syscode.com.tw',
            'password' => 'epapassword',
        ]);
    }
}