<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentPermission;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentPermissionController extends Controller
{
    /**
     * List all active permission grants for a document.
     * Only the document owner can manage shares.
     */
    public function index(Document $document): JsonResponse
    {
        $this->authorize('share', $document);

        $permissions = $document->permissions()
            ->with('user:id,name,email', 'grantedBy:id,name')
            ->orderBy('created_at')
            ->get();

        return response()->json($permissions);
    }

    /**
     * Grant a named user access to a document.
     */
    public function store(Request $request, Document $document, AuditLogger $audit): JsonResponse
    {
        $this->authorize('share', $document);

        $validated = $request->validate([
            'email'      => ['required', 'email', 'exists:users,email'],
            'permission' => ['required', Rule::in(['view', 'download', 'edit'])],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $grantee = User::where('email', $validated['email'])->firstOrFail();

        abort_if($grantee->id === $document->owner_id, 422, 'Cannot grant permission to the document owner.');

        $permission = DocumentPermission::updateOrCreate(
            ['document_id' => $document->id, 'user_id' => $grantee->id],
            [
                'granted_by' => $request->user()->id,
                'permission' => $validated['permission'],
                'expires_at' => $validated['expires_at'] ?? null,
            ]
        );

        $audit->log(
            $document,
            'share_grant',
            null,
            ['user' => $grantee->email, 'permission' => $validated['permission']],
            "Granted {$validated['permission']} access to {$grantee->email}",
        );

        return response()->json($permission->load('user:id,name,email', 'grantedBy:id,name'), 201);
    }

    /**
     * Revoke a specific permission grant.
     */
    public function destroy(
        Document $document,
        DocumentPermission $permission,
        AuditLogger $audit
    ): JsonResponse {
        $this->authorize('share', $document);

        abort_if($permission->document_id !== $document->id, 404);

        $auditExtra = ['user_id' => $permission->user_id, 'permission' => $permission->permission];
        $permission->delete();

        $audit->log($document, 'share_revoke', $auditExtra, null, 'Revoked permission grant');

        return response()->json(null, 204);
    }
}
