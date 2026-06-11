<?php

namespace App\Notifications;

use App\Models\ExamRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExamRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ExamRecord $examRecord,
        public string $reason
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'exam_rejected',
            'exam_id' => $this->examRecord->exam_id,
            'exam_record_id' => $this->examRecord->id,
            'exam_title' => $this->examRecord->exam->title ?? '',
            'reason' => $this->reason,
            'message' => '您的试卷《' . ($this->examRecord->exam->title ?? '') . '》已被驳回，请补充回答后重新提交',
        ];
    }
}
