<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /** レビューの編集は投稿者のみ */
    public function update(User $user, Review $review): bool
    {
        return $review->user_id === $user->id;
    }

    /** レビューの削除は投稿者のみ */
    public function delete(User $user, Review $review): bool
    {
        return $review->user_id === $user->id;
    }
}
