<?php

namespace App\Models;

use App\Enums\CourseType;
use App\Services\FileConvertService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Course extends Model
{
    protected $fillable = [
        'title',
        'description',
        'category_id',
        'series_id',
        'mentor_id',
        'type',
        'content_source',
        'content_url',
        'cover_image',
        'file_name',
        'file_size',
        'min_read_time',
        'duration',
        'status',
        'sort_order',
    ];

    protected $appends = ['preview_url'];

    protected function casts(): array
    {
        return [
            'type' => CourseType::class,
            'min_read_time' => 'integer',
            'duration' => 'integer',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function series()
    {
        return $this->belongsTo(Series::class);
    }

    public function mentor()
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function learningProgress()
    {
        return $this->hasMany(LearningProgress::class);
    }

    public function exams()
    {
        return $this->belongsToMany(Exam::class, 'exam_courses');
    }

    public function exam()
    {
        return $this->belongsToMany(Exam::class, 'exam_courses')->limit(1);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    /**
     * 获取预览 URL（PPT 自动使用转码后的 PDF）
     */
    public function getPreviewUrlAttribute(): ?string
    {
        if (!$this->content_url || $this->content_source !== 'local') {
            return null;
        }

        // 从 content_url 提取相对路径
        $contentUrl = $this->content_url;
        $relativePath = null;

        if (str_contains($contentUrl, '/storage/')) {
            $relativePath = explode('/storage/', $contentUrl)[1] ?? null;
        }

        if (!$relativePath) {
            return null;
        }

        $previewPath = FileConvertService::getPreviewPath($relativePath);

        if ($previewPath && Storage::disk('public')->exists($previewPath)) {
            return '/storage/' . $previewPath;
        }

        return null;
    }
}
