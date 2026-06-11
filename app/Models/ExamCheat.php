<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamCheat extends Model
{
    protected $fillable = [
        'user_id',
        'exam_id',
        'exam_record_id',
        'action',
        'detail',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function examRecord()
    {
        return $this->belongsTo(ExamRecord::class);
    }
}
