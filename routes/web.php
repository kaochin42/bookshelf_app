<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\IsbnSearchController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReadingPlanController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReviewLikeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| ログイン・会員登録・ログアウトは Laravel Fortify が登録する。
*/

// 書籍一覧（トップ）
Route::get('/', [BookController::class, 'index']);

// 認証必須
// books/create が books/{book} に解決されないよう、公開ルートより前に定義する
Route::middleware('auth')->group(function () {
    // 書籍
    Route::resource('books', BookController::class)->except(['index', 'show']);

    // ISBN検索（Google Books API）。書籍の登録・編集画面から呼ばれるため認証必須
    Route::get('/books/isbn/{isbn}', [IsbnSearchController::class, 'show'])->name('books.isbn');

    // ジャンル
    Route::resource('genres', GenreController::class);

    // レビュー
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::resource('reviews', ReviewController::class)->only(['edit', 'update', 'destroy']);
    Route::post('/reviews/{review}/like', [ReviewLikeController::class, 'toggle'])->name('reviews.like');

    // お気に入り
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/books/{book}/favorites', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    // マイ読書レポート
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // 読書計画
    Route::resource('reading-plans', ReadingPlanController::class)->except(['show']);
    Route::post('/reading-plans/{reading_plan}/complete', [ReadingPlanController::class, 'complete'])->name('reading-plans.complete');

    // 通知
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
});

// 公開
Route::resource('books', BookController::class)->only(['index', 'show']);
Route::get('/ranking', [RankingController::class, 'index'])->name('ranking.index');
