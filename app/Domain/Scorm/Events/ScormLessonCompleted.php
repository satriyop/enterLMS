<?php

namespace App\Domain\Scorm\Events;

use App\Domain\Shared\Contracts\DomainEvent;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\ScormScoTracking;

class ScormLessonCompleted extends DomainEvent
{
    public function __construct(
        public readonly Enrollment $enrollment,
        public readonly Lesson $lesson,
        public readonly ScormScoTracking $tracking,
        ?int $actorId = null
    ) {
        parent::__construct($actorId);
    }

    public function getEventName(): string
    {
        return 'scorm.lesson_completed';
    }

    public function getMetadata(): array
    {
        return [
            'enrollment_id' => $this->enrollment->id,
            'user_id' => $this->enrollment->user_id,
            'lesson_id' => $this->lesson->id,
            'lesson_title' => $this->lesson->title,
            'course_id' => $this->enrollment->course_id,
            'scorm_package_id' => $this->tracking->scorm_package_id,
            'score_raw' => $this->tracking->score_raw,
            'lesson_status' => $this->tracking->lesson_status,
            'completion_status' => $this->tracking->completion_status,
        ];
    }

    public function getAggregateId(): int|string
    {
        return $this->enrollment->id;
    }

    public function getAggregateType(): string
    {
        return 'enrollment';
    }
}
