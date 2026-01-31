<?php

namespace App\Http\Controllers;

use App\Http\Requests\Section\ReorderCourseSectionsRequest;
use App\Http\Requests\Section\ReorderSectionLessonsRequest;
use App\Models\Course;
use App\Models\CourseSection;
use Illuminate\Http\JsonResponse;

class CourseReorderController extends Controller
{
    /**
     * Reorder sections within a course.
     */
    public function sections(ReorderCourseSectionsRequest $request, Course $course): JsonResponse
    {
        $validated = $request->validated();

        CourseSection::bulkUpdateOrder($course, $validated['sections']);

        return response()->json([
            'message' => 'Urutan bagian berhasil diperbarui.',
        ]);
    }

    /**
     * Reorder lessons within a section.
     */
    public function lessons(ReorderSectionLessonsRequest $request, CourseSection $section): JsonResponse
    {
        $validated = $request->validated();

        \App\Models\Lesson::bulkUpdateOrder($section, $validated['lessons']);

        return response()->json([
            'message' => 'Urutan pelajaran berhasil diperbarui.',
        ]);
    }
}
