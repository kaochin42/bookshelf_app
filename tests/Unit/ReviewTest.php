<?php

namespace Tests\Unit;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    /** レビューにいいねしたユーザーを取得できる */
    public function test_review_has_liked_by_users(): void
    {
        // 用意する
        $user = User::factory()->create();
        $review = Review::factory()->create();

        // 動かす
        $review->likedByUsers()->attach($user->id);

        // 確認する
        $this->assertCount(1, $review->likedByUsers);
        $this->assertTrue($review->likedByUsers->contains($user->id));
    }
}
