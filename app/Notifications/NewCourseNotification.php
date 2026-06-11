<?php

namespace App\Notifications;

use App\Models\Course;
use App\Models\Series;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewCourseNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Course $course,
        public Series $series
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_course',
            'course_id' => $this->course->id,
            'course_title' => $this->course->title,
            'series_id' => $this->series->id,
            'series_name' => $this->series->name,
            'message' => '系列《' . $this->series->name . '》发布了新课程《' . $this->course->title . '》',
        ];
    }
}
