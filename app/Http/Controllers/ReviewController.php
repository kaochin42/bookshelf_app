<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Models\Book;
use App\Models\Review;

class ReviewController extends Controller
{
    public function store(ReviewRequest $request, Book $book)
    {
        Review::create([
            'book_id' => $book->id,
            'user_id' => auth()->id(),
            'rating' => $request->validated()['rating'],
            'comment' => $request->validated()['comment'] ?? null,
        ]);

        return redirect()->route('books.show', $book)
            ->with('success', 'レビューを投稿しました。');
    }

    // レビュー編集画面(投稿者のみ)
    public function edit(Review $review)
    {
        $this->authorize('update', $review);

        $review->load('book');

        return view('reviews.edit', compact('review'));
    }

    // レビュー更新(投稿者のみ)
    public function update(ReviewRequest $request, Review $review)
    {
        $this->authorize('update', $review);

        $review->update([
            'rating' => $request->validated()['rating'],
            'comment' => $request->validated()['comment'] ?? null,
        ]);

        return redirect()->route('books.show', $review->book)
            ->with('success', 'レビューを更新しました。');
    }

    // レビュー削除(投稿者のみ)。いいねはcascadeで削除される
    public function destroy(Review $review)
    {
        $this->authorize('delete', $review);

        $book = $review->book;
        $review->delete();

        return redirect()->route('books.show', $book)
            ->with('success', 'レビューを削除しました。');
    }
}
