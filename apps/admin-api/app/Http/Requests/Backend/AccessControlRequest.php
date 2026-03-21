<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccessControlRequest extends FormRequest
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
        $accessControlId = $this->route('accessControl')?->id
            ?? $this->route('access_control')?->id
            ?? null;

        return [
            'name' => [
                'required',
                'max:50',
                Rule::unique('access_controls')
                    ->where(fn ($query) => $query->whereNull('deleted_at'))
                    ->ignore($accessControlId),
            ],
            'type' => 'required',
            'status' => 'required',
        ];
    }
}
