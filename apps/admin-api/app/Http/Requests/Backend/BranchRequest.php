<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BranchRequest extends FormRequest
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
        $branchId = $this->route('branch')?->id;

        // Base rules for both POST and PUT/PATCH
        $rules = [
            'name' => [
                'required',
                'string',
                'regex:/^[\p{L} ]+$/u',
                'max:50',
                Rule::unique('branches', 'name')->ignore($branchId),
            ],
            'location' => ['nullable', 'string', 'max:100'],
            'remark' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'string', 'max:10'],
        ];

        // Additional rules only for POST requests
        if ($this->isMethod('post')) {

            $rules['company_id'] = Auth::user()?->company_id
                ? ['nullable', 'integer', 'exists:companies,id']
                : ['required', 'integer', 'exists:companies,id'];
            $rules['username'] = [
                'required',
                'string',
                'regex:/^[A-Za-z0-9]+$/',
                'max:50',
                Rule::unique('users', 'name'),
            ];
            $rules['password'] = [
                'required',
                'min:8',
                'regex:/^(?=.*[A-Za-z])(?=.*\d)[^\s]+$/',
            ];
            $rules['password_confirmation'] = [
                'required',
                'min:8',
                'same:password',
            ];
        }

        return $rules;
    }
}
