<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 登録用の正しいリクエスト内容を作る
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9784000000001',
            'published_date' => '2024-01-01',
            'description' => 'テスト用の説明',
            'image_url' => 'https://example.com/image.jpg',
            'genres' => [Genre::factory()->create()->id],
            'user_id' => User::factory()->create()->id,
        ], $overrides);
    }

    /** 一覧は初期設定で20件ずつ返り、ページ情報が含まれる */
    public function test_index_returns_twenty_books_per_page_by_default(): void
    {
        Book::factory()->count(25)->create();

        $response = $this->getJson('/api/v1/books');

        $response->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'title',
                    'author',
                    'isbn',
                    'published_date',
                    'description',
                    'image_url',
                    'genres',
                    'average_rating',
                    'review_count',
                ]],
                'links',
                'meta',
            ]);
    }

    /** per_page を指定すると、その件数で返る */
    public function test_index_respects_per_page(): void
    {
        Book::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/books?per_page=3');

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    /** per_page が範囲外だと422になる */
    public function test_index_rejects_invalid_per_page(): void
    {
        $response = $this->getJson('/api/v1/books?per_page=101');

        $response->assertStatus(422)->assertJsonValidationErrors(['per_page']);
    }

    /** キーワードはタイトルまたは著者に部分一致する */
    public function test_index_filters_by_keyword(): void
    {
        Book::factory()->create(['title' => '吾輩は猫である', 'author' => '夏目漱石']);
        Book::factory()->create(['title' => 'リーダブルコード', 'author' => 'Dustin Boswell']);

        $byAuthor = $this->getJson('/api/v1/books?keyword=夏目');
        $byTitle = $this->getJson('/api/v1/books?keyword=リーダブル');

        $byAuthor->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.author', '夏目漱石');
        $byTitle->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'リーダブルコード');
    }

    /** ジャンルで絞り込める */
    public function test_index_filters_by_genre(): void
    {
        $genre = Genre::factory()->create();
        $inGenre = Book::factory()->create();
        $inGenre->genres()->attach($genre->id);
        Book::factory()->create();

        $response = $this->getJson('/api/v1/books?genre_id='.$genre->id);

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $inGenre->id);
    }

    /** 存在しないジャンルIDの指定は422になる */
    public function test_index_rejects_unknown_genre_id(): void
    {
        $response = $this->getJson('/api/v1/books?genre_id=9999');

        $response->assertStatus(422)->assertJsonValidationErrors(['genre_id']);
    }

    /** 一覧に平均評価とレビュー件数が含まれ、レビューなしの平均はnullになる */
    public function test_index_includes_average_rating_and_reviews_count(): void
    {
        $reviewed = Book::factory()->create();
        Review::factory()->create(['book_id' => $reviewed->id, 'rating' => 5]);
        Review::factory()->create(['book_id' => $reviewed->id, 'rating' => 4]);
        $noReview = Book::factory()->create();

        $response = $this->getJson('/api/v1/books');

        $response->assertOk();
        $data = collect($response->json('data'))->keyBy('id');
        $this->assertSame(4.5, $data[$reviewed->id]['average_rating']);
        $this->assertSame(2, $data[$reviewed->id]['review_count']);
        $this->assertNull($data[$noReview->id]['average_rating']);
        $this->assertSame(0, $data[$noReview->id]['review_count']);
    }

    /** 詳細はジャンルとレビュー（投稿者名つき）を含めて返す */
    public function test_show_returns_book_with_genres_and_reviews(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();
        $book->genres()->attach($genre->id);
        $reviewer = User::factory()->create(['name' => 'レビュー太郎']);
        Review::factory()->create(['book_id' => $book->id, 'user_id' => $reviewer->id, 'rating' => 5]);

        $response = $this->getJson('/api/v1/books/'.$book->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.genres.0.name', $genre->name)
            ->assertJsonPath('data.reviews.0.user_name', 'レビュー太郎')
            ->assertJsonPath('data.reviews.0.rating', 5)
            ->assertJsonStructure(['data' => ['reviews' => [['id', 'user_name', 'rating', 'comment', 'created_at']]]]);
    }

    /** 存在しないIDの詳細は404とJSONメッセージを返す */
    public function test_show_returns404_for_unknown_book(): void
    {
        $response = $this->getJson('/api/v1/books/9999');

        $response->assertNotFound()->assertExactJson(['error' => '書籍が見つかりませんでした。']);
    }

    /** 登録に成功すると201を返し、書籍とジャンル紐付けが保存される */
    public function test_store_creates_book(): void
    {
        $payload = $this->validPayload();

        $response = $this->postJson('/api/v1/books', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'テスト書籍')
            ->assertJsonPath('data.genres.0.id', $payload['genres'][0]);
        $this->assertDatabaseHas('books', ['isbn' => '9784000000001', 'user_id' => $payload['user_id']]);
        $this->assertDatabaseHas('book_genre', ['genre_id' => $payload['genres'][0]]);
    }

    /** 必須項目が空だと422になり、日本語のエラーが返る */
    public function test_store_fails_with_empty_payload(): void
    {
        $response = $this->postJson('/api/v1/books', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'author', 'isbn', 'published_date', 'genres', 'user_id'])
            ->assertJsonPath('errors.title.0', 'タイトルは必須です。');
    }

    /** ISBNが重複していると422になる */
    public function test_store_fails_with_duplicate_isbn(): void
    {
        Book::factory()->create(['isbn' => '9784000000001']);

        $response = $this->postJson('/api/v1/books', $this->validPayload());

        $response->assertStatus(422)->assertJsonValidationErrors(['isbn']);
    }

    /** ISBNが13桁でないと422になる */
    public function test_store_fails_with_invalid_isbn_length(): void
    {
        $response = $this->postJson('/api/v1/books', $this->validPayload(['isbn' => '12345']));

        $response->assertStatus(422)->assertJsonValidationErrors(['isbn']);
    }

    /** 存在しない登録者IDだと422になる */
    public function test_store_fails_with_unknown_user_id(): void
    {
        $response = $this->postJson('/api/v1/books', $this->validPayload(['user_id' => 9999]));

        $response->assertStatus(422)->assertJsonValidationErrors(['user_id']);
    }

    /** 更新に成功すると200を返し、ジャンル紐付けも更新される。登録者は送信したuser_idに変わる */
    public function test_update_changes_book_and_owner(): void
    {
        $book = Book::factory()->create();
        $newGenre = Genre::factory()->create();
        $payload = $this->validPayload([
            'title' => '更新後タイトル',
            'isbn' => $book->isbn,
            'genres' => [$newGenre->id],
        ]);

        $response = $this->putJson('/api/v1/books/'.$book->id, $payload);

        $response->assertOk()->assertJsonPath('data.title', '更新後タイトル');
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '更新後タイトル', 'user_id' => $payload['user_id']]);
        $this->assertDatabaseHas('book_genre', ['book_id' => $book->id, 'genre_id' => $newGenre->id]);
    }

    /** 更新でも登録者IDが未指定だと更新できない */
    public function test_update_fails_without_user_id(): void
    {
        $book = Book::factory()->create();
        $payload = $this->validPayload(['isbn' => $book->isbn]);
        unset($payload['user_id']);

        $response = $this->putJson('/api/v1/books/' . $book->id, $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['user_id']);
    }

    /** 他の書籍と同じISBNに更新すると422になる */
    public function test_update_fails_with_isbn_of_another_book(): void
    {
        $other = Book::factory()->create();
        $book = Book::factory()->create();

        $response = $this->putJson('/api/v1/books/'.$book->id, $this->validPayload(['isbn' => $other->isbn]));

        $response->assertStatus(422)->assertJsonValidationErrors(['isbn']);
    }

    /** 存在しないIDの更新は404を返す */
    public function test_update_returns404_for_unknown_book(): void
    {
        $response = $this->putJson('/api/v1/books/9999', $this->validPayload());

        $response->assertNotFound()->assertExactJson(['error' => '書籍が見つかりませんでした。']);
    }

    /** 削除に成功すると204を返し、関連データも削除される */
    public function test_destroy_deletes_book_and_related_data(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();
        $book->genres()->attach($genre->id);
        $review = Review::factory()->create(['book_id' => $book->id]);
        $favoriteUser = User::factory()->create();
        $favoriteUser->favoriteBooks()->attach($book->id);

        $response = $this->deleteJson('/api/v1/books/'.$book->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
        $this->assertDatabaseMissing('book_genre', ['book_id' => $book->id]);
        $this->assertDatabaseMissing('favorites', ['book_id' => $book->id]);
    }

    /** 存在しないIDの削除は404を返す */
    public function test_destroy_returns404_for_unknown_book(): void
    {
        $response = $this->deleteJson('/api/v1/books/9999');

        $response->assertNotFound()->assertExactJson(['error' => '書籍が見つかりませんでした。']);
    }
}
