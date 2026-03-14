<?php

namespace App\Http\Controllers\Documents;

use App\Actions\Documents\UploadDocumentAction;
use App\Actions\Documents\CreateDocumentVersionAction;
use App\Actions\Documents\SyncDocumentTagsAndMetadataAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documents\StoreDocumentRequest;
use App\Http\Requests\Documents\UpdateDocumentRequest;
use App\Models\Document;
use App\Models\Folder;
use App\Models\Tag;
use App\Services\Audit\AuditLogger;
use App\Services\Storage\DocumentStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    public function index(Request $request): Response
    {
        $documents = Document::query()
            ->where('owner_id', $request->user()->id)
            ->whereNull('folder_id')
            ->with('currentVersion', 'owner')
            ->withoutTrashed()
            ->orderByDesc('updated_at')
            ->get();

        $folders = Folder::query()
            ->where('owner_id', $request->user()->id)
            ->whereNull('parent_id')
            ->withCount('documents')
            ->orderBy('name')
            ->get();

        return Inertia::render('Documents/Index', [
            'documents' => $documents,
            'folders'   => $folders,
            'folder'    => null,
        ]);
    }

    public function create(Request $request): Response
    {
        $folders = Folder::query()
            ->where('owner_id', $request->user()->id)
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $allTags = Tag::orderByRaw('is_system DESC, name ASC')->get(['id', 'name', 'slug', 'color', 'is_system']);

        return Inertia::render('Documents/Create', [
            'folders'   => $folders,
            'folder_id' => $request->query('folder_id'),
            'allTags'   => $allTags,
        ]);
    }

    public function store(
        StoreDocumentRequest $request,
        UploadDocumentAction $uploadAction,
        SyncDocumentTagsAndMetadataAction $syncAction,
        AuditLogger $audit
    ): RedirectResponse {
        $document = $uploadAction->execute(
            owner: $request->user(),
            title: $request->validated('title'),
            file: $request->file('file'),
            options: [
                'description'    => $request->validated('description'),
                'folder_id'      => $request->validated('folder_id'),
                'status'         => $request->validated('status'),
                'change_summary' => $request->validated('change_summary') ?? 'Initial upload',
            ],
        );

        $syncAction->execute(
            document: $document,
            tagIds: $request->validated('tags') ?? [],
            metadata: $request->validated('metadata') ?? [],
        );

        $audit->log($document, 'upload', null, $document->only(['title', 'status', 'folder_id']), "Uploaded '{$document->title}'");

        return to_route('documents.show', $document->ulid)->with('status', 'Document uploaded.');
    }

    public function show(Request $request, Document $document): Response
    {
        $this->authorize('view', $document);

        $document->load([
            'owner',
            'folder',
            'currentVersion.uploader',
            'versions.uploader',
            'tags',
            'metadata',
            'comments.user',
        ]);

        return Inertia::render('Documents/Show', [
            'document' => $document,
        ]);
    }

    public function edit(Request $request, Document $document): Response
    {
        $this->authorize('update', $document);

        $folders = Folder::query()
            ->where('owner_id', $request->user()->id)
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $document->load('currentVersion', 'folder', 'tags', 'metadata');

        $allTags = Tag::orderByRaw('is_system DESC, name ASC')->get(['id', 'name', 'slug', 'color', 'is_system']);

        return Inertia::render('Documents/Create', [
            'document'  => $document,
            'folders'   => $folders,
            'folder_id' => $document->folder_id,
            'allTags'   => $allTags,
        ]);
    }

    public function update(
        UpdateDocumentRequest $request,
        Document $document,
        CreateDocumentVersionAction $versionAction,
        SyncDocumentTagsAndMetadataAction $syncAction,
        AuditLogger $audit
    ): RedirectResponse {
        $this->authorize('update', $document);

        $original = $document->only(['title', 'description', 'folder_id', 'status']);

        $document->update([
            'title'       => $request->validated('title'),
            'description' => $request->validated('description'),
            'folder_id'   => $request->validated('folder_id'),
            'status'      => $request->validated('status'),
        ]);

        if ($request->hasFile('file')) {
            $versionAction->execute(
                document: $document,
                file: $request->file('file'),
                uploader: $request->user(),
                changeSummary: $request->validated('change_summary'),
            );
        }

        $syncAction->execute(
            document: $document,
            tagIds: $request->validated('tags') ?? [],
            metadata: $request->validated('metadata') ?? [],
        );

        $audit->logChange(
            $document,
            'update',
            $original,
            $document->only(['title', 'description', 'folder_id', 'status']),
            "Updated '{$document->title}'",
        );

        return to_route('documents.show', $document->ulid)->with('status', 'Document updated.');
    }

    public function destroy(Document $document, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('delete', $document);

        $audit->log($document, 'delete', $document->only(['title', 'status', 'owner_id']), null, "Deleted '{$document->title}'");

        $document->delete();

        return to_route('documents.index')->with('status', 'Document deleted.');
    }
}
