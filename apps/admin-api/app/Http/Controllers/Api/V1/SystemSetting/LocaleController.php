<?php

namespace App\Http\Controllers\Api\V1\SystemSetting;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Locale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class LocaleController extends ApiController
{
    public function index(Request $request)
    {
        $itemsPerPage = $request->integer('items', 100);

        $locales = Locale::query()
            ->when($request->filled('keyword'), fn ($query) => $query->where('word', 'like', '%'.$request->input('keyword').'%'))
            ->when($request->filled('en'), fn ($query) => $query->where('en', 'like', '%'.$request->input('en').'%'))
            ->when($request->filled('zh'), fn ($query) => $query->where('zh', 'like', '%'.$request->input('zh').'%'))
            ->orderByDesc('id')
            ->paginate($itemsPerPage)
            ->withQueryString();

        return $this->paginated($locales, 'Locales retrieved successfully.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'word' => ['required', 'max:100'],
            'en' => ['required'],
            'zh' => ['required'],
        ]);

        $locale = Locale::create($validated);
        $this->exportLanguageFiles();

        return $this->created($locale, 'Locale created successfully.');
    }

    public function show(Locale $locale)
    {
        return $this->success($locale, 'Locale retrieved successfully.');
    }

    public function update(Request $request, Locale $locale)
    {
        $validated = $request->validate([
            'word' => ['required', 'max:100', 'unique:locales,word,'.$locale->id],
            'en' => ['sometimes', 'required'],
            'zh' => ['sometimes', 'required'],
        ]);

        $locale->update($validated);
        $this->exportLanguageFiles();

        return $this->success($locale, 'Locale updated successfully.');
    }

    private function exportLanguageFiles(): void
    {
        $translations = Locale::query()->select('word', 'en', 'zh')->get();
        $langPath = resource_path('lang');

        File::ensureDirectoryExists($langPath.DIRECTORY_SEPARATOR.'en');
        File::ensureDirectoryExists($langPath.DIRECTORY_SEPARATOR.'zh');

        $english = [];
        $chinese = [];

        foreach ($translations as $translation) {
            $english[$translation->word] = $translation->en;
            $chinese[$translation->word] = $translation->zh;
        }

        File::put($langPath.DIRECTORY_SEPARATOR.'en'.DIRECTORY_SEPARATOR.'lang.php', "<?php\n\nreturn ".var_export($english, true).";\n");
        File::put($langPath.DIRECTORY_SEPARATOR.'zh'.DIRECTORY_SEPARATOR.'lang.php', "<?php\n\nreturn ".var_export($chinese, true).";\n");
    }
}
