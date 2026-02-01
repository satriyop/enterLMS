<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Media;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Authorization for media delegates to the parent model (Course or Lesson).
 * Media operations require 'update' permission on the owning model.
 */
class MediaPolicy
{
    /**
     * Determine whether the user can create media for a given parent.
     */
    public function create(User $user, Course|Lesson $mediable): bool
    {
        return Gate::allows('update', $mediable);
    }

    /**
     * Determine whether the user can delete the media.
     */
    public function delete(User $user, Media $media): bool
    {
        $mediable = $media->mediable;

        if (! $mediable) {
            return false;
        }

        return Gate::allows('update', $mediable);
    }
}
