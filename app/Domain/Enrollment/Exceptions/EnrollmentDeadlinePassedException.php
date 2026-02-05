<?php

namespace App\Domain\Enrollment\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;
use DateTimeInterface;

class EnrollmentDeadlinePassedException extends DomainException
{
    public function __construct(int $courseId, DateTimeInterface $deadline)
    {
        parent::__construct(
            "Enrollment deadline for course {$courseId} has passed ({$deadline->format('Y-m-d H:i')})",
            [
                'course_id' => $courseId,
                'deadline' => $deadline->format('Y-m-d H:i:s'),
            ]
        );
    }
}
