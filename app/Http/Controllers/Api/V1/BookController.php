<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BookIndexRequest;
use App\Http\Requests\Api\V1\BookStoreRequest;
use App\Http\Requests\Api\V1\BookUpdateRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class BookController extends Controller
{
    /**
     * 書籍一覧を取得する（キーワード・ジャンル絞り込み、ページネーション対応）
     */
    public function index(BookIndexRequest $request)
    {
        $validated = $request->validated();

        $books = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when($validated['keyword'] ?? null, function ($query, $keyword) {
                // title / author の部分一致（OR 条件はまとめて括る）
                $query->where(function ($query) use ($keyword) {
                    $query->where('title', 'like', "%{$keyword}%")
                        ->orWhere('author', 'like', "%{$keyword}%");
                });
            })
            ->when($validated['genre_id'] ?? null, function ($query, $genreId) {
                $query->whereHas('genres', fn ($query) => $query->where('genres.id', $genreId));
            })
            ->latest()
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return BookResource::collection($books);
    }

    /**
     * 書籍を登録する
     */
    public function store(BookStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $book = Book::create(collect($validated)->except('genres')->all());
        $book->genres()->sync($validated['genres']);

        $book->load('genres')->loadAvg('reviews', 'rating')->loadCount('reviews');

        return (new BookResource($book))->response()->setStatusCode(201);
    }

    /**
     * 書籍詳細を取得する
     */
    public function show(Book $book)
    {
        $book->load(['genres', 'reviews.user'])
            ->loadAvg('reviews', 'rating')
            ->loadCount('reviews');

        return new BookResource($book);
    }

    /**
     * 書籍を更新する（基本機能は認証なしのため、登録者もリクエストの値で更新する）
     */
    public function update(BookUpdateRequest $request, Book $book): BookResource
    {
        $validated = $request->validated();

        $book->update(collect($validated)->except('genres')->all());
        $book->genres()->sync($validated['genres']);

        $book->load('genres')->loadAvg('reviews', 'rating')->loadCount('reviews');

        return new BookResource($book);
    }

    /**
     * 書籍を削除する（関連データはcascadeで削除）
     */
    public function destroy(Book $book): Response
    {
        $book->delete();

        return response()->noContent();
    }
}
