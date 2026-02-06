<?php

namespace App\Console\Commands;

use App\Domain\Enrollment\Notifications\EnrollmentDeadlineReminderNotification;
use App\Models\Course;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendDeadlineReminders extends Command
{
    protected $signature = 'app:send-deadline-reminders
                            {--days=3,1 : Comma-separated days before deadline to send reminders}
                            {--dry-run : Show what would be sent without actually sending}';

    protected $description = 'Send reminder notifications for upcoming enrollment deadlines';

    public function handle(): int
    {
        $daysConfig = collect(explode(',', $this->option('days')))
            ->map(fn ($d) => (int) trim($d))
            ->filter(fn ($d) => $d > 0)
            ->values();

        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('DRY RUN MODE - No notifications will be sent');
        }

        $this->info("Checking for deadlines in: {$daysConfig->implode(', ')} days");

        $totalSent = 0;

        foreach ($daysConfig as $days) {
            $sent = $this->sendRemindersForDay($days, $isDryRun);
            $totalSent += $sent;
        }

        $this->newLine();
        $this->info("Total reminders sent: {$totalSent}");

        Log::channel('single')->info('Deadline reminders processed', [
            'total_sent' => $totalSent,
            'days_checked' => $daysConfig->toArray(),
            'dry_run' => $isDryRun,
        ]);

        return Command::SUCCESS;
    }

    protected function sendRemindersForDay(int $days, bool $isDryRun): int
    {
        $targetDate = now()->addDays($days)->startOfDay();
        $targetDateEnd = now()->addDays($days)->endOfDay();

        $this->info("Checking courses with deadline on {$targetDate->toDateString()}...");

        // Find courses with enrollment deadline in the target day
        $courses = Course::query()
            ->published()
            ->whereBetween('enrollment_deadline', [$targetDate, $targetDateEnd])
            ->get();

        if ($courses->isEmpty()) {
            $this->line("  No courses found with deadline in {$days} day(s)");

            return 0;
        }

        $sentCount = 0;

        foreach ($courses as $course) {
            $sentCount += $this->sendCourseReminders($course, $days, $isDryRun);
        }

        return $sentCount;
    }

    protected function sendCourseReminders(Course $course, int $daysRemaining, bool $isDryRun): int
    {
        // Find learners who might be interested but haven't enrolled yet
        // Strategy: Users who have viewed the course but haven't enrolled
        // For now, we'll send to users who have started but not completed enrollment
        // or users who have the course in a wishlist (if that feature exists)

        // Simple approach: Send to all learners who are NOT enrolled in this course
        // This is broad - in production, you'd want to filter by interest signals

        $enrolledUserIds = DB::table('enrollments')
            ->where('course_id', $course->id)
            ->whereIn('status', ['active', 'completed'])
            ->pluck('user_id');

        // Get learners who have expressed interest (e.g., course view activity)
        // For now, use a simpler approach: learners who have any enrollment in the system
        // but not in this specific course (active learners)
        $potentialLearners = User::query()
            ->where('role', 'learner')
            ->whereNotIn('id', $enrolledUserIds)
            ->whereHas('enrollments') // Only users who have enrolled in at least one course
            ->limit(100) // Prevent overwhelming notifications
            ->get();

        if ($potentialLearners->isEmpty()) {
            $this->line("  Course \"{$course->title}\": No potential learners found");

            return 0;
        }

        $this->line("  Course \"{$course->title}\": {$potentialLearners->count()} learners to notify");

        if ($isDryRun) {
            foreach ($potentialLearners as $learner) {
                $this->line("    [DRY RUN] Would notify: {$learner->email}");
            }

            return 0;
        }

        $notification = new EnrollmentDeadlineReminderNotification(
            $course,
            $course->enrollment_deadline,
            $daysRemaining
        );

        foreach ($potentialLearners as $learner) {
            $learner->notify($notification);
        }

        return $potentialLearners->count();
    }
}
