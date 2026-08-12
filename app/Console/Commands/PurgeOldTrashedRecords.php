<?php

namespace App\Console\Commands;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AttemptAnswer;
use App\Models\Course;
use App\Models\CourseRating;
use App\Models\CourseSection;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class PurgeOldTrashedRecords extends Command
{
    protected $signature = 'app:purge-trashed {--days=30 : Days after which trashed records are permanently deleted} {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Permanently delete records that have been in trash longer than the specified number of days';

    /**
     * Models processed in parent→child order so cascading deletes work naturally.
     * All models must use SoftDeletes trait.
     *
     * @var list<class-string<\Illuminate\Database\Eloquent\Model>>
     */
    protected array $models = [
        User::class,
        LearningPath::class,
        Course::class,
        CourseSection::class,
        Lesson::class,
        // Enrollment: no SoftDeletes — lifecycle is Spatie status only
        Assessment::class,
        Question::class,
        QuestionOption::class,
        AssessmentAttempt::class,
        AttemptAnswer::class,
        CourseRating::class,
    ];

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now()->subDays($days);
        $totalPurged = 0;

        $this->info(sprintf(
            '%s records trashed before %s (%d days)...',
            $dryRun ? 'DRY RUN — Would purge' : 'Purging',
            $cutoff->toDateString(),
            $days,
        ));

        $this->newLine();

        foreach ($this->models as $modelClass) {
            /** @phpstan-ignore staticMethod.notFound (onlyTrashed via SoftDeletes — PHPStan can't resolve class-string intersection types) */
            $query = $modelClass::onlyTrashed()->where('deleted_at', '<', $cutoff);
            $count = $query->count();

            if ($count === 0) {
                continue;
            }

            $label = class_basename($modelClass);

            if ($dryRun) {
                $this->line("  {$label}: {$count} records would be purged");
                $totalPurged += $count;

                continue;
            }

            // Clean up file storage for courses with thumbnails
            if ($modelClass === Course::class) {
                $query->clone()
                    ->whereNotNull('thumbnail_path')
                    ->pluck('thumbnail_path')
                    ->each(fn (string $path) => Storage::disk('public')->delete($path));
            }

            $query->forceDelete();
            $this->line("  {$label}: {$count} records purged");
            $totalPurged += $count;
        }

        $this->newLine();

        if ($totalPurged === 0) {
            $this->info('No records to purge.');
        } else {
            $this->info(sprintf(
                '%s %d records across all models.',
                $dryRun ? 'Would purge' : 'Purged',
                $totalPurged,
            ));
        }

        return self::SUCCESS;
    }
}
