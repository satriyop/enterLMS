<?php

namespace App\Domain\Course\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class CannotDeleteDefaultOfferingException extends DomainException
{
    public function __construct(int $offeringId)
    {
        parent::__construct(
            "Default offering {$offeringId} cannot be deleted.",
            ['offering_id' => $offeringId],
        );
    }
}
