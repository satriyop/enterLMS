<?php

namespace App\Domain\QuestionBank\DTOs;

/**
 * @phpstan-type OptionData array{
 *     id?: int|null,
 *     option_text: string,
 *     is_correct: bool,
 *     feedback?: string|null,
 *     order?: int
 * }
 */
final readonly class QuestionBankItemData
{
    /**
     * @param  array<int, OptionData>  $options
     * @param  array<int, int>  $tag_ids
     */
    public function __construct(
        public int $user_id,
        public string $question_text,
        public string $question_type,
        public int $default_points = 1,
        public ?string $feedback = null,
        public ?string $correct_answer = null,
        public bool $case_sensitive = false,
        public ?string $grading_rubric = null,
        public ?string $difficulty_level = null,
        public string $visibility = 'private',
        public array $options = [],
        public array $tag_ids = [],
    ) {}

    /**
     * @param  array{
     *     user_id: int,
     *     question_text: string,
     *     question_type: string,
     *     default_points?: int,
     *     feedback?: string|null,
     *     correct_answer?: string|null,
     *     case_sensitive?: bool,
     *     grading_rubric?: string|null,
     *     difficulty_level?: string|null,
     *     visibility?: string,
     *     options?: array<int, OptionData>,
     *     tag_ids?: array<int, int>
     * }  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            user_id: $data['user_id'],
            question_text: $data['question_text'],
            question_type: $data['question_type'],
            default_points: $data['default_points'] ?? 1,
            feedback: $data['feedback'] ?? null,
            correct_answer: $data['correct_answer'] ?? null,
            case_sensitive: $data['case_sensitive'] ?? false,
            grading_rubric: $data['grading_rubric'] ?? null,
            difficulty_level: $data['difficulty_level'] ?? null,
            visibility: $data['visibility'] ?? 'private',
            options: $data['options'] ?? [],
            tag_ids: $data['tag_ids'] ?? [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toModelData(): array
    {
        return [
            'user_id' => $this->user_id,
            'question_text' => $this->question_text,
            'question_type' => $this->question_type,
            'default_points' => $this->default_points,
            'feedback' => $this->feedback,
            'correct_answer' => $this->correct_answer,
            'case_sensitive' => $this->case_sensitive,
            'grading_rubric' => $this->grading_rubric,
            'difficulty_level' => $this->difficulty_level,
            'visibility' => $this->visibility,
        ];
    }
}
