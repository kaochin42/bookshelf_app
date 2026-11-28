<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class IsbnSearchController extends Controller
{
    /**
     * ISBN から Google Books の書籍情報を取得して JSON で返す（認証必須）
     */
    public function show(string $isbn): JsonResponse
    {
        if (! preg_match('/^\d{13}$/', $isbn)) {
            return response()->json(['error' => 'ISBNは13桁の数字で入力してください。'], 400);
        }

        try {
            $response = Http::timeout((int) config('services.google_books.timeout'))
                ->get(config('services.google_books.url'), ['q' => 'isbn:'.$isbn]);
        } catch (ConnectionException) {
            return $this->communicationError();
        }

        if ($response->failed()) {
            return $this->communicationError();
        }

        $volumeInfo = $response->json('items.0.volumeInfo');

        if ($volumeInfo === null) {
            return response()->json(['error' => '該当する書籍が見つかりませんでした。'], 404);
        }

        return response()->json([
            'title' => $volumeInfo['title'] ?? null,
            'author' => isset($volumeInfo['authors']) ? implode('、', $volumeInfo['authors']) : null,
            'published_date' => $this->normalizePublishedDate($volumeInfo['publishedDate'] ?? null),
            'description' => $volumeInfo['description'] ?? null,
            'image_url' => $this->secureImageUrl($volumeInfo['imageLinks']['thumbnail'] ?? null),
        ]);
    }

    /**
     * 通信エラー・タイムアウト・API側の失敗は 500 で返す
     */
    private function communicationError(): JsonResponse
    {
        return response()->json(['error' => '書籍情報の取得中に通信エラーが発生しました。'], 500);
    }

    /**
     * Google Books の出版日は「2012」「2012-06」のような部分日付があるため、Y-m-d にそろえる
     */
    private function normalizePublishedDate(?string $publishedDate): ?string
    {
        return match (strlen((string) $publishedDate)) {
            4 => $publishedDate.'-01-01',
            7 => $publishedDate.'-01',
            10 => $publishedDate,
            default => null,
        };
    }

    /**
     * Google Books のサムネイルは http で返ることがあるため https に変換する
     */
    private function secureImageUrl(?string $imageUrl): ?string
    {
        return $imageUrl === null ? null : str_replace('http://', 'https://', $imageUrl);
    }
}
