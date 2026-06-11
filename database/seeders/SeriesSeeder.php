<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeriesSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // 获取二级分类
        $catNewEmployee = DB::table('categories')->where('name', '新员工入职')->value('id');
        $catCulture = DB::table('categories')->where('name', '企业文化')->value('id');
        $catProduct = DB::table('categories')->where('name', '产品知识')->value('id');
        $catSales = DB::table('categories')->where('name', '销售技巧')->value('id');
        $catCommunication = DB::table('categories')->where('name', '沟通协作')->value('id');
        $catTime = DB::table('categories')->where('name', '时间管理')->value('id');

        $series = [
            ['name' => '公司文化与价值观', 'description' => '了解公司文化、核心价值观和发展历程', 'category_id' => $catNewEmployee, 'sort_order' => 1],
            ['name' => '规章制度与合规', 'description' => '学习公司各项规章制度和合规要求', 'category_id' => $catCulture, 'sort_order' => 2],
            ['name' => '产品知识培训', 'description' => '全面了解公司核心产品线和竞争优势', 'category_id' => $catProduct, 'sort_order' => 1],
            ['name' => '销售技巧进阶', 'description' => '掌握大客户谈判和销售核心技巧', 'category_id' => $catSales, 'sort_order' => 2],
            ['name' => '沟通与协作', 'description' => '提升职场沟通能力和团队协作方法', 'category_id' => $catCommunication, 'sort_order' => 1],
            ['name' => '时间管理', 'description' => '掌握时间管理工具和方法', 'category_id' => $catTime, 'sort_order' => 2],
        ];

        foreach ($series as $s) {
            DB::table('series')->insert(array_merge($s, [
                'cover_image' => null,
                'status' => 'published',
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }
}
