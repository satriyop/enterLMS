<?php

namespace App\Http\Controllers\Api;

use App\Domain\Xapi\DTOs\XapiStatementData;
use App\Domain\Xapi\Services\XapiStatementService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Xapi\StoreXapiStatementRequest;
use App\Http\Resources\Xapi\XapiStatementResource;
use App\Models\XapiStatement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class XapiStatementController extends Controller
{
    public function __construct(
        protected XapiStatementService $statementService,
    ) {}

    /**
     * Store a new xAPI statement (POST /api/xapi/statements).
     */
    public function store(StoreXapiStatementRequest $request): JsonResponse
    {
        $statement = $this->statementService->record(
            XapiStatementData::fromArray($request->validated())
        );

        return response()->json([
            'data' => new XapiStatementResource($statement),
        ], 201);
    }

    /**
     * Query xAPI statements (GET /api/xapi/statements).
     */
    public function index(Request $request): JsonResponse
    {
        $query = XapiStatement::query();

        // Filter by actor
        if ($request->has('agent')) {
            $query->where('actor_mbox', $request->input('agent'));
        }

        // Filter by verb
        if ($request->has('verb')) {
            $query->where('verb_id', $request->input('verb'));
        }

        // Filter by activity (object)
        if ($request->has('activity')) {
            $query->where('object_id', $request->input('activity'));
        }

        // Filter by source
        if ($request->has('source')) {
            $query->where('source', $request->input('source'));
        }

        // Filter by course context
        if ($request->has('course_id')) {
            $query->where('context_course_id', $request->input('course_id'));
        }

        // Filter by date range
        if ($request->has('since')) {
            $query->where('timestamp', '>=', $request->input('since'));
        }

        if ($request->has('until')) {
            $query->where('timestamp', '<=', $request->input('until'));
        }

        $statements = $query
            ->orderByDesc('timestamp')
            ->limit($request->integer('limit', 100))
            ->get();

        return response()->json([
            'statements' => XapiStatementResource::collection($statements),
        ]);
    }
}
