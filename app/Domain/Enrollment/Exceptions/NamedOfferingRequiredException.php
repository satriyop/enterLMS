<?php

namespace App\Domain\Enrollment\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class NamedOfferingRequiredException extends DomainException
{
    public function __construct(int $courseId)
    {
        parent::__construct(
            "Enrollment on course {$courseId} must name a non-default Offering.",
            ['course_id' => $courseId],
        );
    }
}
