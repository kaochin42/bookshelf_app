<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikeTest extends TestCase
{
    use RefreshDatabase;

    /** いいねは追加→解除→再追加のトグルになる */
    public function test_like_is_toggled(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();
        $detailUrl = route('books.show', $review->book);

        // 追加
        $response = $this->actingAs($user)->from($detailUrl)->post(route('reviews.like', $review));
        $response->assertRedirect($detailUrl);
        $response->assertSessionHas('success', 'いいねしました。');
        $this->assertDatabaseHas('review_likes', ['user_id' => $user->id, 'review_id' => $review->id]);

        // 解除
        $response = $this->actingAs($user)->from($detailUrl)->post(route('reviews.like', $review));
        $response->assertSessionHas('success', 'いいねを取り消しました。');
        $this->assertDatabaseMissing('review_likes', ['user_id' => $user->id, 'review_id' => $review->id]);


        // 再追加
        $response = $this->actingAs($user)->from($detailUrl)->post(route('reviews.like', $review));
        $response->assertSessionHas('success', 'いいねしました。');
        $this->assertDatabaseHas('review_likes', ['user_id' => $user->id, 'review_id' => $review->id]);
    }

    /** ゲストはいいねできず、ログイン画面へ遷移する */
    public function test_guest_cannot_like_review(): void
    {
        $review = Review::factory()->create();

        $response = $this->post(route('reviews.like', $review));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('review_likes', 0);
    }

    /** 自分のレビューにもいいねできる */
    public function test_user_can_like_own_review(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('reviews.like', $review));

        $this->assertDatabaseHas('review_likes', ['user_id' => $user->id, 'review_id' => $review->id]);
    }
}
