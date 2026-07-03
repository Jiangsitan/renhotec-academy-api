<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'course_id',
        'type',
        'content',
        'options',
        'correct_answer',
        'score',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'score' => 'decimal:2',
        ];
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * 将 MySQL ENUM 字符串统一转为整数
     * MySQL ENUM('single','multiple','truefalse','short_answer','fill_blank')
     * 前端统一使用整数 1-5
     */
    public function getTypeAttribute($value): int
    {
        return match($value) {
            'single' => 1,
            'multiple' => 2,
            'truefalse' => 3,
            'short_answer' => 4,
            'fill_blank' => 5,
            default => is_numeric($value) ? (int) $value : 0,
        };
    }

    public function getCorrectAnswerAttribute($value): ?string
    {
        // 连接字符集已是 utf8mb4，DB 存储正确 UTF-8，直接返回
        return $value;
    }
}
