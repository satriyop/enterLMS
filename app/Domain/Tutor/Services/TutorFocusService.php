<?php

namespace App\Domain\Tutor\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\TutorFocus;
use App\Models\User;

class TutorFocusService
{
    public function __construct(
        protected TutorAccess $access,
    ) {}

    /**
     * @return array{
     *     inferred: bool,
     *     enrollment_id: int,
     *     course_id: int,
     *     lesson_id: int,
     *     title: string
     * }|null
     */
    public function current(User $learner, string $skin): ?array
    {
        $stored = TutorFocus::query()
            ->with(['lesson', 'enrollment.course'])
            ->where('user_id', $learner->id)
            ->where('skin', $skin)
            ->first();

        if ($stored instanceof TutorFocus) {
            $payload = $this->payloadIfAllowed($learner, $stored->enrollment, $stored->lesson);
            if ($payload !== null) {
                $payload['inferred'] = false;

                return $payload;
            }
        }

        $inferred = $this->inferFromOverlay($learner);

        if ($inferred !== null) {
            $inferred['inferred'] = true;
        }

        return $inferred;
    }

    public function set(User $learner, string $skin, Course $course, Lesson $lesson): TutorFocus|string
    {
        if (! $course->isPublished()) {
            return 'Kursus belum dipublikasikan.';
        }

        $enrollment = $this->access->enrollmentForLesson($learner->id, $course, $lesson);

        if (! $enrollment instanceof Enrollment) {
            return $enrollment;
        }

        return TutorFocus::query()->updateOrCreate(
            [
                'user_id' => $learner->id,
                'skin' => $skin,
            ],
            [
                'enrollment_id' => $enrollment->id,
                'lesson_id' => $lesson->id,
            ],
        );
    }

    /**
     * Titles only — never Lesson bodies.
     *
     * @return list<array{course_id: int, course_title: string, enrollment_id: int, lessons: list<array{id: int, title: string, order: int}>}>
     */
    public function listFocusable(User $learner): array
    {
        $enrollments = Enrollment::query()
            ->with([
                'course.sections' => fn ($query) => $query->orderBy('order'),
                'course.sections.lessons' => fn ($query) => $query->orderBy('order'),
            ])
            ->where('user_id', $learner->id)
            ->get();

        $catalog = [];

        foreach ($enrollments as $enrollment) {
            if (! $enrollment->canAccessContent()) {
                continue;
            }

            $course = $enrollment->course;

            if (! $course->isPublished() || $this->access->isPathLocked($learner, $course)) {
                continue;
            }

            $catalog[] = [
                'course_id' => $course->id,
                'course_title' => $course->title,
                'enrollment_id' => $enrollment->id,
                'lessons' => $course->sections->flatMap(
                    fn ($section) => $section->lessons->map(fn (Lesson $lesson) => [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'order' => $lesson->order,
                    ])
                )->values()->all(),
            ];
        }

        return $catalog;
    }

    /**
     * @return array{enrollment_id: int, course_id: int, lesson_id: int, title: string}|null
     */
    private function inferFromOverlay(User $learner): ?array
    {
        $enrollments = Enrollment::query()
            ->with('course')
            ->where('user_id', $learner->id)
            ->whereNotNull('last_lesson_id')
            ->orderByDesc('updated_at')
            ->get();

        foreach ($enrollments as $enrollment) {
            $lesson = Lesson::query()->find($enrollment->last_lesson_id);

            if ($lesson === null) {
                continue;
            }

            $payload = $this->payloadIfAllowed($learner, $enrollment, $lesson);

            if ($payload !== null) {
                return $payload;
            }
        }

        return null;
    }

    /**
     * @return array{enrollment_id: int, course_id: int, lesson_id: int, title: string}|null
     */
    private function payloadIfAllowed(User $learner, Enrollment $enrollment, Lesson $lesson): ?array
    {
        $course = $enrollment->course;

        if (! $course instanceof Course || ! $course->isPublished()) {
            return null;
        }

        $access = $this->access->enrollmentForLesson($learner->id, $course, $lesson);

        if (! $access instanceof Enrollment) {
            return null;
        }

        return [
            'enrollment_id' => $access->id,
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'title' => $lesson->title,
        ];
    }
}
