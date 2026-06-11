<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // 一级分类
        $level1 = [
            ['name' => '通识培训', 'sort_order' => 1],
            ['name' => '岗位知识', 'sort_order' => 2],
            ['name' => '通用能力', 'sort_order' => 3],
        ];

        foreach ($level1 as $cat) {
            $parentId = DB::table('categories')->insertGetId(array_merge($cat, [
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]));

            // 二级分类
            $level2 = match ($cat['name']) {
                '通识培训' => ['新员工入职', '企业文化'],
                '岗位知识' => ['产品知识', '销售技巧'],
                '通用能力' => ['沟通协作', '时间管理'],
                default => ['默认分类'],
            };

            foreach ($level2 as $i => $l2Name) {
                DB::table('categories')->insert([
                    'name' => $l2Name,
                    'parent_id' => $parentId,
                    'sort_order' => $i + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
