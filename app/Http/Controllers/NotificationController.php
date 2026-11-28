<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * 通知一覧（認証必須）
     */
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->get();

        return view('notifications.index', compact('notifications'));
    }

    /**
     * 通知を既読にする（認証必須）
     */
    public function read(Request $request, string $id): RedirectResponse
    {
        // 自分の通知だけから探すので、他人の通知IDは404になる
        $notification = $request->user()->notifications()->findOrFail($id);

        $notification->markAsRead();

        return redirect()->route('notifications.index')
            ->with('success', '通知を既読にしました。');
    }
}
