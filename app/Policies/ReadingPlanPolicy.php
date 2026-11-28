<?php

namespace App\Policies;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;

class ReadingPlanPolicy
{
    /** 編集・更新は所有者のみ。読了済みの計画は不可（403） */
    public function update(User $user, ReadingPlan $readingPlan): bool
    {
        return $readingPlan->user_id === $user->id
            && $readingPlan->status !== ReadingPlanStatus::Completed;
    }

    /** 削除は所有者のみ */
    public function delete(User $user, ReadingPlan $readingPlan): bool
    {
        return $readingPlan->user_id === $user->id;
    }

    /** 読了は所有者のみ。期限切れの計画も可 */
    public function complete(User $user, ReadingPlan $readingPlan): bool
    {
        return $readingPlan->user_id === $user->id;
    }
}
