<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Series extends Model
{
    protected $table = 'series';

    protected $fillable = [
        'name',
        'description',
        'category_id',
        'cover_image',
        'sort_order',
        'status',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class)->orderBy('sort_order');
    }

    public function publishedCourses()
    {
        return $this->hasMany(Course::class)->where('status', 'published')->orderBy('sort_order');
    }
}
