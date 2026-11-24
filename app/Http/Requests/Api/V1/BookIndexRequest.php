<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class BookIndexRequest extends FormRequest
{
    /**
     * 公開API（基本機能は認証なし）
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'genre_id' => ['nullable', 'integer', 'exists:genres,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'keyword' => 'キーワード',
            'genre_id' => 'ジャンルID',
            'page' => 'ページ番号',
            'per_page' => '1ページあたりの件数',
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.string' => ':attributeは文字列で指定してください。',
            'keyword.max' => ':attributeは255文字以内で指定してください。',
            'genre_id.integer' => ':attributeは整数で指定してください。',
            'genre_id.exists' => '指定された:attributeは存在しません。',
            'page.integer' => ':attributeは整数で指定してください。',
            'page.min' => ':attributeは1以上で指定してください。',
            'per_page.integer' => ':attributeは整数で指定してください。',
            'per_page.between' => ':attributeは1〜100の範囲で指定してください。',
        ];
    }
}
