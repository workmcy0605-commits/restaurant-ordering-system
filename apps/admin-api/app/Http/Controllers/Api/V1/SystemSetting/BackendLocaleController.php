<?php

namespace App\Http\Controllers\Api\V1\SystemSetting;

use App\Enums\BackendLocaleType;
use App\Http\Controllers\Api\V1\ApiController;
use App\Models\BackendLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class BackendLocaleController extends ApiController
{
    public function index(Request $request)
    {
        $itemsPerPage = $request->integer('items', 100);

        $locales = BackendLocale::query()
            ->when($request->filled('keyword'), fn ($query) => $query->where('word', 'like', '%'.$request->input('keyword').'%'))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->input('type')))
            ->when($request->filled('en'), fn ($query) => $query->where('en', 'like', '%'.$request->input('en').'%'))
            ->when($request->filled('zh'), fn ($query) => $query->where('zh', 'like', '%'.$request->input('zh').'%'))
            ->latest('id')
            ->paginate($itemsPerPage)
            ->withQueryString();

        return $this->paginated($locales, 'Backend locales retrieved successfully.');
    }

    public function options()
    {
        return $this->success([
            'types' => collect(BackendLocaleType::cases())->map(fn ($case) => [
                'label' => $case->value,
                'value' => $case->value,
            ])->values()->all(),
        ], 'Backend locale options retrieved successfully.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'word' => [
                'required',
                'max:100',
                Rule::unique('backend_locales')->where('type', $request->input('type')),
            ],
            'type' => ['required'],
            'en' => ['required'],
            'zh' => ['required'],
        ]);

        $backendLocale = BackendLocale::create($validated);
        $this->exportJson();

        return $this->created($backendLocale, 'Backend locale created successfully.');
    }

    public function show(BackendLocale $backendLocale)
    {
        return $this->success($backendLocale, 'Backend locale retrieved successfully.');
    }

    public function update(Request $request, BackendLocale $backendLocale)
    {
        $validated = $request->validate([
            'word' => [
                'required',
                'max:100',
                Rule::unique('backend_locales')->where('type', $request->input('type'))->ignore($backendLocale->id),
            ],
            'type' => ['required'],
            'en' => ['required'],
            'zh' => ['required'],
        ]);

        $backendLocale->update($validated);
        $this->exportJson();

        return $this->success($backendLocale, 'Backend locale updated successfully.');
    }

    private function exportJson(): void
    {
        $locales = BackendLocale::query()->select('word', 'type', 'en', 'zh')->get();
        $langPath = resource_path('lang');

        File::ensureDirectoryExists($langPath);

        $languages = ['en' => [], 'zh' => []];

        foreach ($locales as $translation) {
            $path = explode('.', $translation->word);
            $type = $translation->type ?? 'default';

            foreach (['en', 'zh'] as $lang) {
                if (! isset($languages[$lang][$type]) || ! is_array($languages[$lang][$type])) {
                    $languages[$lang][$type] = [];
                }

                $temp = &$languages[$lang][$type];
                foreach ($path as $segment) {
                    if (! isset($temp[$segment]) || ! is_array($temp[$segment])) {
                        $temp[$segment] = [];
                    }
                    $temp = &$temp[$segment];
                }
                $temp = $translation->{$lang};
                unset($temp);
            }
        }

        foreach ($languages as $lang => $data) {
            File::put($langPath.DIRECTORY_SEPARATOR.$lang.'.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }
}
