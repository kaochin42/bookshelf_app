<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
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
            'isbn' => 'ISBN-13',
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
            'title.string' => ':attributeは文字で入力してください。',
            'title.max' => ':attributeは:max文字以内で入力してください。',
            'author.required' => ':attributeは必須です。',
            'author.string' => ':attributeは文字で入力してください。',
            'author.max' => ':attributeは:max文字以内で入力してください。',
            'isbn.required' => ':attributeは必須です。',
            'isbn.digits' => ':attributeは数字:digits桁で入力してください。',
            'isbn.unique' => 'この:attributeはすでに登録されています。',
            'published_date.required' => ':attributeは必須です。',
            'published_date.date' => ':attributeは正しい日付で入力してください。',
            'description.string' => ':attributeは文字で入力してください。',
            'description.max' => ':attributeは:max文字以内で入力してください。',
            'image_url.url' => ':attributeは正しいURL形式で入力してください。',
            'image_url.max' => ':attributeは:max文字以内で入力してください。',
            'genres.required' => ':attributeを1つ以上選択してください。',
            'genres.array' => ':attributeの形式が正しくありません。',
            'genres.min' => ':attributeを1つ以上選択してください。',
            'genres.*.integer' => ':attributeの形式が正しくありません。',
            'genres.*.exists' => '選択した:attributeが存在しません。',
        ];
    }
}
