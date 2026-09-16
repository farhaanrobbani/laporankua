<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Import;
use App\Models\ReportTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TemplateController extends Controller
{
    public function index(): View
    {
        $templates = ReportTemplate::where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('admin.templates.index', compact('templates'));
    }

    public function create(Request $request): View
    {
        $imports = Import::where('user_id', auth()->id())
            ->where('status', 'success')
            ->latest()
            ->get(['id', 'file_name', 'table_name']);

        $sourceImport = null;
        $columns = [];
        if ($request->filled('source_import_id')) {
            $sourceImport = Import::where('user_id', auth()->id())
                ->find($request->integer('source_import_id'));
            $columns = $sourceImport?->availableColumns() ?? [];
        }

        return view('admin.templates.create', compact('imports', 'sourceImport', 'columns'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'output_format' => 'required|in:pdf,word,excel,print',
            'fields' => 'nullable|array',
            'fields.*' => 'string|max:255',
            'search' => 'nullable|string|max:255',
            'filter_column' => 'nullable|string|max:255',
            'filter_value' => 'nullable|string|max:255',
            'sort_column' => 'nullable|string|max:255',
            'sort_direction' => 'nullable|in:asc,desc',
            'orientation' => 'nullable|in:portrait,landscape',
            'table_layout' => 'nullable|string',
            'static_values' => 'nullable|string',
            'is_default' => 'nullable|boolean',
        ]);

        $tableLayout = null;
        if (! empty($validated['table_layout'])) {
            $decoded = json_decode($validated['table_layout'], true);
            if (is_array($decoded)) {
                $tableLayout = $decoded;
            }
        }

        $staticValues = null;
        if (! empty($validated['static_values'])) {
            $staticValues = array_values(array_filter(array_map('trim', explode("\n", $validated['static_values']))));
        }

        if ($staticValues && $tableLayout && isset($tableLayout['aggregation'])) {
            $tableLayout['aggregation']['static_values'] = $staticValues;
        }

        $template = DB::transaction(function () use ($validated, $tableLayout) {
            if (! empty($validated['is_default'])) {
                $this->clearDefaults();
            }

            return ReportTemplate::create([
                'user_id' => auth()->id(),
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'output_format' => $validated['output_format'],
                'fields_json' => array_values($validated['fields'] ?? []),
                'filters_json' => [
                    'search' => $validated['search'] ?? null,
                    'filter_column' => $validated['filter_column'] ?? null,
                    'filter_value' => $validated['filter_value'] ?? null,
                ],
                'sorting_json' => [
                    'column' => $validated['sort_column'] ?? null,
                    'direction' => $validated['sort_direction'] ?? 'asc',
                ],
                'layout_json' => array_merge([
                    'orientation' => $validated['orientation'] ?? 'portrait',
                ], $tableLayout ? ['table_layout' => $tableLayout] : []),
                'is_default' => ! empty($validated['is_default']),
                'is_global' => true,
            ]);
        });

        return redirect()->route('admin.templates.index')
            ->with('success', 'Template "'.$template->name.'" berhasil dibuat.');
    }

    public function edit(ReportTemplate $template): View
    {
        $imports = Import::where('user_id', auth()->id())
            ->where('status', 'success')
            ->latest()
            ->get(['id', 'file_name', 'table_name']);

        $columns = $template->fields_json ?? [];

        return view('admin.templates.edit', compact('template', 'imports', 'columns'));
    }

    public function update(Request $request, ReportTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'output_format' => 'required|in:pdf,word,excel,print',
            'fields' => 'nullable|array',
            'fields.*' => 'string|max:255',
            'search' => 'nullable|string|max:255',
            'filter_column' => 'nullable|string|max:255',
            'filter_value' => 'nullable|string|max:255',
            'sort_column' => 'nullable|string|max:255',
            'sort_direction' => 'nullable|in:asc,desc',
            'orientation' => 'nullable|in:portrait,landscape',
            'table_layout' => 'nullable|string',
            'static_values' => 'nullable|string',
            'is_default' => 'nullable|boolean',
        ]);

        $tableLayout = null;
        if (! empty($validated['table_layout'])) {
            $decoded = json_decode($validated['table_layout'], true);
            if (is_array($decoded)) {
                $tableLayout = $decoded;
            }
        }

        $staticValues = null;
        if (! empty($validated['static_values'])) {
            $staticValues = array_values(array_filter(array_map('trim', explode("\n", $validated['static_values']))));
        }

        if ($staticValues && $tableLayout && isset($tableLayout['aggregation'])) {
            $tableLayout['aggregation']['static_values'] = $staticValues;
        }

        DB::transaction(function () use ($template, $validated, $tableLayout) {
            if (! empty($validated['is_default'])) {
                $this->clearDefaults($template->id);
            }

            $template->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'output_format' => $validated['output_format'],
                'fields_json' => array_values($validated['fields'] ?? []),
                'filters_json' => [
                    'search' => $validated['search'] ?? null,
                    'filter_column' => $validated['filter_column'] ?? null,
                    'filter_value' => $validated['filter_value'] ?? null,
                ],
                'sorting_json' => [
                    'column' => $validated['sort_column'] ?? null,
                    'direction' => $validated['sort_direction'] ?? 'asc',
                ],
                'layout_json' => array_merge([
                    'orientation' => $validated['orientation'] ?? 'portrait',
                ], $tableLayout ? ['table_layout' => $tableLayout] : []),
                'is_default' => ! empty($validated['is_default']),
            ]);
        });

        return redirect()->route('admin.templates.index')
            ->with('success', 'Template berhasil diubah.');
    }

    public function destroy(ReportTemplate $template): RedirectResponse
    {
        $template->delete();

        return redirect()->route('admin.templates.index')
            ->with('success', 'Template berhasil dihapus.');
    }

    public function setDefault(ReportTemplate $template): RedirectResponse
    {
        DB::transaction(function () use ($template) {
            $this->clearDefaults($template->id);
            $template->update(['is_default' => true]);
        });

        return redirect()->route('admin.templates.index')
            ->with('success', '"'.$template->name.'" dijadikan template default.');
    }

    private function clearDefaults(?int $exceptId = null): void
    {
        ReportTemplate::where('user_id', auth()->id())
            ->where('is_global', true)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->update(['is_default' => false]);
    }
}
