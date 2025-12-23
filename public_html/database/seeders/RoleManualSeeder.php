<?php

namespace Database\Seeders;

use App\Models\RoleManual;
use Illuminate\Database\Seeder;

class RoleManualSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $manuals = [
            [
                'role_id' => 1,
                'manual_path' => 'public/files/使用手冊-系統管理員.pptx'
            ],
            [
                'role_id' => 2,
                'manual_path' => 'public/files/使用手冊-供應商.pptx'
            ],
            [
                'role_id' => 3,
                'manual_path' => 'public/files/使用手冊-環境部.pptx'
            ],
            [
                'role_id' => 4,
                'manual_path' => 'public/files/使用手冊-地方環保局.pptx'
            ],
            [
                'role_id' => 5,
                'manual_path' => 'public/files/使用手冊-場域.pptx'
            ]
        ];
        foreach ($manuals as $element) {
            RoleManual::create($element);
        }
    }
}
