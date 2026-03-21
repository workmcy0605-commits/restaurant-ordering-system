<?php

namespace App\Http\Controllers\Api\V1\SystemSetting;

use App\Enums\AccessControlType;
use App\Enums\Status;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Backend\AccessControlRequest;
use App\Models\AccessControl;
use Illuminate\Http\Request;

class AccessControlController extends ApiController
{
    public function index(Request $request)
    {
        $itemsPerPage = $request->integer('items', 100);

        $accessControls = AccessControl::query()
            ->with(['createdBy', 'updatedBy'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('name'), fn ($query) => $query->where('name', 'like', '%'.$request->input('name').'%'))
            ->orderByDesc('id')
            ->paginate($itemsPerPage)
            ->withQueryString();

        return $this->paginated($accessControls, 'Access controls retrieved successfully.');
    }

    public function options()
    {
        return $this->success([
            'types' => collect(AccessControlType::cases())->map(fn ($case) => ['label' => $case->value, 'value' => $case->value])->values()->all(),
            'statuses' => [
                ['label' => Status::ACTIVE->value, 'value' => Status::ACTIVE->value],
                ['label' => Status::INACTIVE->value, 'value' => Status::INACTIVE->value],
            ],
        ], 'Access control options retrieved successfully.');
    }

    public function store(AccessControlRequest $request)
    {
        $accessControl = AccessControl::create($request->validated() + [
            'created_by' => $this->requireActorId(),
            'updated_by' => $this->requireActorId(),
        ]);

        $accessControl->load(['createdBy', 'updatedBy']);

        return $this->created($accessControl, 'Access control created successfully.');
    }

    public function show(AccessControl $accessControl)
    {
        $accessControl->load(['createdBy', 'updatedBy']);

        return $this->success($accessControl, 'Access control retrieved successfully.');
    }

    public function update(AccessControlRequest $request, AccessControl $accessControl)
    {
        $accessControl->update($request->validated() + [
            'updated_by' => $this->requireActorId(),
        ]);

        $accessControl->load(['createdBy', 'updatedBy']);

        return $this->success($accessControl, 'Access control updated successfully.');
    }

    public function destroy(AccessControl $accessControl)
    {
        $accessControl->update(['deleted_by' => $this->requireActorId()]);
        $accessControl->delete();

        return $this->deleted('Access control deleted successfully.');
    }
}
