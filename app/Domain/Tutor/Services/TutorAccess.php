<?php

namespace App\Domain\Tutor\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LearningPathCourseProgress;
use App\Models\Lesson;
use App\Models\User;

class TutorAccess
{
    /**
     * Named Learner may read this Lesson body, or an Indonesian error string.
     */
    public function enrollmentForLesson(int $userId, Course $course, Lesson $lesson): Enrollment|string
    {
        $learner = User::query()->find($userId);

        if ($learner === null) {
            return 'Learner tidak ditemukan.';
        }

        $lesson->loadMissing('section');

        if ($lesson->section->course_id !== $course->id) {
            return 'Pelajaran tidak termasuk dalam Course ini.';
        }

        $enrollment = Enrollment::query()
            ->where('user_id', $learner->id)
            ->where('course_id', $course->id)
            ->first();

        if ($enrollment === null || ! $enrollment->canAccessContent()) {
            if ($this->isPathLocked($learner, $course)) {
                return 'Pelajaran masih terkunci pada Learning Path.';
            }

            if ($course->visibility === 'restricted') {
                return 'Learner belum diberi akses ke Course terbatas ini.';
            }

            if ($enrollment !== null && $enrollment->isDropped()) {
                return 'Enrollment sudah dinonaktifkan.';
            }

            return 'Learner tidak terdaftar pada Course ini.';
        }

        return $enrollment;
    }

    /**
     * Locked in every Path that includes this Course for this Learner.
     * An independent Enrollment on an unlocked/non-path Course is not locked.
     */
    public function isPathLocked(User $learner, Course $course): bool
    {
        $progresses = LearningPathCourseProgress::query()
            ->where('course_id', $course->id)
            ->whereHas('enrollment', fn ($query) => $query->where('user_id', $learner->id))
            ->get();

        if ($progresses->isEmpty()) {
            return false;
        }

        return $progresses->every(fn (LearningPathCourseProgress $progress) => $progress->isLocked());
    }
}
