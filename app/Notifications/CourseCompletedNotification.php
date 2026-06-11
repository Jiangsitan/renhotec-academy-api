<?php

namespace App\Notifications;

use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CourseCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Course $course
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'course_completed',
            'course_id' => $this->course->id,
            'course_title' => $this->course->title,
            'series_id' => $this->course->series_id,
            'message' => '您已完成课程《' . $this->course->title . '》，可以参加考试了',
        ];
    }
}
