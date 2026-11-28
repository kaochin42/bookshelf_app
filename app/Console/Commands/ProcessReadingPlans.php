<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Enums\ReminderTiming;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Console\Command;

class ProcessReadingPlans extends Command
{
    protected $signature = 'reading-plans:process';

    protected $description = '期日を過ぎた読書計画を期限切れにし、リマインダー通知を送る';

    public function handle(): int
    {
        // 順番が重要: 期限切れ化 → 通知（3日後通知は期限切れが対象のため）
        $expiredCount = $this->expireOverduePlans();
        $notifiedCount = $this->sendReminders();

        $this->info("期限切れにした計画: {$expiredCount}件");
        $this->info("送信したリマインダー: {$notifiedCount}件");

        return self::SUCCESS;
    }

    /**
     * 期日が今日より前の進行中の計画を期限切れにする
     */
    private function expireOverduePlans(): int
    {
        return ReadingPlan::where('status', ReadingPlanStatus::InProgress)
            ->whereDate('target_date', '<', today())
            ->update(['status' => ReadingPlanStatus::Expired]);
    }

    /**
     * 3種類のリマインダーを、対象の計画の持ち主に送る
     */
    private function sendReminders(): int
    {
        return collect(ReminderTiming::cases())->sum(function (ReminderTiming $timing): int {
            $plans = ReadingPlan::with(['user', 'book'])
                ->where('status', $timing->targetStatus())
                ->whereDate('target_date', $timing->targetDate())
                ->get();

            $plans->each(fn (ReadingPlan $plan) => $plan->user->notify(
                new ReadingPlanReminder($plan, $timing)
            ));

            return $plans->count();
        });
    }
}
