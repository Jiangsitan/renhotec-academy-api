<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $seriesMap = DB::table('series')->pluck('id', 'name');
        $mentorId = DB::table('users')->where('employee_no', 'MEN001')->value('id');

        $courses = [
            ['title' => '第1集：欢迎加入 Renhotec', 'series_id' => $seriesMap['公司文化与价值观'], 'type' => 'video', 'content_url' => 'https://www.w3schools.com/html/mov_bbb.mp4', 'duration' => 120, 'sort_order' => 1],
            ['title' => '第2集：我们的团队与文化', 'series_id' => $seriesMap['公司文化与价值观'], 'type' => 'video', 'content_url' => 'https://www.w3schools.com/html/mov_bbb.mp4', 'duration' => 180, 'sort_order' => 2],
            ['title' => '第3集：职业发展路径', 'series_id' => $seriesMap['公司文化与价值观'], 'type' => 'video', 'content_url' => 'https://www.w3schools.com/html/mov_bbb.mp4', 'duration' => 150, 'sort_order' => 3],
            ['title' => '第1集：考勤与假期制度', 'series_id' => $seriesMap['规章制度与合规'], 'type' => 'video', 'content_url' => 'https://www.w3schools.com/html/mov_bbb.mp4', 'duration' => 200, 'sort_order' => 1],
            ['title' => '第2集：信息安全与保密', 'series_id' => $seriesMap['规章制度与合规'], 'type' => 'video', 'content_url' => 'https://www.w3schools.com/html/mov_bbb.mp4', 'duration' => 160, 'sort_order' => 2],
            ['title' => '第1集：核心产品线概览', 'series_id' => $seriesMap['产品知识培训'], 'type' => 'video', 'content_url' => 'https://www.w3schools.com/html/mov_bbb.mp4', 'duration' => 300, 'sort_order' => 1],
            ['title' => '第2集：产品技术架构', 'series_id' => $seriesMap['产品知识培训'], 'type' => 'video', 'content_url' => 'https://www.w3schools.com/html/mov_bbb.mp4', 'duration' => 250, 'sort_order' => 2],
            ['title' => '第1集：客户需求挖掘', 'series_id' => $seriesMap['销售技巧进阶'], 'type' => 'video', 'content_url' => 'https://www.w3schools.com/html/mov_bbb.mp4', 'duration' => 280, 'sort_order' => 1],
            ['title' => '第2集：方案呈现与演示', 'series_id' => $seriesMap['销售技巧进阶'], 'type' => 'video', 'content_url' => 'https://www.w3schools.com/html/mov_bbb.mp4', 'duration' => 240, 'sort_order' => 2],
            ['title' => '第1集：高效沟通基础', 'series_id' => $seriesMap['沟通与协作'], 'type' => 'video', 'content_url' => 'https://www.w3schools.com/html/mov_bbb.mp4', 'duration' => 180, 'sort_order' => 1],
            ['title' => '第2集：团队协作方法论', 'series_id' => $seriesMap['沟通与协作'], 'type' => 'video', 'content_url' => 'https://www.w3schools.com/html/mov_bbb.mp4', 'duration' => 200, 'sort_order' => 2],
            ['title' => '第1集：四象限时间管理法', 'series_id' => $seriesMap['时间管理'], 'type' => 'video', 'content_url' => 'https://www.w3schools.com/html/mov_bbb.mp4', 'duration' => 150, 'sort_order' => 1],
            ['title' => '第2集：GTD 工作流', 'series_id' => $seriesMap['时间管理'], 'type' => 'video', 'content_url' => 'https://www.w3schools.com/html/mov_bbb.mp4', 'duration' => 170, 'sort_order' => 2],
        ];

        foreach ($courses as $course) {
            $series = DB::table('series')->where('id', $course['series_id'])->first();

            $courseId = DB::table('courses')->insertGetId([
                'title' => $course['title'],
                'description' => $course['title'] . ' 的详细内容',
                'category_id' => $series->category_id,
                'series_id' => $course['series_id'],
                'mentor_id' => $mentorId,
                'type' => $course['type'],
                'content_source' => 'online',
                'content_url' => $course['content_url'],
                'duration' => $course['duration'],
                'status' => 'published',
                'sort_order' => $course['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // 为每个视频创建考试
            DB::table('exams')->insert([
                'title' => $course['title'] . ' - 课后测试',
                'course_id' => $courseId,
                'time_limit' => 10,
                'passing_score' => 60,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
