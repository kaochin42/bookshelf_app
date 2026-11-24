<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    /** お気に入りは追加→解除→再追加の順にトグルされる */
    public function test_favorite_is_toggled(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $detailUrl = route('books.show', $book);

        // 追加
        $response = $this->actingAs($user)->from($detailUrl)->post(route('favorites.toggle', $book));
        $response->assertRedirect($detailUrl);
        $response->assertSessionHas('success', 'お気に入りに追加しました。');
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'book_id' => $book->id]);

        // 解除
        $response = $this->actingAs($user)->from($detailUrl)->post(route('favorites.toggle', $book));
        $response->assertSessionHas('success', 'お気に入りを解除しました。');
        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'book_id' => $book->id]);

        // 再追加
        $this->actingAs($user)->from($detailUrl)->post(route('favorites.toggle', $book));
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'book_id' => $book->id]);
    }

    /** ゲストはお気に入り登録できず、ログイン画面へ遷移する */
    public function test_guest_cannot_toggle_favorite(): void
    {
        $book = Book::factory()->create();

        $response = $this->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('favorites', 0);
    }

    /** 自分の書籍もお気に入りに登録できる */
    public function test_user_can_favorite_own_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('favorites.toggle', $book));

        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'book_id' => $book->id]);
    }

    /** お気に入り一覧には自分のお気に入りだけが表示される */
    public function test_favorite_index_shows_only_own_favorites(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $mine = Book::factory()->create(['title' => '自分のお気に入り本']);
        $others = Book::factory()->create(['title' => '他人のお気に入り本']);
        $user->favoriteBooks()->attach($mine->id);
        $other->favoriteBooks()->attach($others->id);

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertOk();
        $response->assertSee('自分のお気に入り本');
        $response->assertDontSee('他人のお気に入り本');
    }
}
