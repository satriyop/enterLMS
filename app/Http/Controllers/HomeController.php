<?php

namespace App\Http\Controllers;

use App\Domain\Shared\Academy;
use App\Http\Resources\HomeCourseResource;
use App\Models\Category;
use App\Models\Course;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class HomeController extends Controller
{
    public function index(): Response
    {
        $catalogOpen = ! Academy::enabled('offerings');

        $baseQuery = Course::query()
            ->published()
            ->visible()
            ->with(['user:id,name', 'category:id,name,slug'])
            ->withCount(['sections', 'lessons', 'enrollments'])
            ->withAvg('ratings', 'rating')
            ->withCount('ratings');

        $featuredCourses = $catalogOpen
            ? HomeCourseResource::collection(
                (clone $baseQuery)->latest()->limit(8)->get()
            )->resolve()
            : [];

        $popularCourses = $catalogOpen
            ? HomeCourseResource::collection(
                (clone $baseQuery)->orderByDesc('enrollments_count')->limit(8)->get()
            )->resolve()
            : [];

        $categories = Category::query()
            ->whereNull('parent_id')
            ->withCount(['courses' => fn ($query) => $query->published()->visible()])
            ->orderBy('order')
            ->limit(12)
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'courses_count' => $category->courses_count,
                'icon' => $this->getCategoryIcon($category->slug),
            ]);

        $stats = Cache::remember('home_stats', 3600, function () {
            $coursesCount = DB::table('courses')
                ->whereNull('deleted_at')
                ->where('status', 'published')
                ->where('visibility', 'public')
                ->count();

            $studentsCount = DB::table('enrollments')
                ->distinct('user_id')
                ->count('user_id');

            $instructorsCount = DB::table('courses')
                ->whereNull('deleted_at')
                ->where('status', 'published')
                ->distinct('user_id')
                ->count('user_id');

            // Prefer manual duration override, else estimated (minutes) → hours.
            $totalMinutes = (int) DB::table('courses')
                ->whereNull('deleted_at')
                ->where('status', 'published')
                ->where('visibility', 'public')
                ->sum(DB::raw('COALESCE(manual_duration_minutes, estimated_duration_minutes, 0)'));

            $totalHours = (int) round($totalMinutes / 60);

            return [
                ['label' => 'Kursus Tersedia', 'value' => number_format($coursesCount), 'icon' => 'courses'],
                ['label' => 'Siswa Terdaftar', 'value' => number_format($studentsCount), 'icon' => 'students'],
                ['label' => 'Instruktur Ahli', 'value' => number_format($instructorsCount), 'icon' => 'instructors'],
                ['label' => 'Jam Konten', 'value' => number_format($totalHours), 'icon' => 'hours'],
            ];
        });

        return Inertia::render('Welcome', [
            'canRegister' => $catalogOpen && Features::enabled(Features::registration()),
            'featuredCourses' => $featuredCourses,
            'popularCourses' => $popularCourses,
            'categories' => $catalogOpen ? $categories : collect(),
            'stats' => $stats,
        ]);
    }

    private function getCategoryIcon(string $slug): string
    {
        $iconMap = [
            'programming' => 'code',
            'web-development' => 'code',
            'mobile-development' => 'code',
            'design' => 'palette',
            'ui-ux' => 'palette',
            'graphic-design' => 'palette',
            'business' => 'briefcase',
            'marketing' => 'trending',
            'photography' => 'camera',
            'video' => 'camera',
            'music' => 'music',
            'health' => 'heart',
            'fitness' => 'heart',
            'language' => 'globe',
        ];

        foreach ($iconMap as $key => $icon) {
            if (str_contains($slug, $key)) {
                return $icon;
            }
        }

        return 'briefcase';
    }
}
