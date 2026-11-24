<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    /** 平均評価が高い順に表示される */
    public function test_ranking_is_ordered_by_average_rating_desc(): void
    {
        $low = Book::factory()->create(['title' => '低評価の本']);
        $high = Book::factory()->create(['title' => '高評価の本']);
        Review::factory()->create(['book_id' => $low->id, 'rating' => 2]);
        Review::factory()->create(['book_id' => $high->id, 'rating' => 5]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $response->assertSeeInOrder(['高評価の本', '低評価の本']);
    }

    /** 平均評価が同点のときは、レビュー件数が多い順に表示される */
    public function test_ranking_tie_is_ordered_by_review_count_desc(): void
    {
        // わざと正しい順（c, a, b）と違う順で用意する
        $bookA = Book::factory()->create(['title' => '本A']);
        $bookB = Book::factory()->create(['title' => '本B']);
        $bookC = Book::factory()->create(['title' => '本C']);
        Review::factory()->count(3)->create(['book_id' => $bookA->id, 'rating' => 3]);
        Review::factory()->count(1)->create(['book_id' => $bookB->id, 'rating' => 3]);
        Review::factory()->count(4)->create(['book_id' => $bookC->id, 'rating' => 3]);

        $response = $this->get(route('ranking.index'));

        $response->assertSeeInOrder(['本C', '本A', '本B']);
    }

    /** レビューがない書籍は表示されない */
    public function test_book_without_reviews_is_not_ranked(): void
    {
        Book::factory()->create(['title' => 'レビューなしの本']);
        $reviewed = Book::factory()->create(['title' => 'レビューありの本']);
        Review::factory()->create(['book_id' => $reviewed->id, 'rating' => 4]);

        $response = $this->get(route('ranking.index'));

        $response->assertSee('レビューありの本');
        $response->assertDontSee('レビューなしの本');
    }

    /** 11冊以上あっても上位10冊までしか表示されない */
    public function test_ranking_shows_only_top10(): void
    {
        Book::factory()->count(11)->create()->each(function (Book $book): void {
            Review::factory()->create(['book_id' => $book->id, 'rating' => 4]);
        });

        $response = $this->get(route('ranking.index'));

        $response->assertViewHas('rankedBooks', fn ($books) => $books->count() === 10);
    }
}
