<?php

namespace App\Mcp\Tools\Agent;

use App\Domain\Agent\Abilities\AgentAbility;
use App\Mcp\Concerns\AuditsAgentToolCalls;
use App\Models\Course;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list-catalog')]
#[Description('List published public courses in the catalog (paginated). Free LMS path; paid flag only matters when payments are enabled.')]
#[IsReadOnly]
#[IsIdempotent]
class ListCatalogTool extends Tool
{
    use AuditsAgentToolCalls;

    public function handle(Request $request): Response|ResponseFactory
    {
        if ($denied = $this->requireAbility($request, AgentAbility::CATALOG_READ)) {
            return $denied;
        }

        return $this->runAudited($request, function () use ($request) {
            $validated = $request->validate([
                'search' => ['nullable', 'string', 'max:100'],
                'page' => ['nullable', 'integer', 'min:1'],
                'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            ]);

            $perPage = (int) ($validated['per_page'] ?? 15);
            $page = (int) ($validated['page'] ?? 1);

            $paginator = Course::query()
                ->published()
                ->visible()
                ->search($validated['search'] ?? null)
                ->with(['category:id,name,slug'])
                ->withCount(['lessons', 'enrollments'])
                ->orderByDesc('published_at')
                ->paginate(perPage: $perPage, page: $page);

            $items = $paginator->getCollection()->map(fn (Course $course) => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'short_description' => $course->short_description,
                'category' => $course->category?->name,
                'difficulty_level' => $course->difficulty_level,
                'is_paid_flag' => (bool) $course->is_paid,
                'requires_payment' => $course->isPaid(),
                'lessons_count' => $course->lessons_count,
                'enrollments_count' => $course->enrollments_count,
            ])->values()->all();

            return Response::structured([
                'ok' => true,
                'data' => $items,
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ]);
        });
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Optional title/description search'),
            'page' => $schema->integer()->description('Page number (default 1)'),
            'per_page' => $schema->integer()->description('Items per page (default 15, max 50)'),
        ];
    }
}
