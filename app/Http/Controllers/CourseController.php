<?php

namespace App\Http\Controllers;

use App\Domain\Enrollment\DTOs\EnrollmentContext;
use App\Domain\Enrollment\Services\EnrollmentService;
use App\Domain\Progress\Services\ProgressTrackingService;
use App\Http\Requests\Course\StoreCourseRequest;
use App\Http\Requests\Course\UpdateCourseRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\Course\CourseEditResource;
use App\Http\Resources\Course\CourseListResource;
use App\Http\Resources\Course\CourseRatingResource;
use App\Http\Resources\Course\CourseShowResource;
use App\Http\Resources\Course\EnrollmentSummaryResource;
use App\Http\Resources\CourseInvitationResource;
use App\Http\Resources\TagResource;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseInvitation;
use App\Models\Enrollment;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function __construct(
        protected ProgressTrackingService $progressService,
        protected EnrollmentService $enrollmentService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Course::class);

        $user = $request->user();

        $query = Course::query()
            ->with(['category', 'user', 'tags'])
            ->withCount(['sections', 'lessons', 'enrollments', 'ratings'])
            ->withAvg('ratings', 'rating');

        if ($user->isLearner()) {
            $query->published()->visible();
        } elseif (! $user->isLmsAdmin()) {
            $query->where('user_id', $user->id);
        }

        // Apply filters using model scopes
        $query->search($request->input('search'))
            ->filterByCategory($request->integer('category_id') ?: null)
            ->filterByDifficulty($request->input('difficulty_level'));

        if (! $user->isLearner() && ($status = $request->input('status'))) {
            $query->where('status', $status);
        }

        // Trashed filter (admin only)
        if ($request->boolean('trashed') && $user->isLmsAdmin()) {
            $query->onlyTrashed();
        }

        $courses = $query->latest()->paginate(12)->withQueryString();

        $viewName = $user->isLearner() ? 'courses/Browse' : 'courses/Index';

        $enrollmentMap = $user->isLearner()
            ? $this->enrollmentService->getEnrollmentMapForCourses($user, $courses)
            : [];

        return Inertia::render($viewName, [
            'courses' => CourseListResource::collection($courses),
            'categories' => CategoryResource::collection(Category::orderBy('name')->get())->resolve(),
            'filters' => $request->only(['search', 'status', 'category_id', 'difficulty_level', 'trashed']),
            'enrollmentMap' => $enrollmentMap,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', Course::class);

        return Inertia::render('courses/Create', [
            'categories' => CategoryResource::collection(Category::orderBy('name')->get())->resolve(),
            'tags' => TagResource::collection(Tag::orderBy('name')->get())->resolve(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['slug'] = Str::slug($validated['title']).'-'.Str::random(6);
        $validated['user_id'] = $request->user()->id;

        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail_path'] = $request->file('thumbnail')
                ->store('courses/thumbnails', 'public');
        }

        $course = Course::create($validated);

        if (isset($validated['tags'])) {
            $course->tags()->sync($validated['tags']);
        }

        return redirect()
            ->route('courses.edit', $course)
            ->with('success', 'Kursus berhasil dibuat. Silakan tambahkan konten.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Course $course): Response
    {
        $user = $request->user();

        $enrollment = Enrollment::query()
            ->forUserAndCourse($user, $course)
            ->first();

        $enrollmentContext = EnrollmentContext::fromData(
            isActivelyEnrolled: $enrollment?->isActive() ?? false,
            hasPendingInvitation: $user->courseInvitations()
                ->where('course_id', $course->id)
                ->where('status', 'pending')
                ->exists(),
            hasAnyEnrollment: $enrollment !== null,
        );

        Gate::authorize('view', [$course, $enrollmentContext]);

        $course->load(['category', 'user', 'tags', 'sections.lessons']);
        $course->loadCount(['lessons', 'enrollments', 'ratings']);
        $course->loadAvg('ratings', 'rating');

        $assessmentStats = $enrollment
            ? $this->progressService->getAssessmentStats($enrollment)->toArray()
            : null;

        $ratings = $course->ratings()
            ->with('user:id,name')
            ->latest()
            ->take(10)
            ->get();

        $viewName = $user->isLearner() ? 'courses/Detail' : 'courses/Show';

        $invitationQuery = CourseInvitation::query()
            ->where('course_id', $course->id);

        $invitationsTotal = $user->isLearner() ? 0 : $invitationQuery->count();

        $invitations = $user->isLearner()
            ? []
            : CourseInvitationResource::collection(
                $invitationQuery->clone()
                    ->with(['user:id,name,email', 'inviter:id,name'])
                    ->latest()
                    ->limit(10)
                    ->get()
            )->resolve();

        $userRating = $user->courseRatings()->where('course_id', $course->id)->first();

        return Inertia::render($viewName, [
            'course' => new CourseShowResource($course),
            'enrollment' => $enrollment ? new EnrollmentSummaryResource($enrollment) : null,
            'isUnderRevision' => $enrollment !== null && $course->isDraft(),
            'assessmentStats' => $assessmentStats,
            'userRating' => $userRating ? new CourseRatingResource($userRating) : null,
            'ratings' => CourseRatingResource::collection($ratings)->resolve(),
            'averageRating' => $course->average_rating,
            'ratingsCount' => $course->ratings_count,
            'invitations' => $invitations,
            'invitationsTotal' => $invitationsTotal,
            'can' => [
                'update' => Gate::allows('update', $course),
                'delete' => Gate::allows('delete', $course),
                'publish' => Gate::allows('publish', $course),
                'enroll' => Gate::allows('enroll', [$course, $enrollmentContext]),
                'rate' => $enrollment && ! $userRating,
                'invite' => Gate::allows('create', [CourseInvitation::class, $course]),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Course $course): Response
    {
        Gate::authorize('update', $course);

        $course->load(['category', 'tags', 'sections.lessons']);

        $activeEnrollmentsCount = $course->enrollments()->where('status', 'active')->count();

        return Inertia::render('courses/Edit', [
            'course' => new CourseEditResource($course),
            'categories' => CategoryResource::collection(Category::orderBy('name')->get())->resolve(),
            'tags' => TagResource::collection(Tag::orderBy('name')->get())->resolve(),
            'activeEnrollmentsCount' => $activeEnrollmentsCount,
            'can' => [
                'publish' => Gate::allows('publish', $course),
                'setStatus' => Gate::allows('setStatus', $course),
                'setVisibility' => Gate::allows('setVisibility', $course),
                'delete' => Gate::allows('delete', $course),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('thumbnail')) {
            if ($course->thumbnail_path) {
                Storage::disk('public')->delete($course->thumbnail_path);
            }
            $validated['thumbnail_path'] = $request->file('thumbnail')
                ->store('courses/thumbnails', 'public');
        }

        $course->update($validated);

        if (isset($validated['tags'])) {
            $course->tags()->sync($validated['tags']);
        }

        return redirect()
            ->route('courses.edit', $course)
            ->with('success', 'Kursus berhasil diperbarui.');
    }

    /**
     * Soft-delete the specified resource.
     */
    public function destroy(Course $course): RedirectResponse
    {
        Gate::authorize('delete', $course);

        $course->delete();

        return redirect()
            ->route('courses.index')
            ->with('success', 'Kursus berhasil dihapus. Anda dapat memulihkannya dari tempat sampah.');
    }

    /**
     * Restore a soft-deleted course.
     */
    public function restore(Course $course): RedirectResponse
    {
        Gate::authorize('restore', $course);

        $course->restore();

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Kursus berhasil dipulihkan.');
    }

    /**
     * Permanently delete a course and its thumbnail.
     */
    public function forceDelete(Course $course): RedirectResponse
    {
        Gate::authorize('forceDelete', $course);

        if ($course->thumbnail_path) {
            Storage::disk('public')->delete($course->thumbnail_path);
        }

        $course->forceDelete();

        return redirect()
            ->route('courses.index')
            ->with('success', 'Kursus berhasil dihapus secara permanen.');
    }
}
