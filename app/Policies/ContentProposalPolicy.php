<?php

namespace App\Policies;

use App\Models\ContentProposal;
use App\Models\Course;
use App\Models\User;

class ContentProposalPolicy
{
    public function create(User $user, Course $course): bool
    {
        return $user->isLmsAdmin();
    }

    public function accept(User $user, ContentProposal $proposal): bool
    {
        return $user->isLmsAdmin();
    }

    public function reject(User $user, ContentProposal $proposal): bool
    {
        return $user->isLmsAdmin();
    }
}
