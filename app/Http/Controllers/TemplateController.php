<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateReport;
use App\Models\Import;
use App\Models\Report;
use App\Models\ReportTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TemplateController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $templates = ReportTemplate::where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('templates.index', compact('templates'));
    }

    public function create(Request $request): View
    {
        $imports = Import::where('user_id', auth()->id())
            ->where('status', 'success')
            ->latest()
            ->get(['id', 'file_name']);

        $sourceImport = null;
        $columns = [];
        if ($request->filled('source_import_id')) {
            $sourceImport = Import::where('user_id', auth()->id())
                ->find($request->integer('source_import_id'));
            $columns = $sourceImport?->availableColumns() ?? [];
        }

        return view('templates.create', compact('imports', 'sourceImport', 'columns'));
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
            'is_default' => 'nullable|boolean',
        ]);

        $tableLayout = null;
        if (! empty($validated['table_layout'])) {
            $decoded = json_decode($validated['table_layout'], true);
            if (is_array($decoded)) {
                $tableLayout = $decoded;
            }
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
            ]);
        });

        return redirect()->route('templates.index')
            ->with('status', 'Template "'.$template->name.'" berhasil dibuat.');
    }

    public function edit(ReportTemplate $template): View
    {
        $this->authorize('update', $template);

        $imports = Import::where('user_id', auth()->id())
            ->where('status', 'success')
            ->latest()
            ->get(['id', 'file_name']);

        $columns = $template->fields_json ?? [];

        return view('templates.edit', compact('template', 'imports', 'columns'));
    }

    public function update(Request $request, ReportTemplate $template): RedirectResponse
    {
        $this->authorize('update', $template);

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
            'is_default' => 'nullable|boolean',
        ]);

        $tableLayout = null;
        if (! empty($validated['table_layout'])) {
            $decoded = json_decode($validated['table_layout'], true);
            if (is_array($decoded)) {
                $tableLayout = $decoded;
            }
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

        return redirect()->route('templates.index')
            ->with('status', 'Template berhasil diubah.');
    }

    public function destroy(ReportTemplate $template): RedirectResponse
    {
        $this->authorize('delete', $template);

        $template->delete();

        return redirect()->route('templates.index')
            ->with('status', 'Template berhasil dihapus.');
    }

    public function setDefault(ReportTemplate $template): RedirectResponse
    {
        $this->authorize('update', $template);

        DB::transaction(function () use ($template) {
            $this->clearDefaults($template->id);
            $template->update(['is_default' => true]);
        });

        return redirect()->route('templates.index')
            ->with('status', '"'.$template->name.'" dijadikan template default.');
    }

    public function use(ReportTemplate $template): View
    {
        $this->authorize('view', $template);

        $imports = Import::where('user_id', auth()->id())
            ->where('status', 'success')
            ->latest()
            ->get(['id', 'file_name']);

        return view('templates.use', compact('template', 'imports'));
    }

    public function apply(Request $request, ReportTemplate $template): RedirectResponse
    {
        $this->authorize('view', $template);

        $validated = $request->validate([
            'import_id' => 'required|integer',
            'title' => 'required|string|max:255',
        ]);

        $import = Import::where('user_id', auth()->id())->findOrFail($validated['import_id']);

        $available = $import->availableColumns();
        $fields = array_values(array_intersect($template->fields_json ?? [], $available));

        if ($fields === []) {
            $fields = $available;
        }

        if ($fields === [] && $template->output_format !== 'print') {
            return back()->withErrors(['import_id' => 'Template tidak cocok dengan kolom file ini.']);
        }

        $filters = $template->filters_json ?? [];
        $sorting = $template->sorting_json ?? [];
        $layout = $template->layout_json ?? [];
        $user = $request->user();

        $report = Report::create([
            'user_id' => auth()->id(),
            'report_template_id' => $template->id,
            'import_id' => $import->id,
            'title' => $validated['title'],
            'output_format' => $template->output_format,
            'config_json' => array_merge([
                'fields' => $fields,
                'search' => $filters['search'] ?? null,
                'filter_column' => $filters['filter_column'] ?? null,
                'filter_value' => $filters['filter_value'] ?? null,
                'sort_column' => $sorting['column'] ?? null,
                'sort_direction' => $sorting['direction'] ?? 'asc',
                'orientation' => $layout['orientation'] ?? 'portrait',
                'kecamatan' => $user->kecamatan ?? null,
                'nama_kepala_kua' => $user->nama_kepala_kua ?? null,
                'nip_kepala' => $user->nip_kepala ?? null,
            ], ! empty($layout['table_layout']) ? ['table_layout' => $layout['table_layout']] : []),
            'status' => $template->output_format === 'print' ? 'generated' : 'pending',
            'generated_at' => $template->output_format === 'print' ? now() : null,
        ]);

        if ($template->output_format === 'print') {
            return redirect()->route('reports.print', $report);
        }

        GenerateReport::dispatch($report);

        return redirect()->route('reports.index')
            ->with('status', 'Laporan dari template "'.$template->name.'" sedang dibuat.');
    }

    private function clearDefaults(?int $exceptId = null): void
    {
        ReportTemplate::where('user_id', auth()->id())
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->update(['is_default' => false]);
    }
}
