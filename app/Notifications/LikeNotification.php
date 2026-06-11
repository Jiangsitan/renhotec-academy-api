<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LikeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Comment $comment,
        public User $liker
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'like',
            'comment_id' => $this->comment->id,
            'series_id' => $this->comment->series_id,
            'series_name' => $this->comment->series->name ?? '',
            'sender_id' => $this->liker->id,
            'sender_name' => $this->liker->name,
            'content' => mb_substr($this->comment->content, 0, 100),
            'message' => $this->liker->name . ' 赞了你的评论',
        ];
    }
}
