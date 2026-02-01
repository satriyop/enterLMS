<?php

use App\Models\Assessment;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuestionOption;

describe('PurgeOldTrashedRecords', function () {
    it('purges records trashed longer than specified days', function () {
        $course = Course::factory()->draft()->create();
        $course->delete();
        // Backdate the deleted_at to 31 days ago
        Course::withTrashed()->where('id', $course->id)->update([
            'deleted_at' => now()->subDays(31),
        ]);

        $this->artisan('app:purge-trashed --days=30')
            ->assertSuccessful();

        expect(Course::withTrashed()->where('id', $course->id)->exists())->toBeFalse();
    });

    it('does not purge recently trashed records', function () {
        $course = Course::factory()->draft()->create();
        $course->delete();
        // Deleted 10 days ago — should NOT be purged
        Course::withTrashed()->where('id', $course->id)->update([
            'deleted_at' => now()->subDays(10),
        ]);

        $this->artisan('app:purge-trashed --days=30')
            ->assertSuccessful();

        expect(Course::withTrashed()->where('id', $course->id)->exists())->toBeTrue();
    });

    it('dry-run does not delete anything', function () {
        $course = Course::factory()->draft()->create();
        $course->delete();
        Course::withTrashed()->where('id', $course->id)->update([
            'deleted_at' => now()->subDays(31),
        ]);

        $this->artisan('app:purge-trashed --days=30 --dry-run')
            ->assertSuccessful();

        // Record should still exist
        expect(Course::withTrashed()->where('id', $course->id)->exists())->toBeTrue();
    });

    it('cascades force-delete from assessment to questions and options', function () {
        $assessment = Assessment::factory()->create();
        $question = Question::factory()->multipleChoice()->create([
            'assessment_id' => $assessment->id,
        ]);
        $option = QuestionOption::factory()->correct()->create([
            'question_id' => $question->id,
        ]);

        $assessment->delete();
        // Backdate everything
        Assessment::withTrashed()->where('id', $assessment->id)->update([
            'deleted_at' => now()->subDays(31),
        ]);
        Question::withTrashed()->where('id', $question->id)->update([
            'deleted_at' => now()->subDays(31),
        ]);
        QuestionOption::withTrashed()->where('id', $option->id)->update([
            'deleted_at' => now()->subDays(31),
        ]);

        $this->artisan('app:purge-trashed --days=30')
            ->assertSuccessful();

        expect(Assessment::withTrashed()->where('id', $assessment->id)->exists())->toBeFalse();
        expect(Question::withTrashed()->where('id', $question->id)->exists())->toBeFalse();
        expect(QuestionOption::withTrashed()->where('id', $option->id)->exists())->toBeFalse();
    });

    it('cascades force-delete from section to lessons', function () {
        $course = Course::factory()->draft()->create();
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['course_section_id' => $section->id]);

        $section->delete();
        CourseSection::withTrashed()->where('id', $section->id)->update([
            'deleted_at' => now()->subDays(31),
        ]);
        Lesson::withTrashed()->where('id', $lesson->id)->update([
            'deleted_at' => now()->subDays(31),
        ]);

        $this->artisan('app:purge-trashed --days=30')
            ->assertSuccessful();

        expect(CourseSection::withTrashed()->where('id', $section->id)->exists())->toBeFalse();
        expect(Lesson::withTrashed()->where('id', $lesson->id)->exists())->toBeFalse();
    });

    it('respects custom --days option', function () {
        $course = Course::factory()->draft()->create();
        $course->delete();
        Course::withTrashed()->where('id', $course->id)->update([
            'deleted_at' => now()->subDays(8),
        ]);

        // 30 days: should NOT purge
        $this->artisan('app:purge-trashed --days=30')
            ->assertSuccessful();
        expect(Course::withTrashed()->where('id', $course->id)->exists())->toBeTrue();

        // 7 days: should purge
        $this->artisan('app:purge-trashed --days=7')
            ->assertSuccessful();
        expect(Course::withTrashed()->where('id', $course->id)->exists())->toBeFalse();
    });

    it('reports zero records when nothing to purge', function () {
        $this->artisan('app:purge-trashed --days=30')
            ->expectsOutputToContain('No records to purge')
            ->assertSuccessful();
    });
});
