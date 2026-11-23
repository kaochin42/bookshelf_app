<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * 書籍11件を登録し、ジャンルを紐付ける(ISBN重複時は作成しない)。
     * 登録者は先頭ユーザー(山田太郎)。UserSeeder・GenreSeeder の後に実行すること。
     */
    public function run(): void
    {
        $user = User::first();

        $books = [
            ['title' => '吾輩は猫である', 'author' => '夏目漱石', 'isbn' => '9784101010014', 'published_date' => '1905-01-01', 'description' => '中学教師の家に住み着いた猫の目を通して、人間社会を風刺的に描いた長編小説。', 'genres' => ['小説']],
            ['title' => '人を動かす', 'author' => 'D・カーネギー', 'isbn' => '9784422100524', 'published_date' => '1936-10-01', 'description' => '人間関係の原則を、豊富な実例とともに紹介する自己啓発書の古典。', 'genres' => ['ビジネス', '自己啓発']],
            ['title' => 'リーダブルコード', 'author' => 'Dustin Boswell', 'isbn' => '9784873115658', 'published_date' => '2012-06-23', 'description' => '読みやすく理解しやすいコードを書くための、実践的なテクニックをまとめた一冊。', 'genres' => ['技術書']],
            ['title' => '7つの習慣', 'author' => 'スティーブン・R・コヴィー', 'isbn' => '9784863940246', 'published_date' => '2013-08-30', 'description' => '人格主義に基づき、成功するための7つの習慣を体系的に解説する。', 'genres' => ['ビジネス', '自己啓発']],
            ['title' => '坊っちゃん', 'author' => '夏目漱石', 'isbn' => '9784101010021', 'published_date' => '1906-04-01', 'description' => '正義感の強い青年教師が、四国の中学校で巻き起こす騒動を描いた小説。', 'genres' => ['小説']],
            ['title' => 'サピエンス全史', 'author' => 'ユヴァル・ノア・ハラリ', 'isbn' => '9784309226712', 'published_date' => '2016-09-08', 'description' => '認知革命から現代まで、人類の歴史を大きな視点で捉え直した話題作。', 'genres' => ['歴史', '科学']],
            ['title' => 'Clean Code', 'author' => 'Robert C. Martin', 'isbn' => '9784048930598', 'published_date' => '2017-12-18', 'description' => '保守しやすい高品質なコードを書くための原則と実践方法を解説する。', 'genres' => ['技術書']],
            ['title' => '嫌われる勇気', 'author' => '岸見一郎・古賀史健', 'isbn' => '9784478025819', 'published_date' => '2013-12-13', 'description' => 'アドラー心理学を、青年と哲人の対話形式でわかりやすく紹介する。', 'genres' => ['自己啓発']],
            ['title' => '火花', 'author' => '又吉直樹', 'isbn' => '9784163902302', 'published_date' => '2015-03-11', 'description' => '売れない芸人の青春と葛藤を描いた、芥川賞受賞作。', 'genres' => ['小説']],
            ['title' => 'FACTFULNESS', 'author' => 'ハンス・ロスリング', 'isbn' => '9784822289607', 'published_date' => '2019-01-11', 'description' => '思い込みを捨て、データをもとに世界を正しく見るための10の習慣を示す。', 'genres' => ['ビジネス', '科学']],
            ['title' => 'コンテナ物語', 'author' => 'マルク・レビンソン', 'isbn' => '9784822251468', 'published_date' => '2007-01-18', 'description' => '一つの箱が世界の物流と経済を変えた歴史を描くノンフィクション。', 'genres' => ['ビジネス', '歴史']],
        ];

        foreach ($books as $index => $data) {
            $book = Book::firstOrCreate(
                ['isbn' => $data['isbn']],
                [
                    'title' => $data['title'],
                    'author' => $data['author'],
                    'published_date' => $data['published_date'],
                    'description' => $data['description'],
                    'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text='.($index + 1),
                    'user_id' => $user->id,
                ],
            );

            $book->genres()->sync(Genre::whereIn('name', $data['genres'])->pluck('id'));
        }
    }
}
