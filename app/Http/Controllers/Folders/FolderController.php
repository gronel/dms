<?php

namespace App\Http\Controllers\Folders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Folders\StoreFolderRequest;
use App\Models\Folder;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FolderController extends Controller
{
    public function index(Request $request): Response
    {
        $folders = Folder::query()
            ->where('owner_id', $request->user()->id)
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->withCount('documents')])
            ->withCount('documents')
            ->orderBy('name')
            ->get();

        return Inertia::render('Documents/Index', [
            'folders'   => $folders,
            'documents' => [],
            'folder'    => null,
        ]);
    }

    public function store(StoreFolderRequest $request, AuditLogger $audit): RedirectResponse
    {
        $folder = Folder::create([
            'name'      => $request->validated('name'),
            'parent_id' => $request->validated('parent_id'),
            'owner_id'  => $request->user()->id,
        ]);

        $audit->log($folder, 'create', null, $folder->only(['name', 'parent_id']), "Created folder '{$folder->name}'");

        return to_route('folders.show', $folder)->with('status', 'Folder created.');
    }

    public function show(Request $request, Folder $folder): Response
    {
        $this->authorize('view', $folder);

        $folder->load(['parent', 'children' => fn ($q) => $q->withCount('documents')]);
        $folder->loadCount('documents');

        $documents = $folder->documents()
            ->with('currentVersion', 'owner')
            ->withoutTrashed()
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('Documents/Index', [
            'folders'   => $folder->children,
            'documents' => $documents,
            'folder'    => $folder,
        ]);
    }

    public function update(StoreFolderRequest $request, Folder $folder, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $folder);

        $original = $folder->only(['name', 'parent_id']);
        $folder->update($request->validated());
        $audit->logChange($folder, 'update', $original, $folder->only(['name', 'parent_id']), "Renamed folder to '{$folder->name}'");

        return back()->with('status', 'Folder renamed.');
    }

    public function destroy(Folder $folder, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('delete', $folder);

        $audit->log($folder, 'delete', $folder->only(['name', 'owner_id']), null, "Deleted folder '{$folder->name}'");
        $folder->delete();

        return to_route('documents.index')->with('status', 'Folder deleted.');
    }
}
