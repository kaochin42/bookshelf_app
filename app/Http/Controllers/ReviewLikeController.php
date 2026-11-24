<?php

namespace App\Http\Controllers;

use App\Models\Review;

class ReviewLikeController extends Controller
{
    public function toggle(Review $review)
    {
        $result = auth()->user()->likedReviews()->toggle($review->id);

        $message = count($result['attached']) > 0
            ? 'いいねしました。'
            : 'いいねを取り消しました。';

        return back()->with('success', $message);
    }
}
