<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use RefreshDatabase;

    public function test_ジャンルは複数の書籍を持てる(): void
    {
        // 用意する
        $genre = Genre::factory()->create();
        $books = Book::factory()->count(2)->create();

        // 動かす
        $genre->books()->attach($books->pluck('id'));

        // 確認する
        $this->assertCount(2, $genre->books);
    }
}
