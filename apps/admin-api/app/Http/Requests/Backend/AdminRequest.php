<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminRequest extends FormRequest
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
        $userId = $this->route('admin')?->id ?? $this->route('user')?->id;

        $rules = [
            'name' => [
                'required',
                'string',
                'regex:/^[A-Za-z0-9]+$/',
                'max:50',
                Rule::unique('users', 'name')->ignore($userId),
            ],
            'role_type' => ['required'],
            'nickname' => ['string', 'max:50', 'nullable'],
            'status' => ['required', 'string', 'max:10'],
        ];

        // Password rules for create OR update when provided
        if ($this->isMethod('post') || $this->filled(['password', 'password_confirmation'])) {
            $rules['password'] = [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[A-Za-z])(?=.*\d)[^\s]+$/',
            ];
            $rules['password_confirmation'] = [
                'required',
                'string',
                'min:8',
                'same:password',
            ];
        }

        return $rules;
    }
}
