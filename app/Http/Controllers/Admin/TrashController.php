<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TrashController extends Controller
{
    /**
     * @var array<string, class-string<\Illuminate\Database\Eloquent\Model>>
     */
    protected array $restorableModels = [
        'course' => Course::class,
        'assessment' => Assessment::class,
        'learning_path' => LearningPath::class,
        'section' => CourseSection::class,
        'lesson' => Lesson::class,
        'user' => User::class,
    ];

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $type = $request->input('type', '');

        $groups = $this->getTrashedGroups($type);

        return Inertia::render('admin/Trash', [
            'groups' => $groups,
            'filters' => ['type' => $type],
            'summary' => $this->getTrashedSummary(),
        ]);
    }

    public function restore(Request $request, string $type, int $id): RedirectResponse
    {
        Gate::authorize('viewAny', User::class);

        $modelClass = $this->restorableModels[$type] ?? null;
        if (! $modelClass) {
            abort(404);
        }

        $record = $modelClass::onlyTrashed()->findOrFail($id);
        $record->restore();

        return back()->with('success', 'Item berhasil dipulihkan.');
    }

    public function forceDelete(Request $request, string $type, int $id): RedirectResponse
    {
        Gate::authorize('viewAny', User::class);

        $modelClass = $this->restorableModels[$type] ?? null;
        if (! $modelClass) {
            abort(404);
        }

        $record = $modelClass::onlyTrashed()->findOrFail($id);

        // Clean up course thumbnails
        if ($modelClass === Course::class && $record->thumbnail_path) {
            Storage::disk('public')->delete($record->thumbnail_path);
        }

        $record->forceDelete();

        return back()->with('success', 'Item berhasil dihapus secara permanen.');
    }

    /**
     * @return array<int, array{type: string, label: string, items: array<int, array{id: int, title: string, detail: string, deleted_at: string}>}>
     */
    protected function getTrashedGroups(string $filterType): array
    {
        $groups = [];

        $modelConfigs = [
            'course' => [
                'label' => 'Kursus',
                'query' => fn () => Course::onlyTrashed()->select('id', 'title', 'user_id', 'deleted_at')->with('user:id,name')->latest('deleted_at')->limit(50)->get(),
                'map' => fn ($item) => ['id' => $item->id, 'title' => $item->title, 'detail' => $item->user?->name ?? '-', 'deleted_at' => $item->deleted_at->toIso8601String()],
            ],
            'assessment' => [
                'label' => 'Penilaian',
                'query' => fn () => Assessment::onlyTrashed()->select('id', 'title', 'course_id', 'deleted_at')->with('course:id,title')->latest('deleted_at')->limit(50)->get(),
                'map' => fn ($item) => ['id' => $item->id, 'title' => $item->title, 'detail' => $item->course?->title ?? '-', 'deleted_at' => $item->deleted_at->toIso8601String()],
            ],
            'learning_path' => [
                'label' => 'Jalur Belajar',
                'query' => fn () => LearningPath::onlyTrashed()->select('id', 'title', 'deleted_at')->latest('deleted_at')->limit(50)->get(),
                'map' => fn ($item) => ['id' => $item->id, 'title' => $item->title, 'detail' => '-', 'deleted_at' => $item->deleted_at->toIso8601String()],
            ],
            'section' => [
                'label' => 'Bagian Kursus',
                'query' => fn () => CourseSection::onlyTrashed()->select('id', 'title', 'course_id', 'deleted_at')->with('course:id,title')->latest('deleted_at')->limit(50)->get(),
                'map' => fn ($item) => ['id' => $item->id, 'title' => $item->title, 'detail' => $item->course?->title ?? '-', 'deleted_at' => $item->deleted_at->toIso8601String()],
            ],
            'lesson' => [
                'label' => 'Pelajaran',
                'query' => fn () => Lesson::onlyTrashed()->select('id', 'title', 'course_section_id', 'deleted_at')->with('section:id,title')->latest('deleted_at')->limit(50)->get(),
                'map' => fn ($item) => ['id' => $item->id, 'title' => $item->title, 'detail' => $item->section?->title ?? '-', 'deleted_at' => $item->deleted_at->toIso8601String()],
            ],
            'user' => [
                'label' => 'Pengguna',
                'query' => fn () => User::onlyTrashed()->select('id', 'name', 'email', 'deleted_at')->latest('deleted_at')->limit(50)->get(),
                'map' => fn ($item) => ['id' => $item->id, 'title' => $item->name, 'detail' => $item->email, 'deleted_at' => $item->deleted_at->toIso8601String()],
            ],
        ];

        foreach ($modelConfigs as $type => $config) {
            if ($filterType && $filterType !== $type) {
                continue;
            }

            $items = $config['query']()->map($config['map'])->toArray();

            if (count($items) > 0) {
                $groups[] = [
                    'type' => $type,
                    'label' => $config['label'],
                    'items' => $items,
                ];
            }
        }

        return $groups;
    }

    /**
     * @return array<string, array{label: string, count: int}>
     */
    protected function getTrashedSummary(): array
    {
        return [
            'course' => ['label' => 'Kursus', 'count' => Course::onlyTrashed()->count()],
            'assessment' => ['label' => 'Penilaian', 'count' => Assessment::onlyTrashed()->count()],
            'learning_path' => ['label' => 'Jalur Belajar', 'count' => LearningPath::onlyTrashed()->count()],
            'section' => ['label' => 'Bagian', 'count' => CourseSection::onlyTrashed()->count()],
            'lesson' => ['label' => 'Pelajaran', 'count' => Lesson::onlyTrashed()->count()],
            'user' => ['label' => 'Pengguna', 'count' => User::onlyTrashed()->count()],
        ];
    }
}
