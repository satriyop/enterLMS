<?php

namespace App\Domain\Course\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class InvitationNotPendingException extends DomainException
{
    public function __construct(int $invitationId)
    {
        parent::__construct(
            'Invitation is not pending',
            ['invitation_id' => $invitationId]
        );
    }
}
