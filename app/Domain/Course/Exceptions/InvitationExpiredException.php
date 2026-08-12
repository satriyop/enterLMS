<?php

namespace App\Domain\Course\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class InvitationExpiredException extends DomainException
{
    public function __construct(int $invitationId)
    {
        parent::__construct(
            'Invitation has expired',
            ['invitation_id' => $invitationId]
        );
    }
}
