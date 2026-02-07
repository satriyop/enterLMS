<?php

namespace App\Http\Controllers;

use App\Domain\QuestionBank\DTOs\QuestionBankItemData;
use App\Domain\QuestionBank\Services\QuestionBankService;
use App\Http\Requests\QuestionBank\StoreQuestionBankItemRequest;
use App\Http\Requests\QuestionBank\UpdateQuestionBankItemRequest;
use App\Http\Resources\QuestionBank\QuestionBankItemResource;
use App\Models\QuestionBankItem;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class QuestionBankController extends Controller
{
    public function __construct(
        private readonly QuestionBankService $service,
    ) {}

    /**
     * Display a listing of question bank items.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', QuestionBankItem::class);

        $user = $request->user();

        $query = QuestionBankItem::query()
            ->visibleTo($user)
            ->with(['user', 'tags'])
            ->withCount('options');

        // Apply filters
        if ($type = $request->input('type')) {
            $query->ofType($type);
        }

        if ($difficulty = $request->input('difficulty')) {
            $query->ofDifficulty($difficulty);
        }

        if ($tagId = $request->input('tag_id')) {
            $query->withTag((int) $tagId);
        }

        if ($visibility = $request->input('visibility')) {
            $query->where('visibility', $visibility);
        }

        if ($search = $request->input('search')) {
            $query->search($search);
        }

        // Owner filter
        if ($request->input('mine')) {
            $query->where('user_id', $user->id);
        }

        $items = $query->latest()->paginate(15)->withQueryString();
        $tags = Tag::orderBy('name')->get(['id', 'name', 'slug']);

        return Inertia::render('question-bank/Index', [
            'items' => QuestionBankItemResource::collection($items),
            'tags' => $tags,
            'filters' => $request->only(['search', 'type', 'difficulty', 'visibility', 'tag_id', 'mine']),
        ]);
    }

    /**
     * Show the form for creating a new question bank item.
     */
    public function create(): Response
    {
        Gate::authorize('create', QuestionBankItem::class);

        $tags = Tag::orderBy('name')->get(['id', 'name', 'slug']);

        return Inertia::render('question-bank/Create', [
            'tags' => $tags,
        ]);
    }

    /**
     * Store a newly created question bank item.
     */
    public function store(StoreQuestionBankItemRequest $request): RedirectResponse
    {
        $data = QuestionBankItemData::fromArray([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        $item = $this->service->create($data);

        return redirect()
            ->route('question-bank.show', $item)
            ->with('success', 'Pertanyaan bank berhasil dibuat.');
    }

    /**
     * Display the specified question bank item.
     */
    public function show(QuestionBankItem $questionBank): Response
    {
        Gate::authorize('view', $questionBank);

        $questionBank->load(['user', 'tags', 'options']);
        $questionBank->loadCount('derivedQuestions');

        return Inertia::render('question-bank/Show', [
            'item' => new QuestionBankItemResource($questionBank),
            'can' => [
                'update' => Gate::allows('update', $questionBank),
                'delete' => Gate::allows('delete', $questionBank),
                'duplicate' => Gate::allows('duplicate', $questionBank),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified question bank item.
     */
    public function edit(QuestionBankItem $questionBank): Response
    {
        Gate::authorize('update', $questionBank);

        $questionBank->load(['tags', 'options']);
        $tags = Tag::orderBy('name')->get(['id', 'name', 'slug']);

        return Inertia::render('question-bank/Edit', [
            'item' => new QuestionBankItemResource($questionBank),
            'tags' => $tags,
        ]);
    }

    /**
     * Update the specified question bank item.
     */
    public function update(UpdateQuestionBankItemRequest $request, QuestionBankItem $questionBank): RedirectResponse
    {
        $data = QuestionBankItemData::fromArray([
            ...$request->validated(),
            'user_id' => $questionBank->user_id,
        ]);

        $this->service->update($questionBank, $data);

        return redirect()
            ->route('question-bank.show', $questionBank)
            ->with('success', 'Pertanyaan bank berhasil diperbarui.');
    }

    /**
     * Remove the specified question bank item.
     */
    public function destroy(QuestionBankItem $questionBank): RedirectResponse
    {
        Gate::authorize('delete', $questionBank);

        $this->service->delete($questionBank);

        return redirect()
            ->route('question-bank.index')
            ->with('success', 'Pertanyaan bank berhasil dihapus.');
    }

    /**
     * Duplicate a question bank item.
     */
    public function duplicate(Request $request, QuestionBankItem $questionBank): RedirectResponse
    {
        Gate::authorize('duplicate', $questionBank);

        $duplicate = $this->service->duplicate($questionBank, $request->user()->id);

        return redirect()
            ->route('question-bank.edit', $duplicate)
            ->with('success', 'Pertanyaan bank berhasil diduplikasi.');
    }
}
