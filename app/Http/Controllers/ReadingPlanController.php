<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\ReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    /** 自分の読書計画一覧。状態で絞り込み可 */
    public function index(Request $request): View
    {
        // 不正な状態値は絞り込みなしとして扱う
        $currentStatus = ReadingPlanStatus::tryFrom((string) $request->query('status'))?->value;

        $readingPlans = $request->user()
            ->readingPlans()
            ->with('book')
            ->when($currentStatus, fn ($query) => $query->where('status', $currentStatus))
            ->orderBy('target_date')
            ->get();

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    /** 作成画面。書籍プルダウンは全書籍を表示 */
    public function create(): View
    {
        $books = Book::orderBy('title')->get();

        return view('reading-plans.create', compact('books'));
    }

    public function store(ReadingPlanRequest $request): RedirectResponse
    {
        $request->user()->readingPlans()->create([
            'book_id' => $request->validated('book_id'),
            'target_date' => $request->validated('target_date'),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        return redirect()->route('reading-plans.index')
            ->with('success', '読書計画を作成しました。');
    }

    /** 編集画面。所有者かつ読了済みでない計画のみ（それ以外は403） */
    public function edit(ReadingPlan $readingPlan): View
    {
        $this->authorize('update', $readingPlan);

        return view('reading-plans.edit', compact('readingPlan'));
    }

    public function update(ReadingPlanRequest $request, ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('update', $readingPlan);

        $readingPlan->target_date = $request->validated('target_date');

        // 期限切れの計画は、今日以降の期日に変更したら進行中に戻す
        if ($readingPlan->status === ReadingPlanStatus::Expired) {
            $readingPlan->status = ReadingPlanStatus::InProgress;
        }

        $readingPlan->save();

        return redirect()->route('reading-plans.index')
            ->with('success', '読書計画を更新しました。');
    }

    public function destroy(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('delete', $readingPlan);

        $readingPlan->delete();

        return redirect()->route('reading-plans.index')
            ->with('success', '読書計画を削除しました。');
    }

    /** 読了にする。期限切れの計画も可 */
    public function complete(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('complete', $readingPlan);

        $readingPlan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => today(),
        ]);

        return redirect()->route('reading-plans.index')
            ->with('success', '読書計画を完了しました。');
    }
}
