<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewCrudTest extends TestCase
{
    use RefreshDatabase;

    /** レビューを投稿するとDBに保存され、書籍詳細へ遷移する */
    public function test_user_can_store_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(route('reviews.store', $book), [
            'rating' => 4,
            'comment' => 'とても参考になりました。',
        ]);

        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success', 'レビューを投稿しました。');
        $this->assertDatabaseHas('reviews', [
            'book_id' => $book->id,
            'user_id' => $user->id, // 投稿者はログイン中のユーザー
            'rating' => 4,
        ]);
    }

    /** 評価が範囲外（1〜5以外）だと投稿できない */
    public function test_store_review_fails_when_rating_is_out_of_range(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(route('reviews.store', $book), [
            'rating' => 6,
        ]);

        $response->assertSessionHasErrors('rating');
        $this->assertDatabaseCount('reviews', 0);
    }

    /** コメントが空だと投稿できない */
    public function test_store_review_fails_when_comment_is_empty(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(route('reviews.store', $book), [
            'rating' => 4,
        ]);

        $response->assertSessionHasErrors(['comment' => 'コメントは必須です。']);
        $this->assertDatabaseCount('reviews', 0);
    }

    /** ゲストはレビューを投稿できず、ログイン画面へ遷移する */
    public function test_guest_cannot_store_review(): void
    {
        $book = Book::factory()->create();

        $response = $this->post(route('reviews.store', $book), ['rating' => 5]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('reviews', 0);
    }

    /** 投稿者はレビューを更新でき、書籍詳細へ遷移する */
    public function test_owner_can_update_review(): void
    {
        $owner = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $owner->id, 'rating' => 3]);

        $response = $this->actingAs($owner)->put(route('reviews.update', $review), [
            'rating' => 5,
            'comment' => '更新後のコメント',
        ]);

        $response->assertRedirect(route('books.show', $review->book));
        $response->assertSessionHas('success', 'レビューを更新しました。');
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'rating' => 5]);
    }

    /** 他人のレビューは更新できず、内容も変わらない（403） */
    public function test_other_user_cannot_update_review(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create(['rating' => 3]);

        $response = $this->actingAs($user)->put(route('reviews.update', $review), ['rating' => 5, 'comment' => '他人のコメント']);

        $response->assertForbidden();
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'rating' => 3]);
    }

    /** 投稿者がレビューを削除すると、紐付くいいねも削除され、書籍詳細へ遷移する */
    public function test_owner_can_delete_review_with_likes(): void
    {
        $owner = User::factory()->create();
        $liker = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $owner->id]);
        $review->likedByUsers()->attach($liker->id);
        $book = $review->book;

        $response = $this->actingAs($owner)->delete(route('reviews.destroy', $review));

        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success', 'レビューを削除しました。');
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
        $this->assertDatabaseMissing('review_likes', ['review_id' => $review->id]);
    }

    /** 他人のレビューは削除できず、レビューは残る（403） */
    public function test_other_user_cannot_delete_review(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        $this->actingAs($user)->delete(route('reviews.destroy', $review))->assertForbidden();
        $this->assertDatabaseHas('reviews', ['id' => $review->id]);
    }
}
