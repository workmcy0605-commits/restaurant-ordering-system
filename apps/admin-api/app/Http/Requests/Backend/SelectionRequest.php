<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SelectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $selectionId = $this->route('selection')?->id ?? null;

        return [
            'value' => [
                'required',
                'string',
                'regex:/^[\p{L} ]+$/u',
                'max:50',
                Rule::unique('selections', 'value')
                    ->where(function ($query) {
                        return $query->where('category', $this->input('category'));
                    })
                    ->ignore($selectionId),
            ],
            'category' => 'required',
        ];

    }
}
