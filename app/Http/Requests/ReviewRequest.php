<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewRequest extends FormRequest
{
    // 認証はルートのauth、本人確認はPolicyで行う
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes()
    {
        return [
            'rating' => '評価',
            'comment' => 'コメント',
        ];
    }

    public function messages()
    {
        return [
            'rating.required' => '評価は必須です。',
            'rating.integer' => '評価は整数で選択してください。',
            'rating.between' => '評価は1〜5の範囲で選択してください。',
            'comment.string' => 'コメントは文字列で入力してください。',
            'comment.max' => 'コメントは1000文字以内で入力してください。',
        ];
    }
}
