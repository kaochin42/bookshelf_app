<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCrudTest extends TestCase
{
    use RefreshDatabase;

    /** 書籍を登録するとDB保存・ジャンル紐付けが行われ、詳細へ遷移する */
    public function test_user_can_store_book(): void
    {
        // 用意する
        $user = User::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        // 動かす
        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000001',
            'published_date' => '2024-01-01',
            'description' => '説明文',
            'image_url' => 'https://example.com/image.jpg',
            'genres' => $genres->pluck('id')->all(),
        ]);

        // 確認する
        $book = Book::where('isbn', '9784000000001')->first();

        $this->assertDatabaseHas('books', [
            'title' => 'テスト書籍',
            'user_id' => $user->id, // 登録者はログイン中のユーザー
        ]);
        $this->assertCount(2, $book->genres);
        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success', '書籍を登録しました。');
    }

    /** ISBNが重複していると登録できず、書籍は増えない */
    public function test_store_book_fails_when_isbn_is_duplicated(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        Book::factory()->create(['isbn' => '9784000000001']);

        $response = $this->actingAs($user)->post(route('books.store'), $this->validBookData($genre, [
            'isbn' => '9784000000001', // 既存の書籍と同じISBN
        ]));

        $response->assertSessionHasErrors('isbn');
        $this->assertDatabaseCount('books', 1); // 増えていない
    }

    /** ISBNが13桁未満だと登録できない */
    public function test_store_book_fails_when_isbn_is_shorter_than13_digits(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), $this->validBookData($genre, [
            'isbn' => '978400000000', // 12桁
        ]));

        $response->assertSessionHasErrors('isbn');
        $this->assertDatabaseCount('books', 0);
    }

    /** 必須項目が空だと登録できない */
    public function test_store_book_fails_when_required_fields_are_empty(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), []);

        $response->assertSessionHasErrors(['title', 'author', 'isbn', 'genres']);
        $response->assertSessionDoesntHaveErrors(['published_date']);
        $this->assertDatabaseCount('books', 0);
    }

    /** 登録用の正しい入力値（テストごとに一部だけ差し替える） */
    private function validBookData(Genre $genre, array $overrides = []): array
    {
        return array_merge([
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000002',
            'published_date' => '2024-01-01',
            'genres' => [$genre->id],
        ], $overrides);
    }

    /** 作成者は書籍を更新でき、詳細へ遷移する（自身のISBNは重複扱いにならない） */
    public function test_owner_can_update_book(): void
    {
        $owner = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id, 'isbn' => '9784000000003']);

        $response = $this->actingAs($owner)->put(route('books.update', $book), $this->validBookData($genre, [
            'title' => '更新後のタイトル',
            'isbn' => '9784000000003', // 変更しない
        ]));

        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success', '書籍情報を更新しました。');
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '更新後のタイトル']);
        $this->assertCount(1, $book->fresh()->genres);
    }

    /** 他人の書籍は編集画面を開けない（403） */
    public function test_other_user_cannot_open_edit_form(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->actingAs($user)->get(route('books.edit', $book))->assertForbidden();
    }

    /** 他人の書籍は更新できず、内容も変わらない（403） */
    public function test_other_user_cannot_update_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create(['title' => '元のタイトル']);

        $response = $this->actingAs($user)->put(route('books.update', $book), $this->validBookData($genre, [
            'title' => '書き換えたタイトル',
        ]));

        $response->assertForbidden();
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '元のタイトル']);
    }

    /** 作成者が削除すると、レビュー・お気に入り・ジャンル紐付けも削除され、一覧へ遷移する */
    public function test_owner_can_delete_book_with_related_data(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);
        $review = Review::factory()->create(['book_id' => $book->id]);
        $book->genres()->attach(Genre::factory()->create()->id);
        $owner->favoriteBooks()->attach($book->id);

        $response = $this->actingAs($owner)->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));
        $response->assertSessionHas('success', '書籍を削除しました。');
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
        $this->assertDatabaseMissing('favorites', ['book_id' => $book->id]);
        $this->assertDatabaseMissing('book_genre', ['book_id' => $book->id]);
    }

    /** 他人の書籍は削除できず、書籍は残る（403） */
    public function test_other_user_cannot_delete_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->actingAs($user)->delete(route('books.destroy', $book))->assertForbidden();
        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }
}
