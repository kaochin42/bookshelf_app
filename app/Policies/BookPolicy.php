<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    /** 書籍の編集は作成者のみ */
    public function update(User $user, Book $book): bool
    {
        return $book->user_id === $user->id;
    }

    /** 書籍の削除は作成者のみ */
    public function delete(User $user, Book $book): bool
    {
        return $book->user_id === $user->id;
    }
}
