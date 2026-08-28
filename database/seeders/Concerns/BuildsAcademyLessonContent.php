<?php

namespace Database\Seeders\Concerns;

use App\Models\Assessment;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Media;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Services\SeederLessonMedia;
use Illuminate\Support\Facades\Storage;

trait BuildsAcademyLessonContent
{
    /**
     * @param  list<string>  $expectedTitles
     */
    protected function catalogMatches(Course $course, array $expectedTitles): bool
    {
        $existing = Lesson::query()
            ->join('course_sections', 'lessons.course_section_id', '=', 'course_sections.id')
            ->where('course_sections.course_id', $course->id)
            ->whereNull('lessons.deleted_at')
            ->whereNull('course_sections.deleted_at')
            ->orderBy('course_sections.order')
            ->orderBy('lessons.order')
            ->pluck('lessons.title')
            ->all();

        return $existing === $expectedTitles;
    }

    /**
     * @param  list<array{
     *     title: string,
     *     description?: string,
     *     lessons: list<array<string, mixed>>
     * }>  $sections
     */
    protected function replaceCourseLessons(Course $course, array $sections): void
    {
        Enrollment::query()
            ->where('course_id', $course->id)
            ->update(['last_lesson_id' => null]);

        $assessments = Assessment::withTrashed()->where('course_id', $course->id)->get();
        foreach ($assessments as $assessment) {
            $questions = Question::withTrashed()->where('assessment_id', $assessment->id)->get();
            foreach ($questions as $question) {
                QuestionOption::withTrashed()->where('question_id', $question->id)->forceDelete();
                $question->forceDelete();
            }
            $assessment->forceDelete();
        }

        $existingSections = CourseSection::withTrashed()->where('course_id', $course->id)->get();
        foreach ($existingSections as $section) {
            $lessons = Lesson::withTrashed()->where('course_section_id', $section->id)->get();
            foreach ($lessons as $lesson) {
                $mediaItems = Media::query()
                    ->where('mediable_type', $lesson->getMorphClass())
                    ->where('mediable_id', $lesson->id)
                    ->get();
                foreach ($mediaItems as $media) {
                    Storage::disk($media->disk)->delete($media->path);
                    $media->delete();
                }
                $lesson->forceDelete();
            }
            $section->forceDelete();
        }

        foreach ($sections as $sectionOrder => $sectionData) {
            $section = CourseSection::query()->create([
                'course_id' => $course->id,
                'title' => $sectionData['title'],
                'description' => $sectionData['description'] ?? null,
                'order' => $sectionOrder + 1,
            ]);

            foreach ($sectionData['lessons'] as $lessonOrder => $lessonData) {
                $lesson = Lesson::query()->create([
                    'course_section_id' => $section->id,
                    'title' => $lessonData['title'],
                    'description' => $lessonData['description'] ?? null,
                    'order' => $lessonOrder + 1,
                    'content_type' => $lessonData['content_type'],
                    'rich_content' => $lessonData['rich_content'] ?? null,
                    'youtube_url' => $lessonData['youtube_url'] ?? null,
                    'conference_url' => $lessonData['conference_url'] ?? null,
                    'conference_type' => $lessonData['conference_type'] ?? null,
                    'estimated_duration_minutes' => $lessonData['duration'],
                    'is_free_preview' => $lessonData['is_free_preview'] ?? false,
                ]);

                if (($lessonData['fixture'] ?? null) !== null) {
                    app(SeederLessonMedia::class)->attachFixture(
                        $lesson,
                        $lessonData['fixture']['collection'],
                        $lessonData['fixture']['filename'],
                        $lessonData['fixture']['mime'],
                        $lessonData['fixture']['duration'] ?? null,
                    );
                }

                if (($lessonData['pdf'] ?? null) !== null) {
                    app(SeederLessonMedia::class)->attachPdf(
                        $lesson,
                        $lessonData['pdf']['filename'],
                        $lessonData['pdf']['title'],
                        $lessonData['pdf']['paragraphs'],
                    );
                }
            }

            $section->updateEstimatedDuration();
        }

        $course->updateEstimatedDuration();
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return array{type: string, content: list<array<string, mixed>>}
     */
    protected function doc(array $blocks): array
    {
        return [
            'type' => 'doc',
            'content' => $blocks,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function heading(string $text, int $level = 2): array
    {
        return [
            'type' => 'heading',
            'attrs' => ['level' => $level],
            'content' => [['type' => 'text', 'text' => $text]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function paragraph(string $text): array
    {
        return [
            'type' => 'paragraph',
            'content' => [['type' => 'text', 'text' => $text]],
        ];
    }

    /**
     * @param  list<string>  $items
     * @return array<string, mixed>
     */
    protected function bullets(array $items): array
    {
        return [
            'type' => 'bulletList',
            'content' => array_map(fn (string $item) => [
                'type' => 'listItem',
                'content' => [$this->paragraph($item)],
            ], $items),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function quote(string $text): array
    {
        return [
            'type' => 'blockquote',
            'content' => [$this->paragraph($text)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rule(): array
    {
        return ['type' => 'horizontalRule'];
    }

    /**
     * @param  list<array{option_text: string, is_correct: bool}>  $options
     */
    protected function createMultipleChoice(Assessment $assessment, string $text, array $options, int $order, int $points = 10): void
    {
        $question = Question::query()->create([
            'assessment_id' => $assessment->id,
            'question_text' => $text,
            'question_type' => 'multiple_choice',
            'points' => $points,
            'order' => $order,
        ]);

        foreach ($options as $index => $option) {
            QuestionOption::query()->create([
                'question_id' => $question->id,
                'option_text' => $option['option_text'],
                'is_correct' => $option['is_correct'],
                'order' => $index + 1,
            ]);
        }
    }

    protected function createTrueFalse(Assessment $assessment, string $text, bool $correctIsTrue, int $order, int $points = 10): void
    {
        $question = Question::query()->create([
            'assessment_id' => $assessment->id,
            'question_text' => $text,
            'question_type' => 'true_false',
            'points' => $points,
            'order' => $order,
            'correct_answer' => $correctIsTrue ? 'true' : 'false',
        ]);

        QuestionOption::query()->create([
            'question_id' => $question->id,
            'option_text' => 'Benar',
            'is_correct' => $correctIsTrue,
            'order' => 1,
        ]);

        QuestionOption::query()->create([
            'question_id' => $question->id,
            'option_text' => 'Salah',
            'is_correct' => ! $correctIsTrue,
            'order' => 2,
        ]);
    }

    protected function createManualShortAnswer(Assessment $assessment, string $text, int $order, int $points = 15): void
    {
        Question::query()->create([
            'assessment_id' => $assessment->id,
            'question_text' => $text,
            'question_type' => 'short_answer',
            'points' => $points,
            'order' => $order,
            'correct_answer' => null,
        ]);
    }

    protected function createEssay(Assessment $assessment, string $text, string $rubric, int $order, int $points = 25): void
    {
        Question::query()->create([
            'assessment_id' => $assessment->id,
            'question_text' => $text,
            'question_type' => 'essay',
            'points' => $points,
            'order' => $order,
            'grading_rubric' => $rubric,
        ]);
    }
}
