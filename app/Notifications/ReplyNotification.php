<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReplyNotification extends Notification
{
    use Queueable;

    public function __construct(public Comment $comment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'reply',
            'comment_id' => $this->comment->id,
            'parent_id' => $this->comment->parent_id,
            'series_id' => $this->comment->series_id,
            'series_name' => $this->comment->series->name ?? '',
            'sender_id' => $this->comment->user_id,
            'sender_name' => $this->comment->user->name ?? '',
            'content' => mb_substr($this->comment->content, 0, 100),
            'message' => ($this->comment->user->name ?? '用户') . ' 回复了你的评论',
        ];
    }
}
