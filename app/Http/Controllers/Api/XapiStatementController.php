<?php

namespace App\Http\Controllers\Api;

use App\Domain\Xapi\DTOs\XapiStatementData;
use App\Domain\Xapi\Services\XapiStatementService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Xapi\StoreXapiStatementRequest;
use App\Http\Resources\Xapi\XapiStatementResource;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\XapiStatement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class XapiStatementController extends Controller
{
    public function __construct(
        protected XapiStatementService $statementService,
    ) {}

    /**
     * Store a new xAPI statement (POST /api/xapi/statements).
     *
     * Actor is always bound to the authenticated user (no spoofing).
     * Context enrollment/course must belong to that actor (unless compliance role).
     */
    public function store(StoreXapiStatementRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        $validated['actor_id'] = $user->id;
        $validated['actor_name'] = $user->name;
        $validated['actor_mbox'] = 'mailto:'.$user->email;

        $validated = $this->scopedContextForActor($user, $validated);

        $statement = $this->statementService->record(
            XapiStatementData::fromArray($validated)
        );

        return response()->json([
            'data' => new XapiStatementResource($statement),
        ], 201);
    }

    /**
     * Ensure context_enrollment_id / context_course_id cannot point at another user's enrollment.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function scopedContextForActor(User $user, array $validated): array
    {
        if ($user->canViewCompliance()) {
            return $validated;
        }

        $enrollmentId = $validated['context_enrollment_id'] ?? null;
        $courseId = $validated['context_course_id'] ?? null;

        if ($enrollmentId !== null) {
            $enrollment = Enrollment::query()
                ->whereKey($enrollmentId)
                ->where('user_id', $user->id)
                ->first();

            if ($enrollment === null) {
                throw ValidationException::withMessages([
                    'context_enrollment_id' => 'Enrollment tidak milik pengguna yang terautentikasi.',
                ]);
            }

            // Align course context with the owned enrollment when both are sent.
            if ($courseId !== null && (int) $courseId !== (int) $enrollment->course_id) {
                throw ValidationException::withMessages([
                    'context_course_id' => 'Course context tidak sesuai dengan enrollment yang dipilih.',
                ]);
            }

            $validated['context_course_id'] = $enrollment->course_id;
        }

        return $validated;
    }

    /**
     * Query xAPI statements (GET /api/xapi/statements).
     *
     * Learners only see their own statements; compliance roles may query broadly.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $query = XapiStatement::query();

        if (! $user->canViewCompliance()) {
            $query->where('actor_id', $user->id);
        } elseif ($request->filled('actor_id')) {
            $query->where('actor_id', $request->integer('actor_id'));
        }

        // Optional agent filter only for compliance roles (already scoped for learners).
        if ($request->has('agent') && $user->canViewCompliance()) {
            $query->where('actor_mbox', $request->input('agent'));
        }

        if ($request->has('verb')) {
            $query->where('verb_id', $request->input('verb'));
        }

        if ($request->has('activity')) {
            $query->where('object_id', $request->input('activity'));
        }

        if ($request->has('source')) {
            $query->where('source', $request->input('source'));
        }

        if ($request->has('course_id')) {
            $query->where('context_course_id', $request->input('course_id'));
        }

        if ($request->has('since')) {
            $query->where('timestamp', '>=', $request->input('since'));
        }

        if ($request->has('until')) {
            $query->where('timestamp', '<=', $request->input('until'));
        }

        $statements = $query
            ->orderByDesc('timestamp')
            ->limit(min($request->integer('limit', 100), 500))
            ->get();

        return response()->json([
            'statements' => XapiStatementResource::collection($statements),
        ]);
    }
}
