<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Http\Resources\NoteResource;
use App\Models\Note;
use App\Services\AI\EmbeddingService;
use App\Services\AI\SummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function __construct(
        private readonly EmbeddingService $embeddingService,
        private readonly SummaryService $summaryService,
    ) {
    }

    /** GET /api/notes?page=1&limit=10 */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('limit', 10), 100);
        $perPage = max($perPage, 1);

        $notes = Note::query()
            ->latest('created_at')
            ->paginate($perPage);

        return NoteResource::collection($notes)->response();
    }

    /** POST /api/notes */
    public function store(StoreNoteRequest $request): JsonResponse
    {
        $note = new Note($request->validated());
        $note->embedding = $this->embeddingService->embed($note->title.' '.$note->content);
        $note->save();

        return (new NoteResource($note))
            ->response()
            ->setStatusCode(201);
    }

    /** GET /api/notes/{note} */
    public function show(Note $note): JsonResponse
    {
        return (new NoteResource($note))->response();
    }

    /** PUT/PATCH /api/notes/{note} */
    public function update(UpdateNoteRequest $request, Note $note): JsonResponse
    {
        $note->fill($request->validated());

        if ($note->isDirty(['title', 'content'])) {
            $note->embedding = $this->embeddingService->embed($note->title.' '.$note->content);
            $note->summary = null;
        }

        $note->save();

        return (new NoteResource($note))->response();
    }

    /** DELETE /api/notes/{note} */
    public function destroy(Note $note): JsonResponse
    {
        $note->delete();

        return response()->json(null, 204);
    }

    /** POST /api/notes/{note}/summary - generates and caches an AI summary. */
    public function summary(Note $note): JsonResponse
    {
        if (is_null($note->summary)) {
            $note->summary = $this->summaryService->summarize($note->title, $note->content);
            $note->save();
        }

        return response()->json([
            'id' => $note->id,
            'summary' => $note->summary,
        ]);
    }

    /** GET /api/notes/search?q=...&limit=10 - ranks notes by embedding similarity. */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:500'],
        ]);

        $limit = min((int) $request->integer('limit', 10), 50);
        $queryVector = $this->embeddingService->embed($request->string('q')->toString());

        $ranked = Note::query()
            ->whereNotNull('embedding')
            ->get()
            ->map(function (Note $note) use ($queryVector) {
                $note->relevance_score = $this->embeddingService->cosineSimilarity($queryVector, $note->embedding);

                return $note;
            })
            ->sortByDesc('relevance_score')
            ->take($limit)
            ->values();

        return NoteResource::collection($ranked)->response();
    }
}
