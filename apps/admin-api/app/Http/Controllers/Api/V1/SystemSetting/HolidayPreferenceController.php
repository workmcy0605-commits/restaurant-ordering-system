<?php

namespace App\Http\Controllers\Api\V1\SystemSetting;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Company;
use Illuminate\Http\Request;

class HolidayPreferenceController extends ApiController
{
    public function toggleWeekend(Request $request)
    {
        return $this->updatePreference($request, 'place_order_weekend', 'Weekend preference updated successfully.');
    }

    public function toggleHoliday(Request $request)
    {
        return $this->updatePreference($request, 'place_order_holiday', 'Holiday preference updated successfully.');
    }

    private function updatePreference(Request $request, string $field, string $message)
    {
        $validated = $request->validate([
            'value' => ['required', 'boolean'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
        ]);

        $companyId = $this->currentUser()?->company_id ?? ($validated['company_id'] ?? null);
        abort_if($companyId === null, 422, 'Company is required to update this preference.');

        $company = Company::query()->findOrFail($companyId);
        $company->update([
            $field => (bool) $validated['value'],
            'updated_by' => $this->requireActorId(),
        ]);

        return $this->success([
            'company_id' => $company->id,
            $field => (bool) $company->{$field},
        ], $message);
    }
}
