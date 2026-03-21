<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RestaurantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $restaurant = $this->route('restaurant');
        $restaurantId = $restaurant?->id ?? null;

        $isCreate = $this->isMethod('post');
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        if ($isCreate) {
            $companyRule = Auth::user()?->company_id
                ? ['nullable', 'integer', 'exists:companies,id']
                : ['required', 'integer', 'exists:companies,id'];
        } else {
            $companyRule = ['sometimes', 'integer', 'exists:companies,id'];
        }

        $companyIdForUnique = $this->input('company_id') ?? $restaurant?->company_id ?? null;

        $rules = [
            'company_id' => $companyRule,
            'remark' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'string', 'max:10'],
            'name' => [
                'required',
                'string',
                'regex:/^[\p{L}0-9 \-&]+$/u',
                'max:100',
                Rule::unique('restaurants', 'name')
                    ->where(function ($query) use ($companyIdForUnique) {
                        if ($companyIdForUnique !== null) {
                            $query->where('company_id', $companyIdForUnique);
                        }

                        return $query->whereNull('deleted_at');
                    })->ignore($restaurantId),
            ],
        ];

        if ($isCreate) {
            $rules['username'] = [
                'required',
                'string',
                'regex:/^[A-Za-z0-9]+$/',
                'max:50',
                Rule::unique('users', 'name'),
            ];

            $rules['password'] = [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[A-Za-z])(?=.*\d)[^\s]+$/',
                'confirmed',
            ];

            $rules['password_confirmation'] = [
                'required',
                'string',
                'min:8',
            ];
        } elseif ($isUpdate) {
            $rules['password'] = ['nullable', 'string', 'min:8', 'regex:/^(?=.*[A-Za-z])(?=.*\d)[^\s]+$/', 'confirmed'];
        }

        return $rules;
    }
}
