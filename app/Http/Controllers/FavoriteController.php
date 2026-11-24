<?php

namespace App\Http\Controllers;

use App\Models\Book;

class FavoriteController extends Controller
{
    public function index()
    {
        $books = auth()->user()->favoriteBooks()->latest()->paginate(10);

        return view('favorites.index', compact('books'));
    }

    public function toggle(Book $book)
    {
        $result = auth()->user()->favoriteBooks()->toggle($book->id);

        $message = count($result['attached']) > 0
            ? 'お気に入りに追加しました。'
            : 'お気に入りを解除しました。';

        return back()->with('success', $message);
    }
}
