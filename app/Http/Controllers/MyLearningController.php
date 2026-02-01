<?php

namespace App\Http\Controllers;

use App\Http\Resources\Dashboard\DashboardEnrollmentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MyLearningController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = Auth::user();

        abort_unless($user->isLearner(), 403);

        $status = $request->input('status');

        $enrollments = $user->enrollments()
            ->with(['course' => fn ($q) => $q->with(['user:id,name', 'category:id,name'])->withCount('lessons')])
            ->when($status && in_array($status, ['active', 'completed', 'dropped']), function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when(! $status, function ($q) {
                $q->whereIn('status', ['active', 'completed']);
            })
            ->orderByDesc('updated_at')
            ->paginate(12)
            ->withQueryString();

        $statusCounts = $user->enrollments()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return Inertia::render('enrollments/Index', [
            'enrollments' => DashboardEnrollmentResource::collection($enrollments),
            'filters' => [
                'status' => $status,
            ],
            'statusCounts' => [
                'all' => array_sum($statusCounts),
                'active' => $statusCounts['active'] ?? 0,
                'completed' => $statusCounts['completed'] ?? 0,
                'dropped' => $statusCounts['dropped'] ?? 0,
            ],
        ]);
    }
}
