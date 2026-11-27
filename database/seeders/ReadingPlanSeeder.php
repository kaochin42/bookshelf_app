<?php

namespace Database\Seeders;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReadingPlanSeeder extends Seeder
{
    public function run(): void
    {
        $yamada = User::where('email', 'yamada@example.com')->firstOrFail();
        $suzuki = User::where('email', 'suzuki@example.com')->firstOrFail();
        $books = Book::orderBy('id')->get();

        // 計画1: 期日の3日前リマインダーの確認用
        ReadingPlan::create([
            'user_id' => $yamada->id,
            'book_id' => $books[0]->id,
            'target_date' => Carbon::today()->addDays(3),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // 計画2: 期日当日リマインダーの確認用
        ReadingPlan::create([
            'user_id' => $yamada->id,
            'book_id' => $books[1]->id,
            'target_date' => Carbon::today(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // 計画3: バッチで期限切れに変わる確認用（3日後の通知の対象にもなる）
        ReadingPlan::create([
            'user_id' => $yamada->id,
            'book_id' => $books[2]->id,
            'target_date' => Carbon::today()->subDays(3),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // 計画4: リマインダー対象外の確認用
        ReadingPlan::create([
            'user_id' => $yamada->id,
            'book_id' => $books[3]->id,
            'target_date' => Carbon::today()->addDays(7),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // 計画5: 完了済みの確認用
        ReadingPlan::create([
            'user_id' => $yamada->id,
            'book_id' => $books[4]->id,
            'target_date' => Carbon::today()->subDays(10),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => Carbon::today()->subDays(5),
        ]);

        // 計画6: 他人の計画への403確認用（鈴木花子）
        ReadingPlan::create([
            'user_id' => $suzuki->id,
            'book_id' => $books[5]->id,
            'target_date' => Carbon::today()->addDays(5),
            'status' => ReadingPlanStatus::InProgress,
        ]);
    }
}
