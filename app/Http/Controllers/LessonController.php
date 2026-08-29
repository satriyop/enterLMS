<?php

namespace App\Http\Controllers;

use App\Domain\Course\Services\LessonViewPresenter;
use App\Domain\Tutor\Services\ConversationService;
use App\Http\Requests\Lesson\StoreLessonRequest;
use App\Http\Requests\Lesson\UpdateLessonRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\Course\CourseShowResource;
use App\Http\Resources\Course\EnrollmentSummaryResource;
use App\Http\Resources\LessonResource;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LessonController extends Controller
{
    public function __construct(
        protected LessonViewPresenter $presenter,
        protected ConversationService $conversations,
    ) {}

    /**
     * Display the specified lesson for enrolled learners.
     */
    public function show(Request $request, Course $course, Lesson $lesson): Response
    {
        $lessonCourse = $lesson->section->course;
        if ($lessonCourse->id !== $course->id) {
            abort(404);
        }

        Gate::authorize('view', $lesson);

        $course->load(['category', 'user', 'sections.lessons']);
        $lesson->load(['section', 'media']);
        $lesson->append('rich_content_html');

        $user = $request->user();
        $enrollment = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        $navigationData = $this->presenter->getLessonViewData($course, $lesson, $enrollment);

        $conversation = null;
        if ($enrollment) {
            $record = $this->conversations->forEnrollmentAndLesson($enrollment, $lesson);
            $conversation = $record
                ? (new ConversationResource($record))->resolve($request)
                : [
                    'id' => null,
                    'can_post' => $enrollment->isActive() || $enrollment->isCompleted(),
                    'turns' => [],
                ];
        }

        return Inertia::render('lessons/Show', [
            'course' => new CourseShowResource($course),
            'lesson' => new LessonResource($lesson),
            'enrollment' => $enrollment ? new EnrollmentSummaryResource($enrollment) : null,
            'conversation' => $conversation,
            ...$navigationData,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(CourseSection $section): Response
    {
        Gate::authorize('create', [Lesson::class, $section]);

        $section->load('course');

        return Inertia::render('lessons/Edit', [
            'section' => [
                'id' => $section->id,
                'title' => $section->title,
                'course' => [
                    'id' => $section->course->id,
                    'title' => $section->course->title,
                ],
            ],
            'lesson' => null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLessonRequest $request, CourseSection $section): RedirectResponse
    {
        $validated = $request->validated();

        $maxOrder = $section->lessons()->max('order') ?? 0;
        $validated['order'] = $maxOrder + 1;

        /** @var Lesson $lesson */
        $lesson = $section->lessons()->create($validated);
        $lesson->updateDurations();

        return redirect()
            ->route('lessons.edit', $lesson)
            ->with('success', 'Pelajaran berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Lesson $lesson): Response
    {
        Gate::authorize('update', $lesson);

        $lesson->load(['section.course', 'media']);

        return Inertia::render('lessons/Edit', [
            'section' => [
                'id' => $lesson->section->id,
                'title' => $lesson->section->title,
                'course' => [
                    'id' => $lesson->section->course->id,
                    'title' => $lesson->section->course->title,
                ],
            ],
            'lesson' => new LessonResource($lesson),
            'tutor_body' => $this->tutorBody($lesson),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLessonRequest $request, Lesson $lesson): RedirectResponse
    {
        $lesson->update($request->validated());
        $lesson->updateDurations();

        return redirect()
            ->route('lessons.edit', $lesson)
            ->with('success', 'Pelajaran berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lesson $lesson): RedirectResponse
    {
        Gate::authorize('delete', $lesson);

        $course = $lesson->section->course;
        $lessonId = $lesson->id;
        $lessonTitle = $lesson->title;

        $lesson->delete();

        return redirect()
            ->route('courses.edit', $course)
            ->with('success', 'Pelajaran berhasil dihapus.');
    }

    /**
     * @return array{
     *     ready: bool,
     *     text: string|null,
     *     capture: list<array{id: int, file_name: string, status: string}>
     * }
     */
    private function tutorBody(Lesson $lesson): array
    {
        $lesson->loadMissing('media');

        return [
            'ready' => $lesson->isBodyReady(),
            'text' => $lesson->content_type === 'document' ? $lesson->readableBody() : null,
            'capture' => $lesson->media
                ->filter(fn (Media $media): bool => $media->is_document)
                ->map(fn (Media $media): array => [
                    'id' => $media->id,
                    'file_name' => $media->file_name,
                    'status' => is_array($media->custom_properties)
                        ? (string) ($media->custom_properties['body_capture'] ?? 'missing')
                        : 'missing',
                ])
                ->values()
                ->all(),
        ];
    }
}
