<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $menuItemId = $this->route('menuItem')?->id
            ?? $this->route('menu_item')?->id
            ?? null;

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Za-z0-9_-]+$/', // Only accept english, for image url purposes
                Rule::unique('menu_items', 'code')
                    ->whereNull('deleted_at')
                    ->ignore($menuItemId),
            ],
            'name' => [
                'required',
                'string',
                'regex:/^[\p{L} ]+$/u',
                'max:50',
                Rule::unique('menu_items', 'name')
                    ->where(fn($query) => $query->where('menu_category_id', $this->input('menu_category_id'))
                        ->whereNull('deleted_at'))
                    ->ignore($menuItemId),
            ],
            'status' => ['required', 'string', 'max:10'],
            'menu_category_id' => ['required', 'integer', 'exists:menu_categories,id'],
            'meal_time' => ['required', 'string', 'max:64'],
            'add_on' => ['required', 'in:yes,no'],
            'selection_type' => ['nullable', 'in:single,multiple'],
            'unit_price' => ['required', 'numeric', 'regex:/^\d{1,17}(\.\d{1,2})?$/'],
            'available_quantity' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'mimes:jpeg,png,jpg,gif', 'image', 'max:8192'],
            'cropped_image' => ['nullable', 'string', 'regex:/^data:image\/[a-zA-Z0-9]+;base64,/'],
            'is_veg' => ['required', 'in:Yes,No'],
            'select_ingredient' => ['nullable', 'array'],
            'select_ingredient.*' => ['string', Rule::in([
                'ALL',
                'CONTAIN_EGG',
                'CONTAIN_DAIRY',
                'CONTAIN_ONION_GARLIC',
                'CONTAIN_CHILI',
            ])],
            'remark' => ['nullable', 'string', 'max:200'],
            'import_file_id' => ['nullable', 'integer'],

            'add_ons' => ['array', Rule::requiredIf($this->input('add_on') === 'yes'), 'min:1'],
            'add_ons.*.name' => [Rule::requiredIf($this->input('add_on') === 'yes'), 'string', 'max:64'],
            'add_ons.*.type' => [Rule::requiredIf($this->input('add_on') === 'yes'), 'string', 'max:64'],
            'add_ons.*.min' => [Rule::requiredIf($this->input('add_on') === 'yes'), 'integer'],
            'add_ons.*.max' => [Rule::requiredIf($this->input('add_on') === 'yes'), 'integer'],
            'add_ons.*.required' => [Rule::requiredIf($this->input('add_on') === 'yes'), 'in:yes,no'],
            'add_ons.*.options' => ['array', Rule::requiredIf($this->input('add_on') === 'yes')],
            'add_ons.*.options.*.optionname' => [Rule::requiredIf($this->input('add_on') === 'yes'), 'string', 'max:64'],
            'add_ons.*.options.*.surcharge' => [Rule::requiredIf($this->input('add_on') === 'yes'), 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'add_ons.*.name.required' => __('lang.TheAddOnNameFieldIsRequired'),
            'add_ons.*.type.required' => __('lang.TheAddOnTypeFieldIsRequired'),
            'add_ons.*.required.required' => __('lang.TheAddOnRequiredFieldIsRequired'),
            'add_ons.*.min.required' => __('lang.TheAddOnMinFieldIsRequired'),
            'add_ons.*.max.required' => __('lang.TheAddOnMaxFieldIsRequired'),
            'add_ons.*.options.*.optionname.required' => __('lang.TheOptionNameFieldIsRequired'),
            'add_ons.*.options.*.surcharge.required' => __('lang.TheOptionSurchargeFieldIsRequired'),
        ];
    }
}
