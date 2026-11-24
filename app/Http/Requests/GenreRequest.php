<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenreRequest extends FormRequest
{
    // 認可はルートの auth で担保（Policyなし）
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // 更新時のみ自身のレコードを一意性チェックから除外
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('genres', 'name')->ignore($this->route('genre')),
            ],
        ];
    }

    public function attributes()
    {
        return [
            'name' => 'ジャンル名',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'ジャンル名は必須です。',
            'name.string' => 'ジャンル名は文字列で入力してください。',
            'name.max' => 'ジャンル名は255文字以内で入力してください。',
            'name.unique' => 'このジャンル名はすでに登録されています。',
        ];
    }
}
