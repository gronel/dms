<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $userId = $request->user()->id;

        // IDs of all non-deleted documents owned by this user
        $documentIds = Document::query()
            ->where('owner_id', $userId)
            ->withoutTrashed()
            ->pluck('id');

        // Count by status
        $byStatus = Document::query()
            ->where('owner_id', $userId)
            ->withoutTrashed()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        // Total storage — sum of current-version file sizes
        $totalStorage = DocumentVersion::query()
            ->whereIn('document_id', $documentIds)
            ->where('is_current', true)
            ->sum('size_bytes');

        // Last 8 audit events for this user
        $recentActivity = AuditLog::query()
            ->where('user_id', $userId)
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        // Last 5 updated documents
        $recentDocuments = Document::query()
            ->where('owner_id', $userId)
            ->withoutTrashed()
            ->with('currentVersion')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get(['id', 'ulid', 'title', 'status', 'is_locked', 'updated_at']);

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_documents' => $documentIds->count(),
                'draft'           => (int) ($byStatus->get('draft', 0)),
                'published'       => (int) ($byStatus->get('published', 0)),
                'archived'        => (int) ($byStatus->get('archived', 0)),
                'storage_bytes'   => (int) $totalStorage,
            ],
            'recentActivity'  => $recentActivity,
            'recentDocuments' => $recentDocuments,
        ]);
    }
}
