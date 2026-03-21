<?php

namespace App\Http\Controllers\Api\V1\MenuManagement;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\MenuServedDate;
use Illuminate\Http\Request;

class MenuServedDateController extends ApiController
{
    public function destroy(MenuServedDate $menuServedDate)
    {
        $actorId = $this->requireActorId();
        $menuServedDate->update([
            'deleted_by' => $actorId,
            'updated_by' => $actorId,
        ]);
        $menuServedDate->delete();

        return $this->deleted('Menu served date deleted successfully.');
    }

    public function destroyByRequest(Request $request)
    {
        $validated = $request->validate([
            'delete_id' => ['required', 'integer', 'exists:menu_served_dates,id'],
        ]);

        $menuServedDate = MenuServedDate::query()->findOrFail($validated['delete_id']);

        return $this->destroy($menuServedDate);
    }
}
