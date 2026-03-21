<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $company = $this->route('company');

        $isCreate = $this->isMethod('post');
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        $rules = [
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'order_per_meal_time' => ['nullable', 'string', Rule::in(['Single', 'Multiple'])],
            'period' => ['nullable', 'integer'],
            'day' => ['nullable', 'string', 'max:10'],
            'day_number' => ['nullable', 'string', 'max:10'],
            'place_order_weekend' => ['required', 'boolean'],
            'place_order_holiday' => ['required', 'boolean'],
            'remark' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'string', 'max:10'],
        ];

        if ($isCreate) {
            $rules['username'] = [
                'required',
                'string',
                'regex:/^[A-Za-z0-9]+$/',
                'max:50',
                Rule::unique('users', 'name'),
            ];

            $rules['name'] = [
                'required',
                'string',
                'regex:/^[\p{L} ]+$/u',
                'max:50',
                Rule::unique('companies', 'name')->whereNull('deleted_at'),
            ];

            $rules['password'] = [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[A-Za-z])(?=.*\d)[^\s]+$/',
            ];

            $rules['password_confirmation'] = [
                'required',
                'same:password',
            ];
        } elseif ($isUpdate) {
            $rules['password'] = [
                'nullable',
                'string',
                'min:8',
                'regex:/^(?=.*[A-Za-z])(?=.*\d)[^\s]+$/',
            ];

            $rules['password_confirmation'] = [
                'nullable',
                'same:password',
            ];
        }

        if ((int) $this->input('period') === 2) {
            $rules['day'] = ['required', 'string', 'max:10'];
        }

        if ((int) $this->input('period') === 3) {
            $rules['day_number'] = ['required', 'string', 'max:10'];
        }

        // Holidays validation
        $rules['holidays'] = ['nullable', 'array'];
        $rules['holidays.*.id'] = ['nullable', 'integer', 'exists:holidays,id'];
        $rules['holidays.*.name'] = ['nullable', 'string', 'max:100'];
        $rules['holidays.*.date'] = ['nullable', 'date', 'date_format:Y-m-d'];

        return $rules;
    }
}
