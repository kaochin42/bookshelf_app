<?php

namespace App\Enums;

use Illuminate\Support\Carbon;

enum ReminderTiming: string
{
    case ThreeDaysBefore = 'three_days_before';
    case OnDueDate = 'on_due_date';
    case ThreeDaysAfter = 'three_days_after';

    public function title(): string
    {
        return match ($this) {
            self::ThreeDaysBefore => '読書計画リマインド — 期限まであと 3 日',
            self::OnDueDate => '読書計画 — 本日が期限',
            self::ThreeDaysAfter => '読書計画 — 期限超過 3 日経過',
        };
    }

    public function body(string $bookTitle): string
    {
        return match ($this) {
            self::ThreeDaysBefore => "「{$bookTitle}」の期限まで残り 3 日です。\n引き続き読書を進めましょう。",
            self::OnDueDate => "「{$bookTitle}」は本日が期限です。\n読了済みなら完了登録を、もう少し必要なら期限を変更してください。",
            self::ThreeDaysAfter => "「{$bookTitle}」の期限から 3 日が経過しました。\n読了済みなら完了登録、続けるなら期限を変更してください。",
        };
    }

    public function targetStatus(): ReadingPlanStatus
    {
        return match ($this) {
            self::ThreeDaysBefore, self::OnDueDate => ReadingPlanStatus::InProgress,
            self::ThreeDaysAfter => ReadingPlanStatus::Expired,
        };
    }

    public function targetDate(): Carbon
    {
        return match ($this) {
            self::ThreeDaysBefore => today()->addDays(3),
            self::OnDueDate => today(),
            self::ThreeDaysAfter => today()->subDays(3),
        };
    }
}
