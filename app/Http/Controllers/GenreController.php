<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenreRequest;
use App\Models\Genre;

class GenreController extends Controller
{
    // ジャンル一覧（書籍数付き）
    public function index()
    {
        $genres = Genre::withCount('books')->get();

        return view('genres.index', compact('genres'));
    }

    // ジャンル登録画面
    public function create()
    {
        return view('genres.create');
    }

    // ジャンル詳細（紐づく書籍を10件/ページ）
    public function show(Genre $genre)
    {
        $books = $genre->books()->with('genres')->latest()->paginate(10);

        return view('genres.show', compact('genre', 'books'));
    }

    // ジャンル登録
    public function store(GenreRequest $request)
    {
        Genre::create($request->validated());

        return redirect()->route('genres.index')->with('success', 'ジャンルを作成しました。');
    }

    // ジャンル編集画面
    public function edit(Genre $genre)
    {
        return view('genres.edit', compact('genre'));
    }

    // ジャンル更新
    public function update(GenreRequest $request, Genre $genre)
    {
        $genre->update($request->validated());

        return redirect()->route('genres.index')->with('success', 'ジャンルを更新しました。');
    }

    // ジャンル削除（書籍が紐付いている場合は拒否）
    public function destroy(Genre $genre)
    {
        if ($genre->books()->exists()) {
            return redirect()->route('genres.index')
                ->with('error', 'このジャンルには書籍が紐付いているため削除できません。');
        }

        $genre->delete();

        return redirect()->route('genres.index')->with('success', 'ジャンルを削除しました。');
    }
}
