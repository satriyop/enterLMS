<?php

namespace App\Http\Controllers;

use App\Domain\Shared\Academy;
use App\Http\Resources\Dashboard\DashboardCourseResource;
use App\Http\Resources\Dashboard\DashboardEnrollmentResource;
use App\Http\Resources\Dashboard\DashboardFacilitatedOfferingResource;
use App\Http\Resources\Dashboard\DashboardInvitationResource;
use App\Models\Course;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LearnerDashboardController extends Controller
{
    public function __invoke(): Response
    {
        $user = Auth::user();

        // Only learners can access the learner dashboard
        if ($user->role !== 'learner') {
            abort(403);
        }

        $featuredCourses = Academy::enabled('offerings')
            ? collect()
            : Course::query()
                ->published()
                ->visible()
                ->with(['user:id,name', 'category:id,name'])
                ->withCount('enrollments')
                ->orderByDesc('enrollments_count')
                ->limit(5)
                ->get();

        // My learning - enrolled courses with progress (including completed courses)
        $myLearning = $user->enrollments()
            ->with([
                'course' => fn ($q) => $q->with(['user:id,name', 'category:id,name'])->withCount('lessons'),
                'offering:id,name,code',
            ])
            ->whereIn('status', ['active', 'completed'])
            ->orderByDesc('updated_at')
            ->get();

        $facilitatedOfferings = Academy::enabled('offerings')
            ? $user->facilitatedOfferings()
                ->with('course:id,title')
                ->withCount('enrollments')
                ->orderBy('name')
                ->get()
            : collect();

        // Invited courses - pending invitations
        $invitedCourses = $user->pendingInvitations()
            ->with([
                'course' => fn ($q) => $q->with(['user:id,name', 'category:id,name'])->withCount('lessons'),
                'inviter:id,name',
            ])
            ->get();

        $browseCourses = Academy::enabled('offerings')
            ? collect()
            : Course::query()
                ->published()
                ->visible()
                ->whereDoesntHave('enrollments', fn ($q) => $q->where('user_id', $user->id))
                ->whereDoesntHave('invitations', fn ($q) => $q->where('user_id', $user->id)->where('status', 'pending'))
                ->with(['user:id,name', 'category:id,name'])
                ->withCount('enrollments')
                ->orderByDesc('created_at')
                ->limit(12)
                ->get();

        return Inertia::render('learner/Dashboard', [
            'featuredCourses' => $featuredCourses->map(
                fn ($course) => new DashboardCourseResource($course)
            ),
            'myLearning' => $myLearning->map(
                fn ($enrollment) => new DashboardEnrollmentResource($enrollment)
            ),
            'facilitatedOfferings' => $facilitatedOfferings->map(
                fn ($offering) => new DashboardFacilitatedOfferingResource($offering)
            ),
            'invitedCourses' => $invitedCourses->map(
                fn ($invitation) => new DashboardInvitationResource($invitation)
            ),
            'browseCourses' => $browseCourses->map(
                fn ($course) => new DashboardCourseResource($course)
            ),
        ]);
    }
}
