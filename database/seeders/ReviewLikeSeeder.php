<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * 各レビューに0〜3人のいいねを登録する(投稿者本人は除く)。
     * ReviewSeeder の後に実行すること。
     */
    public function run(): void
    {
        $users = User::all();

        Review::all()->each(function (Review $review) use ($users): void {
            $likers = $users->where('id', '!=', $review->user_id)->random(rand(0, 3));

            $review->likedByUsers()->syncWithoutDetaching($likers->pluck('id'));
        });
    }
}
