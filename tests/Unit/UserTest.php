<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_ユーザーは書籍をお気に入りに登録できる(): void
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
