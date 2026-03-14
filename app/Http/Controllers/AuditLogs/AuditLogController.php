<?php

namespace App\Http\Controllers\AuditLogs;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Document;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    /**
     * Global audit log viewer — all events owned by the authenticated user.
     *
     * Query params:
     *   action    – filter by action key
     *   type      – filter by auditable_type short name (document|folder)
     *   per_page  – default 25
     */
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'action'   => ['nullable', 'string', 'max:64'],
            'type'     => ['nullable', 'string', 'in:document,folder'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $userId  = $request->user()->id;
        $action  = $validated['action'] ?? null;
        $type    = $validated['type'] ?? null;
        $perPage = (int) ($validated['per_page'] ?? 25);

        $query = AuditLog::query()
            ->where('user_id', $userId)
            ->with('user')
            ->orderByDesc('created_at');

        if ($action) {
            $query->where('action', $action);
        }

        if ($type === 'document') {
            $query->where('auditable_type', Document::class);
        } elseif ($type === 'folder') {
            $query->where('auditable_type', \App\Models\Folder::class);
        }

        $logs = $query->paginate($perPage)->withQueryString();

        // Distinct action values for the filter dropdown (own logs only)
        $actions = AuditLog::query()
            ->where('user_id', $userId)
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return Inertia::render('AuditLogs/Index', [
            'logs'    => $logs,
            'actions' => $actions,
            'filters' => [
                'action'   => $action,
                'type'     => $type,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Per-document audit trail — shows all events for a specific document.
     */
    public function show(Request $request, Document $document): Response
    {
        $this->authorize('view', $document);

        $logs = AuditLog::query()
            ->where('auditable_type', Document::class)
            ->where('auditable_id', $document->id)
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('AuditLogs/Show', [
            'document' => $document->load('owner', 'folder'),
            'logs'     => $logs,
        ]);
    }
}
