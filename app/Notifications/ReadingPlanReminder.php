<?php

namespace App\Notifications;

use App\Enums\ReminderTiming;
use App\Models\ReadingPlan;
use Illuminate\Notifications\Notification;

class ReadingPlanReminder extends Notification
{
    public function __construct(
        private readonly ReadingPlan $readingPlan,
        private readonly ReminderTiming $timing,
    ) {}

    /**
     * 配信先（DatabaseChannelのみ）
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * notificationsテーブルのdata列に保存する内容（Bladeが読む3キー）
     *
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'timing' => $this->timing->value,
            'title' => $this->timing->title(),
            'body' => $this->timing->body($this->readingPlan->book->title),
        ];
    }
}
