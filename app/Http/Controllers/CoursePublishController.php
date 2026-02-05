<?php

namespace App\Http\Controllers;

use App\Http\Requests\Course\UpdateCourseStatusRequest;
use App\Http\Requests\Course\UpdateCourseVisibilityRequest;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CoursePublishController extends Controller
{
    /**
     * Publish a course.
     */
    public function publish(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('publish', $course);

        // Validate course has content before publishing
        $errors = $this->validateCourseContent($course);
        if (! empty($errors)) {
            return back()->withErrors(['publish' => $errors]);
        }

        $course->publish($request->user());

        return redirect()
            ->route('courses.edit', $course)
            ->with('success', 'Kursus berhasil dipublikasikan.');
    }

    /**
     * Validate course has minimum content required for publishing.
     *
     * @return array<string> Validation error messages
     */
    protected function validateCourseContent(Course $course): array
    {
        $errors = [];

        $sectionCount = $course->sections()->count();
        if ($sectionCount === 0) {
            $errors[] = 'Kursus harus memiliki minimal 1 bagian (section).';
        }

        $lessonCount = $course->lessons()->count();
        if ($lessonCount === 0) {
            $errors[] = 'Kursus harus memiliki minimal 1 pelajaran (lesson).';
        }

        return $errors;
    }

    /**
     * Unpublish a course (set back to draft).
     */
    public function unpublish(Course $course): RedirectResponse
    {
        Gate::authorize('unpublish', $course);

        $course->unpublish();

        return redirect()
            ->route('courses.edit', $course)
            ->with('success', 'Kursus berhasil di-unpublish.');
    }

    /**
     * Archive a course.
     */
    public function archive(Course $course): RedirectResponse
    {
        Gate::authorize('archive', $course);

        $course->archive();

        return redirect()
            ->route('courses.edit', $course)
            ->with('success', 'Kursus berhasil diarsipkan.');
    }

    /**
     * Update course status (LMS Admin only).
     */
    public function updateStatus(UpdateCourseStatusRequest $request, Course $course): RedirectResponse
    {
        $course->updateStatus($request->validated('status'), $request->user());

        return redirect()
            ->route('courses.edit', $course)
            ->with('success', 'Status kursus berhasil diperbarui.');
    }

    /**
     * Update course visibility (LMS Admin only).
     */
    public function updateVisibility(UpdateCourseVisibilityRequest $request, Course $course): RedirectResponse
    {
        $validated = $request->validated();

        $course->update($validated);

        return redirect()
            ->route('courses.edit', $course)
            ->with('success', 'Visibilitas kursus berhasil diperbarui.');
    }
}
