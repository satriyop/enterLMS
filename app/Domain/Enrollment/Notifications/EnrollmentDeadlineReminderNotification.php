<?php

namespace App\Domain\Enrollment\Notifications;

use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Reminds learners about upcoming course enrollment deadlines.
 */
class EnrollmentDeadlineReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Course $course,
        public readonly Carbon $deadline,
        public readonly int $daysRemaining
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $urgency = $this->daysRemaining <= 1 ? 'Mendesak' : 'Pengingat';
        $subject = "{$urgency}: Pendaftaran kursus \"{$this->course->title}\" akan ditutup";

        $timeText = $this->daysRemaining <= 1
            ? 'besok'
            : "dalam {$this->daysRemaining} hari";

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Halo, {$notifiable->name}!")
            ->line("Pendaftaran untuk kursus \"{$this->course->title}\" akan ditutup {$timeText}.")
            ->line("Batas waktu pendaftaran: {$this->deadline->translatedFormat('l, d F Y H:i')} WIB")
            ->action('Daftar Sekarang', url("/courses/{$this->course->slug}"))
            ->line('Jangan lewatkan kesempatan untuk mengikuti kursus ini!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'enrollment.deadline_reminder',
            'course_id' => $this->course->id,
            'course_title' => $this->course->title,
            'deadline' => $this->deadline->toIso8601String(),
            'days_remaining' => $this->daysRemaining,
            'message' => "Pendaftaran kursus \"{$this->course->title}\" akan ditutup dalam {$this->daysRemaining} hari",
        ];
    }
}
