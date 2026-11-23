<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * 各ユーザーに3〜5冊のお気に入りを登録する(既存の登録は消さない)。
     * 値は書籍の並び順(0始まり)。
     */
    public function run(): void
    {
        $users = User::orderBy('id')->get();
        $books = Book::orderBy('id')->get();

        $favoriteBookIndexes = [
            [0, 2, 5],
            [1, 3, 8, 9],
            [0, 2, 6, 7, 10],
            [4, 5, 9],
            [1, 3, 7, 10],
        ];

        foreach ($users as $userIndex => $user) {
            $user->favoriteBooks()->syncWithoutDetaching(
                $books->only($favoriteBookIndexes[$userIndex])->pluck('id')
            );
        }
    }
}
