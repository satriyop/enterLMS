<?php

namespace App\Domain\Enrollment\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class OfferingClosedForEnrollmentException extends DomainException
{
    public function __construct(int $offeringId)
    {
        parent::__construct(
            "Offering {$offeringId} is not open for enrollment.",
            ['offering_id' => $offeringId],
        );
    }
}
