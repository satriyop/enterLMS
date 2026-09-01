<?php

namespace App\Http\Controllers;

use App\Http\Resources\Course\ConversationSummaryResource;
use App\Http\Resources\Course\ConversationTranscriptResource;
use App\Models\Conversation;
use App\Models\ConversationTurn;
use App\Models\Course;
use App\Models\Offering;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Reading what the Tutor taught.
 *
 * CONTEXT.md makes a Conversation readable by its Learner, by LMS Admin, and
 * by the Facilitator of that Enrollment's Offering. The first had a page; the
 * other two had only a policy. This is that page.
 */
class CourseConversationController extends Controller
{
    public function index(Request $request, Course $course): Response
    {
        Gate::authorize('viewAny', [Conversation::class, $course]);

        $readableOfferingIds = $this->readableOfferingIds($request->user(), $course);

        $conversations = $this->scopedQuery($course, $readableOfferingIds)
            ->with([
                'lesson:id,title',
                'enrollment:id,user_id,offering_id,status,course_id',
                'enrollment.user:id,name,email',
                'enrollment.offering:id,name',
            ])
            ->withCount('turns')
            ->addSelect([
                'last_turn_at' => ConversationTurn::query()
                    ->select('created_at')
                    ->whereColumn('conversation_id', 'conversations.id')
                    ->latest('id')
                    ->limit(1),
                // The question a Learner opened with is the one signal in this
                // list that is about the Course rather than about the Tutor.
                'opening_question' => ConversationTurn::query()
                    ->select('body')
                    ->whereColumn('conversation_id', 'conversations.id')
                    ->where('role', ConversationTurn::ROLE_LEARNER)
                    ->oldest('id')
                    ->limit(1),
            ])
            ->when(
                $request->integer('lesson_id'),
                fn (Builder $query, int $lessonId) => $query->where('conversations.lesson_id', $lessonId)
            )
            ->when(
                $request->integer('offering_id'),
                fn (Builder $query, int $offeringId) => $query->where('enrollments.offering_id', $offeringId)
            )
            ->when(
                $request->string('search')->trim()->value(),
                fn (Builder $query, string $search) => $query->whereHas(
                    'enrollment.user',
                    fn (Builder $learner) => $learner
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                )
            )
            ->orderByDesc('last_turn_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('courses/conversations/Index', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
            ],
            'conversations' => ConversationSummaryResource::collection($conversations),
            'lessons' => $course->lessons()
                ->orderBy('course_sections.order')
                ->orderBy('lessons.order')
                ->get(['lessons.id', 'lessons.title']),
            'offerings' => Offering::query()
                ->where('course_id', $course->id)
                ->when(
                    $readableOfferingIds !== null,
                    fn (Builder $query) => $query->whereKey($readableOfferingIds)
                )
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters' => [
                'search' => $request->string('search')->trim()->value(),
                'lesson_id' => $request->integer('lesson_id') ?: null,
                'offering_id' => $request->integer('offering_id') ?: null,
            ],
        ]);
    }

    public function show(Request $request, Course $course, Conversation $conversation): Response
    {
        $conversation->loadMissing('enrollment');

        // Route model binding does not scope a child to its parent, so a
        // Conversation from another Course would otherwise be readable through
        // a Course this reader happens to run.
        if ($conversation->enrollment->course_id !== $course->id) {
            throw new NotFoundHttpException;
        }

        Gate::authorize('view', $conversation);

        $conversation->load([
            'lesson:id,title',
            'enrollment.user:id,name,email',
            'enrollment.offering:id,name',
            'turns',
        ]);

        return Inertia::render('courses/conversations/Show', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
            ],
            'conversation' => new ConversationTranscriptResource($conversation),
        ]);
    }

    /**
     * @param  Collection<int, int>|null  $readableOfferingIds
     * @return Builder<Conversation>
     */
    protected function scopedQuery(Course $course, ?Collection $readableOfferingIds): Builder
    {
        $query = Conversation::query()
            ->select('conversations.*')
            ->join('enrollments', 'enrollments.id', '=', 'conversations.enrollment_id')
            ->where('enrollments.course_id', $course->id);

        if ($readableOfferingIds !== null) {
            $query->whereIn('enrollments.offering_id', $readableOfferingIds);
        }

        return $query;
    }

    /**
     * Null means "every Offering" -- an LMS Admin runs the academy. A
     * Facilitator's grant is per Offering, so theirs is an explicit list.
     *
     * @return Collection<int, int>|null
     */
    protected function readableOfferingIds(User $user, Course $course): ?Collection
    {
        if ($user->canManageCourses()) {
            return null;
        }

        return $user->facilitatedOfferings()
            ->where('course_id', $course->id)
            ->pluck('id');
    }
}
