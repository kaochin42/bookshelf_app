<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScreenAccessTest extends TestCase
{
    use RefreshDatabase;

    /** ゲストは書籍一覧を表示できる */
    public function test_guest_can_view_book_index(): void
    {
        $this->get('/')->assertOk();
    }

    /** ゲストは書籍詳細を表示できる */
    public function test_guest_can_view_book_show(): void
    {
        $book = Book::factory()->create();

        $this->get(route('books.show', $book))->assertOk();
    }

    /** ゲストはランキングを表示できる */
    public function test_guest_can_view_ranking(): void
    {
        $this->get(route('ranking.index'))->assertOk();
    }

    /** ゲストは書籍登録画面からログイン画面へ遷移する */
    public function test_guest_is_redirected_from_book_create_to_login(): void
    {
        $this->get(route('books.create'))->assertRedirect(route('login'));
    }

    /** ゲストはジャンル一覧からログイン画面へ遷移する */
    public function test_guest_is_redirected_from_genre_index_to_login(): void
    {
        $this->get(route('genres.index'))->assertRedirect(route('login'));
    }

    /** ゲストはお気に入り一覧からログイン画面へ遷移する */
    public function test_guest_is_redirected_from_favorite_index_to_login(): void
    {
        $this->get(route('favorites.index'))->assertRedirect(route('login'));
    }

    /** ログイン済みでログイン画面を開くと書籍一覧へ遷移する */
    public function test_authenticated_user_is_redirected_from_login_to_book_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('login'))->assertRedirect('/');
    }
}
