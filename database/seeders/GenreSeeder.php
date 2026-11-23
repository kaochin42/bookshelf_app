<?php

namespace Database\Seeders;

use App\Models\Genre;
use Illuminate\Database\Seeder;

class GenreSeeder extends Seeder
{
    /**
     * ジャンル10件を登録する(名前の重複時は作成しない)。
     */
    public function run(): void
    {
        $names = ['小説', 'ビジネス', '技術書', '自己啓発', 'エッセイ', '歴史', '科学', '芸術', '料理', '旅行'];

        foreach ($names as $name) {
            Genre::firstOrCreate(['name' => $name]);
        }
    }
}
