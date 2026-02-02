<?php

namespace App\Domain\Enrollment\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class PaymentRequiredException extends DomainException
{
    public function __construct(int $courseId, ?float $price = null)
    {
        parent::__construct(
            "Course {$courseId} requires payment before enrollment",
            [
                'course_id' => $courseId,
                'price' => $price,
            ]
        );
    }
}
