<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
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
}
