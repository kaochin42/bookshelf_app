<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** ユーザーは書籍をお気に入りに登録できる */
    public function test_user_can_favorite_book(): void
    {
        // 用意する
        $user = User::factory()->create();
        $book = Book::factory()->create();

        // 動かす
        $user->favoriteBooks()->attach($book->id);

        // 確認する
        $this->assertCount(1, $user->favoriteBooks);
        $this->assertTrue($user->favoriteBooks->contains($book->id));
    }
}
