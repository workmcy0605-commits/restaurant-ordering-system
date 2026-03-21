<?php

namespace App\Http\Controllers\Api\V1\SystemSetting;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Audit;
use Illuminate\Http\Request;

class AuditController extends ApiController
{
    public function index(Request $request)
    {
        $itemsPerPage = $request->integer('items', 100);

        $audits = Audit::query()
            ->when($request->filled('auditable_id'), fn ($query) => $query->where('auditable_id', 'like', '%'.$request->input('auditable_id').'%'))
            ->when($request->filled('url'), fn ($query) => $query->where('url', 'like', '%'.$request->input('url').'%'))
            ->orderByDesc('created_at')
            ->paginate($itemsPerPage)
            ->withQueryString();

        return $this->paginated($audits, 'Audits retrieved successfully.');
    }

    public function show(Audit $audit)
    {
        $audit->load('user');

        return $this->success($audit, 'Audit retrieved successfully.');
    }
}
