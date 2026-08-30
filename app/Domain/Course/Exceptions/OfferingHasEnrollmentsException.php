<?php

namespace App\Domain\Course\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class OfferingHasEnrollmentsException extends DomainException
{
    public function __construct(int $offeringId)
    {
        parent::__construct(
            "Offering {$offeringId} still has enrollments.",
            ['offering_id' => $offeringId],
        );
    }
}
