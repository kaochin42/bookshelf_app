<?php

namespace Tests\Unit;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_レビューにいいねしたユーザーを取得できる(): void
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
