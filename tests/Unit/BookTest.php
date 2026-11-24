<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    public function test_書籍は複数のジャンルを持てる(): void
    {
        // 用意する
        $book = Book::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        // 動かす
        $book->genres()->attach($genres->pluck('id'));

        // 確認する
        $this->assertCount(2, $book->genres);
    }

    public function test_書籍の平均評価とレビュー件数を算出できる(): void
    {
        // 用意する
        $book = Book::factory()->create();
        Review::factory()->create(['book_id' => $book->id, 'rating' => 5]);
        Review::factory()->create(['book_id' => $book->id, 'rating' => 1]);

        // 動かす
        $result = Book::withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->find($book->id);

        // 確認する
        $this->assertEquals(3, $result->reviews_avg_rating);
        $this->assertSame(2, $result->reviews_count);
    }
}
