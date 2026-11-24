<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreCrudTest extends TestCase
{
    use RefreshDatabase;

    /** ジャンルを登録するとDBに保存され、一覧へ遷移する */
    public function test_user_can_store_genre(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('genres.store'), ['name' => '小説']);

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success', 'ジャンルを作成しました。');
        $this->assertDatabaseHas('genres', ['name' => '小説']);
    }

    /** 重複したジャンル名は登録できない */
    public function test_store_genre_fails_when_name_is_duplicated(): void
    {
        $user = User::factory()->create();
        Genre::factory()->create(['name' => '小説']);

        $response = $this->actingAs($user)->post(route('genres.store'), ['name' => '小説']);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('genres', 1);
    }

    /** ジャンル名が空だと登録できない */
    public function test_store_genre_fails_when_name_is_empty(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('genres.store'), ['name' => '']);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('genres', 0);
    }

    /** ジャンルを更新でき、一覧へ遷移する */
    public function test_user_can_update_genre(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => '旧名称']);

        $response = $this->actingAs($user)->put(route('genres.update', $genre), ['name' => '新名称']);

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success', 'ジャンルを更新しました。');
        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => '新名称']);
    }

    /** 自身の名前のまま更新しても、重複扱いにならない */
    public function test_update_genre_allows_own_name(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => '小説']);

        $response = $this->actingAs($user)->put(route('genres.update', $genre), ['name' => '小説']);

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect(route('genres.index'));
    }

    /** 書籍が紐付いていないジャンルは削除でき、一覧へ遷移する */
    public function test_user_can_delete_genre_without_books(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success', 'ジャンルを削除しました。');
        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
    }

    /** 書籍が紐付いているジャンルは削除を拒否され、ジャンルは残る */
    public function test_delete_genre_is_rejected_when_books_are_attached(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        Book::factory()->create()->genres()->attach($genre->id);

        $response = $this->actingAs($user)->delete(route('genres.destroy', $genre));

        $response->assertSessionHas('error', 'このジャンルには書籍が紐付いているため削除できません。');
        $this->assertDatabaseHas('genres', ['id' => $genre->id]);
    }
}
