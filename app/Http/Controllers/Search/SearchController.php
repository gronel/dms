<?php

namespace App\Http\Controllers\Search;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    /**
     * Full-text search across documents the authenticated user owns
     * plus all published documents from any user.
     *
     * Query params:
     *   q          – search query (required, min 1 char)
     *   status     – filter by status (draft|published|archived)
     *   tag_ids[]  – filter by tag IDs (array)
     *   per_page   – results per page (default 15, max 50)
     *   page       – page number
     */
    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'q'          => ['nullable', 'string', 'max:255'],
            'status'     => ['nullable', 'string', 'in:draft,published,archived'],
            'tag_ids'    => ['nullable', 'array'],
            'tag_ids.*'  => ['integer', 'exists:tags,id'],
            'per_page'   => ['nullable', 'integer', 'min:1', 'max:50'],
            'sort'       => ['nullable', 'string', 'in:relevance,title_asc,title_desc,newest,oldest'],
        ]);

        $query    = $validated['q'] ?? '';
        $perPage  = (int) ($validated['per_page'] ?? 15);
        $status   = $validated['status'] ?? null;
        $tagIds   = $validated['tag_ids'] ?? [];
        $sort     = $validated['sort'] ?? 'relevance';

        // Build the Scout search — fall back to Eloquent when query is blank
        // so the page still works before Meilisearch is set up.
        if ($query !== '') {
            // Scout search: own documents OR any published document
            $builder = Document::search($query)
                ->query(function ($q) use ($request) {
                    $q->where(fn ($q2) => $q2
                        ->where('owner_id', $request->user()->id)
                        ->orWhere('status', 'published')
                    )->with('currentVersion', 'tags', 'owner', 'folder');
                });

            if ($status) {
                $builder->where('status', $status);
            }

            if (!empty($tagIds)) {
                // Meilisearch filter: tag_ids array must contain ALL requested tags
                foreach ($tagIds as $tagId) {
                    $builder->where('tag_ids', $tagId);
                }
            }

            // Sorting
            match ($sort) {
                'title_asc'  => $builder->orderBy('title', 'asc'),
                'title_desc' => $builder->orderBy('title', 'desc'),
                'newest'     => $builder->orderBy('created_at_timestamp', 'desc'),
                'oldest'     => $builder->orderBy('created_at_timestamp', 'asc'),
                default      => null,  // default Meilisearch relevance
            };

            $paginator = $builder->paginate($perPage)->withQueryString();
        } else {
            // Empty query — show recent documents
            $eloquentQuery = Document::query()
                ->where(fn ($q) => $q
                    ->where('owner_id', $request->user()->id)
                    ->orWhere('status', 'published')
                )
                ->with('currentVersion', 'tags', 'owner', 'folder')
                ->withoutTrashed();

            if ($status) {
                $eloquentQuery->where('status', $status);
            }

            if (!empty($tagIds)) {
                $eloquentQuery->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds));
            }

            match ($sort) {
                'title_asc'  => $eloquentQuery->orderBy('title'),
                'title_desc' => $eloquentQuery->orderByDesc('title'),
                'oldest'     => $eloquentQuery->orderBy('created_at'),
                default      => $eloquentQuery->orderByDesc('updated_at'),
            };

            $paginator = $eloquentQuery->paginate($perPage)->withQueryString();
        }

        $allTags = Tag::orderByRaw('is_system DESC, name ASC')->get(['id', 'name', 'slug', 'color', 'is_system']);

        return Inertia::render('Search/Results', [
            'documents' => $paginator,
            'allTags'   => $allTags,
            'filters'   => [
                'q'       => $query,
                'status'  => $status,
                'tag_ids' => array_map('intval', $tagIds),
                'per_page' => $perPage,
                'sort'    => $sort,
            ],
        ]);
    }
}
