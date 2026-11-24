<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookUpdateRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            // 自身のレコードは重複扱いにしない
            'isbn' => ['required', 'digits:13', Rule::unique('books', 'isbn')->ignore($this->route('book'))],
            'published_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image_url' => ['nullable', 'url', 'max:255'],
            'genres' => ['required', 'array', 'min:1'],
            'genres.*' => ['integer', 'exists:genres,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'タイトル',
            'author' => '著者',
            'isbn' => 'ISBN',
            'published_date' => '出版日',
            'description' => '説明',
            'image_url' => '画像URL',
            'genres' => 'ジャンル',
            'genres.*' => 'ジャンル',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => ':attributeは必須です。',
            'author.required' => ':attributeは必須です。',
            'isbn.required' => ':attributeは必須です。',
            'isbn.digits' => ':attributeはハイフンなしの13桁の数字で入力してください。',
            'isbn.unique' => 'この:attributeはすでに登録されています。',
            'published_date.required' => ':attributeは必須です。',
            'published_date.date' => ':attributeは有効な日付で入力してください。',
            'description.max' => ':attributeは1000文字以内で入力してください。',
            'image_url.url' => ':attributeはURL形式で入力してください。',
            'genres.required' => ':attributeは1つ以上選択してください。',
            'genres.min' => ':attributeは1つ以上選択してください。',
            'genres.*.exists' => '選択された:attributeは存在しません。',
        ];
    }
}
