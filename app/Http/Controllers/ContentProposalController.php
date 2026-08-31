<?php

namespace App\Http\Controllers;

use App\Domain\Content\Services\ContentProposalService;
use App\Http\Requests\ContentProposal\AskContentProposalRequest;
use App\Models\ContentProposal;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class ContentProposalController extends Controller
{
    public function __construct(
        protected ContentProposalService $proposals,
    ) {}

    public function store(AskContentProposalRequest $request, Course $course): RedirectResponse
    {
        $lesson = Lesson::query()->findOrFail($request->integer('lesson_id'));

        try {
            $this->proposals->ask(
                $request->user(),
                $course,
                $lesson,
                $request->string('instruction')->toString(),
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $this->userMessage($e));
        }

        return back()->with('success', 'Usulan konten siap ditinjau.');
    }

    public function accept(Course $course, ContentProposal $contentProposal): RedirectResponse
    {
        $this->assertOnCourse($course, $contentProposal);
        Gate::authorize('accept', $contentProposal);

        try {
            $contentProposal->accept($this->requestUser());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Usulan konten diterima. Lesson diperbarui.');
    }

    public function reject(Course $course, ContentProposal $contentProposal): RedirectResponse
    {
        $this->assertOnCourse($course, $contentProposal);
        Gate::authorize('reject', $contentProposal);

        try {
            $contentProposal->reject($this->requestUser());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Usulan konten ditolak. Lesson tidak berubah.');
    }

    private function assertOnCourse(Course $course, ContentProposal $proposal): void
    {
        if ($proposal->course_id !== $course->id) {
            abort(404);
        }
    }

    private function requestUser(): \App\Models\User
    {
        /** @var \App\Models\User $user */
        $user = request()->user();

        return $user;
    }

    private function userMessage(RuntimeException $e): string
    {
        $message = $e->getMessage();

        if ($message === 'Author runtime failed.') {
            return 'Author Agent gagal menyusun usulan. Coba lagi.';
        }

        if ($message === 'Author runtime is not configured.') {
            return 'Author Agent belum terhubung.';
        }

        return $message;
    }
}
