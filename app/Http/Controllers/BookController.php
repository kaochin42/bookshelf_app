<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->query('keyword');
        $genreId = $request->query('genre');

        $query = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->when(filled($keyword), function ($query) use ($keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('title', 'like', "%{$keyword}%")
                        ->orWhere('author', 'like', "%{$keyword}%");
                });
            })
            ->when(filled($genreId), function ($query) use ($genreId) {
                $query->whereHas('genres', fn ($query) => $query->where('genres.id', $genreId));
            });

        $query = match ($request->query('sort')) {
            'oldest' => $query->oldest()->oldest('id'),
            'title' => $query->orderBy('title')->orderBy('id'),
            'rating' => $query->orderByDesc('reviews_avg_rating')->orderByDesc('id'),
            default => $query->latest()->latest('id'),
        };

        $books = $query->paginate(10)->withQueryString();
        $genres = Genre::all();

        return view('books.index', compact('books', 'genres'));
    }

    public function create()
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    public function store(BookRequest $request)
    {
        $validated = $request->validated();

        $book = Book::create([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'] ?? null,
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
            'user_id' => auth()->id(),
        ]);

        $book->genres()->sync($validated['genres']);

        return redirect()->route('books.show', $book)->with('success', '書籍を登録しました。');
    }

    public function show(Book $book)
    {
        $book->load(['genres', 'reviews.user', 'reviews.likedByUsers']);

        return view('books.show', compact('book'));
    }

    public function edit(Book $book)
    {
        $this->authorize('update', $book);

        $genres = Genre::all();
        $book->load('genres');

        return view('books.edit', compact('book', 'genres'));
    }

    public function update(BookRequest $request, Book $book)
    {
        $this->authorize('update', $book);

        $validated = $request->validated();

        $book->update([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'] ?? null,
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
        ]);

        $book->genres()->sync($validated['genres']);

        return redirect()->route('books.show', $book)->with('success', '書籍情報を更新しました。');
    }

    public function destroy(Book $book)
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()->route('books.index')->with('success', '書籍を削除しました。');
    }
}
