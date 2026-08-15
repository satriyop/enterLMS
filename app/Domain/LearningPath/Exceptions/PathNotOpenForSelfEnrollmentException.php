<?php

namespace App\Domain\LearningPath\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

class PathNotOpenForSelfEnrollmentException extends DomainException
{
    public function __construct(int $pathId)
    {
        parent::__construct(
            "Learning path {$pathId} is not open for self-enrollment",
            [
                'learning_path_id' => $pathId,
            ]
        );
    }
}
