<?php

namespace App\Domain\Xapi\Services;

use App\Domain\Xapi\DTOs\XapiStatementData;
use App\Domain\Xapi\Events\XapiStatementRecorded;
use App\Models\User;
use App\Models\XapiStatement;
use Illuminate\Support\Str;

class XapiStatementService
{
    /**
     * xAPI verb IRI constants.
     */
    public const VERB_COMPLETED = 'http://adlnet.gov/expapi/verbs/completed';

    public const VERB_LAUNCHED = 'http://adlnet.gov/expapi/verbs/launched';

    public const VERB_SCORED = 'http://adlnet.gov/expapi/verbs/scored';

    public const VERB_PASSED = 'http://adlnet.gov/expapi/verbs/passed';

    public const VERB_FAILED = 'http://adlnet.gov/expapi/verbs/failed';

    public const VERB_EXPERIENCED = 'http://adlnet.gov/expapi/verbs/experienced';

    /**
     * Record a new xAPI statement.
     */
    public function record(XapiStatementData $data): XapiStatement
    {
        // Resolve actor info if we have a user ID
        $actorMbox = $data->actorMbox;
        $actorName = $data->actorName;

        if ($data->actorId && (! $actorMbox || ! $actorName)) {
            $user = User::find($data->actorId);
            if ($user) {
                $actorMbox = $actorMbox ?? "mailto:{$user->email}";
                $actorName = $actorName ?? $user->name;
            }
        }

        $statement = XapiStatement::create([
            'statement_id' => Str::uuid()->toString(),
            'actor_id' => $data->actorId,
            'actor_mbox' => $actorMbox,
            'actor_name' => $actorName,
            'verb_id' => $data->verbId,
            'verb_display' => $data->verbDisplay,
            'object_type' => $data->objectType,
            'object_id' => $data->objectId,
            'object_name' => $data->objectName,
            'result_score_raw' => $data->resultScoreRaw,
            'result_score_min' => $data->resultScoreMin,
            'result_score_max' => $data->resultScoreMax,
            'result_score_scaled' => $data->resultScoreScaled,
            'result_success' => $data->resultSuccess,
            'result_completion' => $data->resultCompletion,
            'result_duration' => $data->resultDuration,
            'context_registration' => $data->contextRegistration,
            'context_course_id' => $data->contextCourseId,
            'context_enrollment_id' => $data->contextEnrollmentId,
            'context_extensions' => $data->contextExtensions,
            'timestamp' => now(),
            'stored' => now(),
            'source' => $data->source,
        ]);

        XapiStatementRecorded::dispatch($statement, $data->actorId);

        return $statement;
    }

    /**
     * Build the activity IRI for a given entity.
     */
    public function buildActivityId(string $type, int $id): string
    {
        $baseUrl = config('app.url', 'http://enterlms.test');

        return "{$baseUrl}/activities/{$type}/{$id}";
    }
}
