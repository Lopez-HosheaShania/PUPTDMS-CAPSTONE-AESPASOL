<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\DocumentTemplateRenderer;

class DocumentTemplateController extends Controller
{
    private function templateStats(): array
    {
        $allowedStatuses = ['active', 'archived'];

        return [
            'total' => DocumentTemplate::whereIn('status', $allowedStatuses)->count(),
            'active' => DocumentTemplate::where('status', 'active')->count(),
            'archived' => DocumentTemplate::where('status', 'archived')->count(),
        ];
    }

    private function templatePayload(DocumentTemplate $template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'code' => $template->code,
            'document_type' => $template->document_type,
            'category' => $template->category,
            'status' => $template->status,
            'is_default' => (bool) $template->is_default,
            'notes' => $template->notes,
            'updated_at' => optional($template->updated_at)->format('M d, Y h:i A'),
        ];
    }

    public function index(Request $request)
    {
        if (!session('admin_logged_in')) {
            return redirect('/admin/login');
        }

        $allowedStatuses = ['active', 'archived'];

        $query = DocumentTemplate::query()
            ->whereIn('status', $allowedStatuses)
            ->latest();

        if ($request->filled('document_type')) {
            $query->where('document_type', $request->string('document_type'));
        }

        if ($request->filled('status')) {
            $status = (string) $request->string('status');

            if (in_array($status, $allowedStatuses, true)) {
                $query->where('status', $status);
            }
        }

        if ($request->filled('search')) {
            $search = strtolower(trim($request->search));

            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(code) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(document_type) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(category) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(notes) LIKE ?', ["%{$search}%"]);
            });
        }

        $templates = $query->get();
        $stats = $this->templateStats();

        return view('admin.document-template', compact('templates', 'stats'));
    }

    public function show($id)
    {
        if (!session('admin_logged_in')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $template = DocumentTemplate::findOrFail($id);
        $renderer = app(DocumentTemplateRenderer::class);

        return response()->json([
            'id' => $template->id,
            'name' => $template->name,
            'code' => $template->code,
            'document_type' => $template->document_type,
            'category' => $template->category,
            'engine' => $template->engine,
            'output_format' => $template->output_format,
            'content' => $renderer->renderForPreview($template),
            'paper_size' => $template->paper_size,
            'orientation' => $template->orientation,
            'status' => $template->status,
            'is_default' => (bool) $template->is_default,
            'notes' => $template->notes,
            'created_at' => optional($template->created_at)->format('M d, Y h:i A'),
            'updated_at' => optional($template->updated_at)->format('M d, Y h:i A'),
        ]);
    }

    public function archive(Request $request, $id)
    {
        if (!session('admin_logged_in')) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            return redirect('/admin/login');
        }

        $template = DocumentTemplate::findOrFail($id);

        $template->update([
            'status' => 'archived',
            'is_default' => false,
            'updated_by' => session('admin_id'),
        ]);

        $template->refresh();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Template archived successfully.',
                'template' => $this->templatePayload($template),
                'stats' => $this->templateStats(),
            ]);
        }

        return redirect()
            ->route('admin.document-template')
            ->with('success', 'Template archived successfully.');
    }

    public function activate(Request $request, $id)
    {
        if (!session('admin_logged_in')) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            return redirect('/admin/login');
        }

        $template = DocumentTemplate::findOrFail($id);

        $template->update([
            'status' => 'active',
            'updated_by' => session('admin_id'),
        ]);

        $template->refresh();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Template activated successfully.',
                'template' => $this->templatePayload($template),
                'stats' => $this->templateStats(),
            ]);
        }

        return redirect()
            ->route('admin.document-template')
            ->with('success', 'Template activated successfully.');
    }

    public function setDefault($id)
    {
        if (!session('admin_logged_in')) {
            return redirect('/admin/login');
        }

        $template = DocumentTemplate::findOrFail($id);

        DB::transaction(function () use ($template) {
            DocumentTemplate::where('document_type', $template->document_type)
                ->update(['is_default' => false]);

            $template->update([
                'is_default' => true,
                'status' => 'active',
                'updated_by' => session('admin_id'),
            ]);
        });

        return redirect()
            ->route('admin.document-template')
            ->with('success', 'Default template updated successfully.');
    }
}
