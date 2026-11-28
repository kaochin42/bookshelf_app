<?php

namespace App\Http\Requests;

use App\Enums\ReadingPlanStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReadingPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'target_date' => ['required', 'date', 'after_or_equal:today'],
        ];

        // 書籍は作成時のみ。編集画面には書籍の入力欄がない
        if ($this->isMethod('POST')) {
            $rules['book_id'] = [
                'required',
                'exists:books,id',
                // 同じユーザーが同じ書籍に持てる進行中の計画は1つだけ
                Rule::unique('reading_plans', 'book_id')
                    ->where('user_id', $this->user()->id)
                    ->where('status', ReadingPlanStatus::InProgress->value),
            ];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'book_id' => '書籍',
            'target_date' => '期日',
        ];
    }

    public function messages(): array
    {
        return [
            'book_id.required' => '書籍を選択してください。',
            'book_id.exists' => '選択された書籍は存在しません。',
            'book_id.unique' => 'この書籍は既に進行中の読書計画が存在します。',
            'target_date.required' => ':attributeは必須です。',
            'target_date.date' => ':attributeは有効な日付形式で入力してください。',
            'target_date.after_or_equal' => ':attributeは今日以降の日付を指定してください。',
        ];
    }
}
