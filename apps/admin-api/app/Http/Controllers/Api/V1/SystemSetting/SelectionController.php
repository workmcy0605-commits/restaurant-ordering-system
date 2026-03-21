<?php

namespace App\Http\Controllers\Api\V1\SystemSetting;

use App\Enums\SelectionType;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Backend\SelectionRequest;
use App\Models\Selection;
use Illuminate\Http\Request;

class SelectionController extends ApiController
{
    public function index(Request $request)
    {
        $itemsPerPage = $request->integer('items', 100);

        $selections = Selection::query()
            ->when($request->filled('value'), fn ($query) => $query->where('value', 'like', '%'.$request->input('value').'%'))
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->input('category')))
            ->orderByDesc('id')
            ->paginate($itemsPerPage)
            ->withQueryString();

        return $this->paginated($selections, 'Selections retrieved successfully.');
    }

    public function options()
    {
        return $this->success([
            'categories' => collect(SelectionType::cases())->map(fn ($case) => [
                'label' => $case->value,
                'value' => $case->value,
            ])->values()->all(),
        ], 'Selection options retrieved successfully.');
    }

    public function store(SelectionRequest $request)
    {
        $actorId = $this->requireActorId();

        $selection = Selection::create($request->validated() + [
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ]);

        return $this->created($selection, 'Selection created successfully.');
    }

    public function show(Selection $selection)
    {
        return $this->success($selection, 'Selection retrieved successfully.');
    }

    public function update(SelectionRequest $request, Selection $selection)
    {
        $selection->update($request->validated() + [
            'updated_by' => $this->requireActorId(),
        ]);

        return $this->success($selection, 'Selection updated successfully.');
    }
}
