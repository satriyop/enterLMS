<?php

use App\Models\Assessment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Database\Seeders\AgentAcademyCourseSeeder;
use Database\Seeders\FreeFlowDemoSeeder;

describe('Agent academy catalog content', function () {
    it('seeds Pengenalan Agen AI with every lesson form and a required authored quiz', function () {
        $this->seed(FreeFlowDemoSeeder::class);

        $course = Course::query()->where('title', FreeFlowDemoSeeder::FREE_COURSE_TITLE)->first();

        expect($course)->not->toBeNull();
        expect($course->lessons()->count())->toBe(count(FreeFlowDemoSeeder::LESSON_TITLES));

        $types = Lesson::query()
            ->join('course_sections', 'lessons.course_section_id', '=', 'course_sections.id')
            ->where('course_sections.course_id', $course->id)
            ->pluck('lessons.content_type')
            ->unique()
            ->sort()
            ->values()
            ->all();

        expect($types)->toBe(['audio', 'conference', 'document', 'text', 'youtube']);

        $youtube = $course->lessons()->where('content_type', 'youtube')->first();
        expect($youtube?->youtube_url)->toBe(FreeFlowDemoSeeder::YOUTUBE_AGENTS_EXPLAINED);

        $audio = $course->lessons()->where('content_type', 'audio')->with('media')->first();
        expect($audio?->media->firstWhere('collection_name', 'audio'))->not->toBeNull();

        $document = $course->lessons()->where('content_type', 'document')->with('media')->first();
        expect($document?->media->firstWhere('collection_name', 'document')?->mime_type)->toBe('application/pdf');

        $conference = $course->lessons()->where('content_type', 'conference')->first();
        expect($conference?->conference_url)->toBe(FreeFlowDemoSeeder::OFFICE_HOURS_URL);
        expect($conference?->conference_type)->toBe('google_meet');

        $quiz = Assessment::query()
            ->where('course_id', $course->id)
            ->where('title', FreeFlowDemoSeeder::INTRO_QUIZ_TITLE)
            ->with('questions')
            ->first();

        expect($quiz)->not->toBeNull();
        expect($quiz->is_required)->toBeTrue();
        expect($quiz->status)->toBe('published');
        expect($quiz->questions->pluck('question_type')->all())->toBe([
            'multiple_choice',
            'true_false',
            'multiple_choice',
            'short_answer',
        ]);
        expect($quiz->questions->firstWhere('question_type', 'short_answer')?->correct_answer)->toBeNull();
    });

    it('seeds OpenClaw with remaining lesson forms and a required essay exam', function () {
        $this->seed(FreeFlowDemoSeeder::class);
        $this->seed(AgentAcademyCourseSeeder::class);

        $course = Course::query()->where('title', AgentAcademyCourseSeeder::RESTRICTED_COURSE_TITLE)->first();

        expect($course)->not->toBeNull();
        expect($course->visibility)->toBe('restricted');
        expect($course->lessons()->count())->toBe(count(AgentAcademyCourseSeeder::LESSON_TITLES));

        $types = Lesson::query()
            ->join('course_sections', 'lessons.course_section_id', '=', 'course_sections.id')
            ->where('course_sections.course_id', $course->id)
            ->pluck('lessons.content_type')
            ->unique()
            ->sort()
            ->values()
            ->all();

        expect($types)->toBe(['audio', 'conference', 'document', 'text', 'video']);

        $video = $course->lessons()->where('content_type', 'video')->with('media')->first();
        expect($video?->media->firstWhere('collection_name', 'video')?->mime_type)->toBe('video/mp4');

        $exam = Assessment::query()
            ->where('course_id', $course->id)
            ->where('title', AgentAcademyCourseSeeder::FINAL_EXAM_TITLE)
            ->with('questions')
            ->first();

        expect($exam)->not->toBeNull();
        expect($exam->is_required)->toBeTrue();
        expect($exam->questions->pluck('question_type')->all())->toBe([
            'multiple_choice',
            'true_false',
            'multiple_choice',
            'essay',
        ]);
    });

    it('shows conference join data on an enrolled conference lesson', function () {
        $this->seed(FreeFlowDemoSeeder::class);

        $learner = User::query()->where('email', 'learner@enterlms.test')->first();
        $course = Course::query()->where('title', FreeFlowDemoSeeder::FREE_COURSE_TITLE)->first();
        $lesson = $course->lessons()->where('content_type', 'conference')->first();

        $this->actingAs($learner)
            ->post(route('courses.enroll', $course))
            ->assertRedirect();

        $this->actingAs($learner)
            ->get(route('courses.lessons.show', [$course, $lesson]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('lessons/Show')
                ->where('lesson.content_type', 'conference')
                ->where('lesson.conference_url', FreeFlowDemoSeeder::OFFICE_HOURS_URL)
                ->where('lesson.conference_type', 'google_meet')
            );
    });

    it('rebuilds stale catalog content when lesson titles no longer match', function () {
        $this->seed(FreeFlowDemoSeeder::class);

        $course = Course::query()->where('title', FreeFlowDemoSeeder::FREE_COURSE_TITLE)->first();
        $course->lessons()->first()?->update(['title' => 'Judul lama yang harus diganti']);

        $this->seed(FreeFlowDemoSeeder::class);

        $titles = Lesson::query()
            ->join('course_sections', 'lessons.course_section_id', '=', 'course_sections.id')
            ->where('course_sections.course_id', $course->id)
            ->orderBy('course_sections.order')
            ->orderBy('lessons.order')
            ->pluck('lessons.title')
            ->all();

        expect($titles)->toBe(FreeFlowDemoSeeder::LESSON_TITLES);
    });
});
