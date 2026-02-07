<?php

namespace App\Http\Controllers;

use App\Domain\QuestionBank\Services\QuestionImportService;
use App\Http\Requests\QuestionBank\ImportQuestionsRequest;
use App\Http\Resources\QuestionBank\QuestionBankItemResource;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\QuestionBankItem;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class QuestionImportController extends Controller
{
    public function __construct(
        private readonly QuestionImportService $importService,
    ) {}

    /**
     * Show the import question bank browser.
     */
    public function index(Request $request, Course $course, Assessment $assessment): Response
    {
        Gate::authorize('update', [$assessment, $course]);

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

        if ($search = $request->input('search')) {
            $query->search($search);
        }

        if ($request->input('mine')) {
            $query->where('user_id', $user->id);
        }

        $items = $query->latest()->paginate(15)->withQueryString();
        $tags = Tag::orderBy('name')->get(['id', 'name', 'slug']);

        return Inertia::render('question-bank/Import', [
            'course' => $course,
            'assessment' => $assessment,
            'items' => QuestionBankItemResource::collection($items),
            'tags' => $tags,
            'filters' => $request->only(['search', 'type', 'difficulty', 'tag_id', 'mine']),
        ]);
    }

    /**
     * Import selected questions to the assessment.
     */
    public function store(ImportQuestionsRequest $request, Course $course, Assessment $assessment): RedirectResponse
    {
        $bankItemIds = $request->validated()['question_bank_item_ids'];

        // Verify user can view all selected items
        $user = $request->user();
        $items = QuestionBankItem::whereIn('id', $bankItemIds)->get();

        foreach ($items as $item) {
            if (! $item->isVisibleTo($user)) {
                return redirect()
                    ->back()
                    ->with('error', 'Anda tidak memiliki akses ke beberapa pertanyaan yang dipilih.');
            }
        }

        $result = $this->importService->importToAssessment($assessment, $bankItemIds);

        if (! $result->wasSuccessful()) {
            return redirect()
                ->back()
                ->with('error', 'Tidak ada pertanyaan yang berhasil diimpor.');
        }

        return redirect()
            ->route('assessments.questions.index', [$course, $assessment])
            ->with('success', "Berhasil mengimpor {$result->imported_count} pertanyaan ({$result->total_points} poin).");
    }
}
