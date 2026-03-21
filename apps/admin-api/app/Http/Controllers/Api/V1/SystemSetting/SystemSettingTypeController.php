<?php

namespace App\Http\Controllers\Api\V1\SystemSetting;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\SystemSetting;
use App\Models\SystemSettingType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SystemSettingTypeController extends ApiController
{
    public function index(Request $request)
    {
        $itemsPerPage = $request->integer('items', 100);

        $settings = SystemSettingType::query()
            ->with(['createdBy', 'updatedBy'])
            ->when($request->filled('name'), fn ($query) => $query->where('name', 'like', '%'.$request->input('name').'%'))
            ->orderByDesc('id')
            ->paginate($itemsPerPage)
            ->withQueryString();

        return $this->paginated($settings, 'System setting types retrieved successfully.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                Rule::unique('system_setting_types')->where(fn ($query) => $query->whereNull('deleted_at')),
            ],
            'data_type' => ['required', 'string', 'max:20'],
            'is_branchadmin' => ['nullable', 'boolean'],
        ]);

        $actorId = $this->requireActorId();

        $systemSettingType = SystemSettingType::create([
            'name' => $validated['name'],
            'data_type' => $validated['data_type'],
            'is_branchadmin' => (int) ($validated['is_branchadmin'] ?? 0),
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ]);

        $systemSettingType->load(['createdBy', 'updatedBy']);

        return $this->created($systemSettingType, 'System setting type created successfully.');
    }

    public function show(SystemSettingType $systemSettingType)
    {
        $systemSettingType->load(['createdBy', 'updatedBy']);

        return $this->success($systemSettingType, 'System setting type retrieved successfully.');
    }

    public function update(Request $request, SystemSettingType $systemSettingType)
    {
        $validated = $request->validate([
            'name' => [
                'sometimes',
                'required',
                Rule::unique('system_setting_types')->ignore($systemSettingType->id)->where(fn ($query) => $query->whereNull('deleted_at')),
            ],
            'data_type' => ['sometimes', 'required', 'string', 'max:20'],
            'is_branchadmin' => ['sometimes', 'boolean'],
        ]);

        $systemSettingType->update(array_filter([
            'name' => $validated['name'] ?? null,
            'data_type' => $validated['data_type'] ?? null,
            'is_branchadmin' => array_key_exists('is_branchadmin', $validated) ? (int) $validated['is_branchadmin'] : null,
            'updated_by' => $this->requireActorId(),
        ], fn ($value) => $value !== null));

        $systemSettingType->load(['createdBy', 'updatedBy']);

        return $this->success($systemSettingType, 'System setting type updated successfully.');
    }

    public function destroy(SystemSettingType $systemSettingType)
    {
        $exists = SystemSetting::query()
            ->where('system_setting_type_uuid', $systemSettingType->uuid)
            ->exists();

        abort_if($exists, 422, 'System setting type is still in use.');

        $systemSettingType->update(['deleted_by' => $this->requireActorId()]);
        $systemSettingType->delete();

        return $this->deleted('System setting type deleted successfully.');
    }
}
