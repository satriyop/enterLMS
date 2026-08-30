<?php

namespace App\Http\Controllers;

use App\Domain\Course\Exceptions\CannotDeleteDefaultOfferingException;
use App\Domain\Course\Exceptions\OfferingHasEnrollmentsException;
use App\Domain\Shared\Academy;
use App\Http\Requests\Offering\StoreOfferingRequest;
use App\Http\Requests\Offering\UpdateOfferingRequest;
use App\Http\Resources\Course\OfferingResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Offering;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CourseOfferingController extends Controller
{
    public function index(Request $request, Course $course): Response
    {
        Gate::authorize('viewAny', [Offering::class, $course]);

        $offerings = $course->offerings()
            ->with('facilitator:id,name,email')
            ->withCount('enrollments')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $user = $request->user();
        $rosterOfferingIds = $user->isLmsAdmin()
            ? $offerings->pluck('id')
            : $offerings->where('facilitator_id', $user->id)->pluck('id');

        if ($rosterOfferingIds->isNotEmpty()) {
            $roster = Enrollment::query()
                ->whereIn('offering_id', $rosterOfferingIds)
                ->with('user:id,name,email,external_id')
                ->orderBy('id')
                ->get()
                ->groupBy('offering_id');

            foreach ($offerings as $offering) {
                if (! $offering instanceof Offering) {
                    continue;
                }

                if ($rosterOfferingIds->contains($offering->id)) {
                    $offering->setRelation(
                        'enrollments',
                        $roster->get($offering->id, collect()),
                    );
                }
            }
        }
        $namedIds = $course->offerings()->where('is_default', false)->pluck('id');
        $grantOfferingIds = $user->isLmsAdmin()
            ? ($namedIds->isNotEmpty()
                ? $namedIds->all()
                : $course->offerings()->where('is_default', true)->pluck('id')->all())
            : $course->offerings()->where('facilitator_id', $user->id)->pluck('id')->all();

        return Inertia::render('courses/offerings/Index', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
            ],
            'offerings' => OfferingResource::collection($offerings)->resolve(),
            'grantOfferingIds' => $grantOfferingIds,
            'label' => Academy::label('offering'),
            'can' => [
                'create' => Gate::allows('create', [Offering::class, $course]),
                'grant' => Gate::allows('bulkEnroll', $course),
            ],
        ]);
    }

    public function store(StoreOfferingRequest $request, Course $course): RedirectResponse
    {
        $validated = $request->safe()->except(['facilitator_email']);
        $validated['code'] = Offering::uniqueCodeFor(
            $course,
            $validated['name'],
            $validated['code'] ?? null,
        );
        $validated['is_default'] = false;

        $course->offerings()->create($validated);

        return redirect()
            ->route('courses.offerings.index', $course)
            ->with('success', Academy::label('offering').' berhasil dibuat.');
    }

    public function update(UpdateOfferingRequest $request, Course $course, Offering $offering): RedirectResponse
    {
        if ($offering->course_id !== $course->id) {
            abort(404);
        }

        $validated = $request->safe()->except(['facilitator_email']);

        if ($offering->is_default) {
            unset($validated['code']);
        } else {
            $validated['code'] = Offering::uniqueCodeFor(
                $course,
                $validated['name'],
                $validated['code'] ?? null,
                $offering->id,
            );
        }

        $offering->update($validated);

        return redirect()
            ->route('courses.offerings.index', $course)
            ->with('success', Academy::label('offering').' berhasil diperbarui.');
    }

    public function destroy(Course $course, Offering $offering): RedirectResponse
    {
        if ($offering->course_id !== $course->id) {
            abort(404);
        }

        Gate::authorize('delete', $offering);

        try {
            $offering->deleteRun();
        } catch (CannotDeleteDefaultOfferingException) {
            return back()->with('error', 'Offering default tidak dapat dihapus.');
        } catch (OfferingHasEnrollmentsException) {
            return back()->with('error', 'Offering masih memiliki pendaftaran.');
        }

        return redirect()
            ->route('courses.offerings.index', $course)
            ->with('success', Academy::label('offering').' berhasil dihapus.');
    }
}
