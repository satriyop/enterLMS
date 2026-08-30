<?php

namespace App\Http\Controllers;

use App\Domain\Tutor\Services\ConversationService;
use App\Http\Requests\Conversation\StoreConversationTurnRequest;
use App\Http\Resources\ConversationResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class ConversationTurnController extends Controller
{
    public function __construct(
        protected ConversationService $conversations,
    ) {}

    public function show(Request $request, Course $course, Lesson $lesson): Response
    {
        $this->assertLessonOnCourse($course, $lesson);

        $enrollment = $this->enrollmentFor($request, $course);

        if ($enrollment === null) {
            abort(403);
        }

        $conversation = $this->conversations->forEnrollmentAndLesson($enrollment, $lesson);

        if ($conversation === null) {
            abort(404);
        }

        Gate::authorize('view', $conversation);

        return response()->json((new ConversationResource($conversation))->resolve($request));
    }

    public function store(StoreConversationTurnRequest $request, Course $course, Lesson $lesson): RedirectResponse
    {
        $this->assertLessonOnCourse($course, $lesson);

        $enrollment = $request->enrollment();

        if ($enrollment === null) {
            abort(403);
        }

        try {
            $this->conversations->postTurn(
                $enrollment,
                $lesson,
                $request->validated('message'),
            );
        } catch (RuntimeException) {
            return back()->withErrors([
                'message' => 'Tutor sedang tidak dapat menjawab. Silakan coba lagi.',
            ]);
        }

        return redirect()
            ->route('courses.lessons.show', [$course, $lesson])
            ->with('success', 'Pesan terkirim.');
    }

    private function assertLessonOnCourse(Course $course, Lesson $lesson): void
    {
        if ($lesson->section->course->id !== $course->id) {
            abort(404);
        }
    }

    private function enrollmentFor(Request $request, Course $course): ?Enrollment
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        $requestedEnrollmentId = $request->integer('enrollment_id') ?: null;

        if ($requestedEnrollmentId && ($user->canManageCourses() || $user->facilitatedOfferings()->exists())) {
            $enrollment = Enrollment::query()
                ->where('id', $requestedEnrollmentId)
                ->where('course_id', $course->id)
                ->first();

            if ($enrollment === null) {
                return null;
            }

            if ($user->canManageCourses() || $user->facilitatesEnrollment($enrollment)) {
                return $enrollment;
            }

            return null;
        }

        return Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();
    }
}
