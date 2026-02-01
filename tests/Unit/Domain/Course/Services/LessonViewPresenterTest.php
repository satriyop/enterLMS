<?php

declare(strict_types=1);

use App\Domain\Course\Services\LessonViewPresenter;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;

describe('LessonViewPresenter', function () {
    beforeEach(function () {
        $this->presenter = new LessonViewPresenter;
    });

    describe('getLessonViewData', function () {
        it('returns null lessonProgress when no enrollment', function () {
            $course = Course::factory()->published()->create();
            $section = CourseSection::factory()->create(['course_id' => $course->id, 'order' => 1]);
            $lesson = Lesson::factory()->create(['course_section_id' => $section->id, 'order' => 1]);

            $course->load(['sections.lessons']);

            $result = $this->presenter->getLessonViewData($course, $lesson, null);

            expect($result['lessonProgress'])->toBeNull();
        });

        it('returns correct lessonProgress when enrollment exists', function () {
            $course = Course::factory()->published()->create();
            $section = CourseSection::factory()->create(['course_id' => $course->id, 'order' => 1]);
            $lesson = Lesson::factory()->create(['course_section_id' => $section->id, 'order' => 1]);

            $user = User::factory()->create(['role' => 'learner']);
            $enrollment = Enrollment::factory()->create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'status' => 'active',
            ]);

            $lessonProgress = LessonProgress::factory()->create([
                'enrollment_id' => $enrollment->id,
                'lesson_id' => $lesson->id,
                'is_completed' => true,
                'completed_at' => now(),
            ]);

            $course->load(['sections.lessons']);

            $result = $this->presenter->getLessonViewData($course, $lesson, $enrollment);

            expect($result['lessonProgress'])->not->toBeNull()
                ->and($result['lessonProgress']->id)->toBe($lessonProgress->id)
                ->and($result['lessonProgress']->is_completed)->toBeTrue();
        });
    });

    describe('navigation', function () {
        it('first lesson has no prevLesson', function () {
            $course = Course::factory()->published()->create();
            $section = CourseSection::factory()->create(['course_id' => $course->id, 'order' => 1]);
            $lesson1 = Lesson::factory()->create(['course_section_id' => $section->id, 'order' => 1]);
            $lesson2 = Lesson::factory()->create(['course_section_id' => $section->id, 'order' => 2]);

            $course->load(['sections.lessons']);

            $result = $this->presenter->getLessonViewData($course, $lesson1, null);

            expect($result['prevLesson'])->toBeNull()
                ->and($result['nextLesson'])->not->toBeNull()
                ->and($result['nextLesson']['id'])->toBe($lesson2->id);
        });

        it('last lesson has no nextLesson', function () {
            $course = Course::factory()->published()->create();
            $section = CourseSection::factory()->create(['course_id' => $course->id, 'order' => 1]);
            $lesson1 = Lesson::factory()->create(['course_section_id' => $section->id, 'order' => 1]);
            $lesson2 = Lesson::factory()->create(['course_section_id' => $section->id, 'order' => 2]);

            $course->load(['sections.lessons']);

            $result = $this->presenter->getLessonViewData($course, $lesson2, null);

            expect($result['nextLesson'])->toBeNull()
                ->and($result['prevLesson'])->not->toBeNull()
                ->and($result['prevLesson']['id'])->toBe($lesson1->id);
        });

        it('middle lesson has both prevLesson and nextLesson', function () {
            $course = Course::factory()->published()->create();
            $section = CourseSection::factory()->create(['course_id' => $course->id, 'order' => 1]);
            $lesson1 = Lesson::factory()->create(['course_section_id' => $section->id, 'order' => 1]);
            $lesson2 = Lesson::factory()->create(['course_section_id' => $section->id, 'order' => 2]);
            $lesson3 = Lesson::factory()->create(['course_section_id' => $section->id, 'order' => 3]);

            $course->load(['sections.lessons']);

            $result = $this->presenter->getLessonViewData($course, $lesson2, null);

            expect($result['prevLesson'])->not->toBeNull()
                ->and($result['prevLesson']['id'])->toBe($lesson1->id)
                ->and($result['nextLesson'])->not->toBeNull()
                ->and($result['nextLesson']['id'])->toBe($lesson3->id);
        });
    });

    describe('lesson ordering', function () {
        it('orders lessons correctly across sections', function () {
            $course = Course::factory()->published()->create();
            $section1 = CourseSection::factory()->create(['course_id' => $course->id, 'order' => 1]);
            $section2 = CourseSection::factory()->create(['course_id' => $course->id, 'order' => 2]);

            $lesson1 = Lesson::factory()->create(['course_section_id' => $section1->id, 'order' => 1]);
            $lesson2 = Lesson::factory()->create(['course_section_id' => $section1->id, 'order' => 2]);
            $lesson3 = Lesson::factory()->create(['course_section_id' => $section2->id, 'order' => 1]);

            $course->load(['sections.lessons']);

            $result = $this->presenter->getLessonViewData($course, $lesson2, null);

            expect($result['allLessons'])->toHaveCount(3)
                ->and($result['allLessons'][0]['id'])->toBe($lesson1->id)
                ->and($result['allLessons'][0]['order'])->toBe(1001)
                ->and($result['allLessons'][1]['id'])->toBe($lesson2->id)
                ->and($result['allLessons'][1]['order'])->toBe(1002)
                ->and($result['allLessons'][2]['id'])->toBe($lesson3->id)
                ->and($result['allLessons'][2]['order'])->toBe(2001);
        });

        it('handles multi-section with multiple lessons ordering', function () {
            $course = Course::factory()->published()->create();
            $section1 = CourseSection::factory()->create(['course_id' => $course->id, 'order' => 1]);
            $section2 = CourseSection::factory()->create(['course_id' => $course->id, 'order' => 2]);
            $section3 = CourseSection::factory()->create(['course_id' => $course->id, 'order' => 3]);

            // Section 1: 2 lessons
            $s1l1 = Lesson::factory()->create(['course_section_id' => $section1->id, 'order' => 1]);
            $s1l2 = Lesson::factory()->create(['course_section_id' => $section1->id, 'order' => 2]);

            // Section 2: 3 lessons
            $s2l1 = Lesson::factory()->create(['course_section_id' => $section2->id, 'order' => 1]);
            $s2l2 = Lesson::factory()->create(['course_section_id' => $section2->id, 'order' => 2]);
            $s2l3 = Lesson::factory()->create(['course_section_id' => $section2->id, 'order' => 3]);

            // Section 3: 1 lesson
            $s3l1 = Lesson::factory()->create(['course_section_id' => $section3->id, 'order' => 1]);

            $course->load(['sections.lessons']);

            $result = $this->presenter->getLessonViewData($course, $s2l2, null);

            expect($result['allLessons'])->toHaveCount(6)
                ->and($result['allLessons'][0]['id'])->toBe($s1l1->id)
                ->and($result['allLessons'][1]['id'])->toBe($s1l2->id)
                ->and($result['allLessons'][2]['id'])->toBe($s2l1->id)
                ->and($result['allLessons'][3]['id'])->toBe($s2l2->id)
                ->and($result['allLessons'][4]['id'])->toBe($s2l3->id)
                ->and($result['allLessons'][5]['id'])->toBe($s3l1->id);
        });
    });

    describe('completion status', function () {
        it('marks completion status correctly for enrolled users', function () {
            $course = Course::factory()->published()->create();
            $section = CourseSection::factory()->create(['course_id' => $course->id, 'order' => 1]);
            $lesson1 = Lesson::factory()->create(['course_section_id' => $section->id, 'order' => 1]);
            $lesson2 = Lesson::factory()->create(['course_section_id' => $section->id, 'order' => 2]);
            $lesson3 = Lesson::factory()->create(['course_section_id' => $section->id, 'order' => 3]);

            $user = User::factory()->create(['role' => 'learner']);
            $enrollment = Enrollment::factory()->create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'status' => 'active',
            ]);

            // Complete only lesson1 and lesson3
            LessonProgress::factory()->create([
                'enrollment_id' => $enrollment->id,
                'lesson_id' => $lesson1->id,
                'is_completed' => true,
                'completed_at' => now(),
            ]);

            LessonProgress::factory()->create([
                'enrollment_id' => $enrollment->id,
                'lesson_id' => $lesson3->id,
                'is_completed' => true,
                'completed_at' => now(),
            ]);

            $course->load(['sections.lessons']);

            $result = $this->presenter->getLessonViewData($course, $lesson2, $enrollment);

            expect($result['allLessons'])->toHaveCount(3)
                ->and($result['allLessons'][0]['is_completed'])->toBeTrue()
                ->and($result['allLessons'][1]['is_completed'])->toBeFalse()
                ->and($result['allLessons'][2]['is_completed'])->toBeTrue();
        });

        it('marks all lessons as incomplete when no enrollment', function () {
            $course = Course::factory()->published()->create();
            $section = CourseSection::factory()->create(['course_id' => $course->id, 'order' => 1]);
            $lesson1 = Lesson::factory()->create(['course_section_id' => $section->id, 'order' => 1]);
            $lesson2 = Lesson::factory()->create(['course_section_id' => $section->id, 'order' => 2]);

            $course->load(['sections.lessons']);

            $result = $this->presenter->getLessonViewData($course, $lesson1, null);

            expect($result['allLessons'])->toHaveCount(2)
                ->and($result['allLessons'][0]['is_completed'])->toBeFalse()
                ->and($result['allLessons'][1]['is_completed'])->toBeFalse();
        });
    });

    describe('allLessons structure', function () {
        it('includes correct lesson metadata', function () {
            $course = Course::factory()->published()->create();
            $section = CourseSection::factory()->create([
                'course_id' => $course->id,
                'order' => 1,
                'title' => 'Introduction Section',
            ]);
            $lesson = Lesson::factory()->create([
                'course_section_id' => $section->id,
                'order' => 1,
                'title' => 'Getting Started',
            ]);

            $course->load(['sections.lessons']);

            $result = $this->presenter->getLessonViewData($course, $lesson, null);

            $lessonData = $result['allLessons'][0];

            expect($lessonData)->toHaveKey('id')
                ->and($lessonData)->toHaveKey('title')
                ->and($lessonData)->toHaveKey('section_title')
                ->and($lessonData)->toHaveKey('order')
                ->and($lessonData)->toHaveKey('is_completed')
                ->and($lessonData['id'])->toBe($lesson->id)
                ->and($lessonData['title'])->toBe('Getting Started')
                ->and($lessonData['section_title'])->toBe('Introduction Section')
                ->and($lessonData['order'])->toBe(1001)
                ->and($lessonData['is_completed'])->toBeFalse();
        });
    });
});
