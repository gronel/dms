<?php

namespace App\Http\Controllers\Tags;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tags\StoreTagRequest;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * Return all tags as JSON (used by the TagPicker component).
     */
    public function index(): JsonResponse
    {
        return response()->json(
            Tag::orderByRaw('is_system DESC, name ASC')->get(['id', 'name', 'slug', 'color', 'is_system'])
        );
    }

    /**
     * Create a new user-defined tag.
     */
    public function store(StoreTagRequest $request): JsonResponse
    {
        $tag = Tag::create([
            'name'      => $request->validated('name'),
            'color'     => $request->validated('color') ?? '#6b7280',
            'is_system' => false,
        ]);

        return response()->json($tag, 201);
    }

    /**
     * Delete a user-defined tag (system tags are protected).
     */
    public function destroy(Tag $tag): RedirectResponse|JsonResponse
    {
        abort_if($tag->is_system, 403, 'System tags cannot be deleted.');

        $tag->delete();

        return response()->json(null, 204);
    }
}
