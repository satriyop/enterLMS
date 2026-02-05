<?php

namespace App\Domain\Course\Notifications;

use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies learners when course access changes due to unpublishing or archiving.
 */
class CourseAccessChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Course $course,
        public readonly string $eventType
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
        $subject = $this->eventType === 'unpublished'
            ? "Perhatian: Kursus \"{$this->course->title}\" sementara tidak tersedia"
            : "Perhatian: Kursus \"{$this->course->title}\" telah diarsipkan";

        $message = $this->eventType === 'unpublished'
            ? 'Kursus ini sementara tidak tersedia untuk akses. Tim kami sedang melakukan pembaruan konten.'
            : 'Kursus ini telah diarsipkan dan tidak lagi tersedia untuk akses. Silakan hubungi administrator untuk informasi lebih lanjut.';

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("Halo, {$notifiable->name}!")
            ->line("Kami ingin menginformasikan bahwa kursus \"{$this->course->title}\" yang Anda ikuti mengalami perubahan status.")
            ->line($message);

        if ($this->eventType === 'unpublished') {
            $mail->line('Kami akan menghubungi Anda kembali ketika kursus sudah tersedia.');
        }

        return $mail->line('Terima kasih atas pengertian Anda.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'course.access_changed',
            'event_type' => $this->eventType,
            'course_id' => $this->course->id,
            'course_title' => $this->course->title,
            'message' => $this->eventType === 'unpublished'
                ? "Kursus \"{$this->course->title}\" sementara tidak tersedia"
                : "Kursus \"{$this->course->title}\" telah diarsipkan",
        ];
    }
}
