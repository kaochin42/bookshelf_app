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

    /** 書籍は複数のジャンルを持てる */
    public function test_book_has_many_genres(): void
    {
        // 用意する
        $book = Book::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        // 動かす
        $book->genres()->attach($genres->pluck('id'));

        // 確認する
        $this->assertCount(2, $book->genres);
    }

    /** 書籍の平均評価とレビュー件数を算出できる */
    public function test_book_calculates_average_rating_and_review_count(): void
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
