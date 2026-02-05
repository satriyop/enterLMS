<?php

namespace App\Domain\Enrollment\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class EnrollmentCapacityExceededException extends DomainException
{
    public function __construct(int $courseId, int $maxEnrollments)
    {
        parent::__construct(
            "Course {$courseId} has reached maximum capacity ({$maxEnrollments} enrollments)",
            [
                'course_id' => $courseId,
                'max_enrollments' => $maxEnrollments,
            ]
        );
    }
}
