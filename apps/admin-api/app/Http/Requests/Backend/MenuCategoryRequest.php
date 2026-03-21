<?php

namespace App\Http\Requests\Backend;

use App\Enums\CalendarWeek;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuCategoryRequest extends FormRequest
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
        $menuCategoryId = $this->route('menuCategory')?->id
            ?? $this->route('menu_category')?->id
            ?? null;

        $rules = [
            'name' => [
                'required',
                'string',
                'regex:/^[\p{L} ]+$/u',
                'max:50',
                Rule::unique('menu_categories', 'name')
                    ->where(function ($query) {
                        return $query->where('restaurant_id', $this->input('restaurant_id'))
                            ->whereNull('deleted_at');
                    })
                    ->ignore($menuCategoryId),
            ],
            'restaurant_id' => ['required', 'integer', 'exists:restaurants,id'],
            'remark' => ['nullable', 'string', 'max:200'],
            'status' => ['required', 'string', 'max:10'],
        ];

        $rules += [
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date', 'date_format:Y-m-d', 'required_if:repeat,yes', 'after_or_equal:start_date',],
            'start_time' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'end_time' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/', 'after:start_time'],
            'repeat' => ['required', 'string', 'max:64'],
            'repeat_by' => ['nullable', 'string', 'max:64', 'required_if:repeat,yes'],
            'select_day' => [
                'nullable',
                'array',
                'required_if:repeat_by,Weekly',
                'required_if:repeat_by,Biweekly',
            ],
            'select_day.*' => ['string', new \Illuminate\Validation\Rules\Enum(CalendarWeek::class)],
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'restaurant_id.required' => 'The restaurant field is required.',
            'restaurant_id.exists' => 'The selected restaurant is invalid.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $this->all();

            if (empty($data['start_time']) || empty($data['end_time'])) {
                return;
            }

            try {
                $start = CarbonImmutable::createFromFormat('H:i', substr($data['start_time'], 0, 5));
                $end = CarbonImmutable::createFromFormat('H:i', substr($data['end_time'], 0, 5));

                $startMinutes = $start->hour * 60 + $start->minute;
                $endMinutes = $end->hour * 60 + $end->minute;

                if ($endMinutes <= $startMinutes) {
                    $validator->errors()->add('end_time', __('lang.EndTimeMustBeGreater'));
                }
            } catch (\Throwable $e) {
                // Let the base regex rules handle malformed times.
            }
        });
    }
}
