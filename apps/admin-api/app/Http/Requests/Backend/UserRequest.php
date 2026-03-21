<?php

namespace App\Http\Requests\Backend;

use App\Enums\RoleValue;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var \App\Models\User|null $user */
        $user = $this->route('user');
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');
        $roleId = $this->input('role_id');
        $currentUserRoleId = Auth::user()?->role_id;

        $rules = [
            'name' => [
                'required',
                'string',
                'regex:/^[A-Za-z0-9]+$/',
                'max:50',
                Rule::unique('users', 'name')->ignore($user?->id),
            ],
            'role_id' => ['required', 'string', 'max:10'],
            'status' => ['required', 'string', 'max:10'],
            'nickname' => ['nullable', 'string', 'max:100'],
            'contact_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-() ]*$/'],
            'initial_credit' => ['nullable', 'numeric', 'min:0'],
            'credit' => ['nullable', 'numeric', 'min:0'],
        ];

        match ($currentUserRoleId) {
            RoleValue::SUPER_ADMIN->value, RoleValue::SYSTEM_ADMIN->value => $rules = $this->setAdminRules($rules, $roleId),
            RoleValue::COMPANY_ADMIN->value => $rules = $this->setCompanyRules($rules, $roleId),
            RoleValue::RESTAURANT_ADMIN->value => $rules = $this->setRestaurantRules($rules, $roleId),
            RoleValue::BRANCH_ADMIN->value => $rules = $this->setBranchRules($rules, $roleId),
            default => $rules,
        };

        if (! $isUpdate) {
            $rules['password'] = [
                'required',
                'min:8',
                'regex:/^(?=.*[A-Za-z])(?=.*\d)[^\s]+$/',
            ];
            $rules['password_confirmation'] = ['required', 'same:password'];
        }

        if ($isUpdate && ($this->filled('password') || $this->filled('password_confirmation'))) {
            $rules['password'] = [
                'required',
                'min:8',
                'regex:/^(?=.*[A-Za-z])(?=.*\d)[^\s]+$/',
            ];
            $rules['password_confirmation'] = ['required', 'same:password'];
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'branch_id.required' => 'The branch field is required.',
            'branch_id.exists' => 'The selected branch is invalid.',
            'company_id.required' => 'The company field is required.',
            'company_id.exists' => 'The selected company is invalid.',
            'restaurant_id.required' => 'The restaurant field is required.',
            'restaurant_id.exists' => 'The selected restaurant is invalid.',
        ];
    }

    private function setAdminRules(array $rules, $roleId): array
    {
        $rules['company_id'] = array_merge(
            in_array($roleId, [RoleValue::OPERATOR->value, RoleValue::STAFF->value, RoleValue::DRIVER->value])
                ? ['required'] : ['nullable'],
            ['integer', 'exists:companies,id']
        );

        $rules['branch_id'] = array_merge(
            $roleId === RoleValue::STAFF->value
                ? ['required'] : ['nullable'],
            ['integer', 'exists:branches,id']
        );

        $rules['restaurant_id'] = array_merge(
            $roleId === RoleValue::OPERATOR->value
                ? ['required'] : ['nullable'],
            ['integer', 'exists:restaurants,id']
        );

        return $rules;
    }

    private function setCompanyRules(array $rules, $roleId): array
    {
        $rules['company_id'] = ['prohibited'];

        $rules['branch_id'] = array_merge(
            $roleId === RoleValue::STAFF->value
                ? ['required'] : ['nullable'],
            ['integer', 'exists:branches,id']
        );

        $rules['restaurant_id'] = array_merge(
            $roleId === RoleValue::OPERATOR->value
                ? ['required'] : ['nullable'],
            ['integer', 'exists:restaurants,id']
        );

        return $rules;
    }

    private function setRestaurantRules(array $rules, $roleId): array
    {
        $rules['company_id'] = ['prohibited'];

        $rules['branch_id'] = ['prohibited'];

        $rules['restaurant_id'] = array_merge(
            $roleId === RoleValue::OPERATOR->value
                ? ['required'] : ['nullable'],
            ['integer', 'exists:restaurants,id']
        );

        return $rules;
    }

    private function setBranchRules(array $rules, $roleId): array
    {
        $rules['company_id'] = ['prohibited'];

        $rules['branch_id'] = array_merge(
            $roleId === RoleValue::STAFF->value
                ? ['required'] : ['nullable'],
            ['integer', 'exists:branches,id']
        );

        $rules['restaurant_id'] = ['prohibited'];

        return $rules;
    }
}
