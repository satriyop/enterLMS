<?php

namespace App\Domain\Agent\Listeners;

use App\Domain\Agent\Services\AgentWebhookDispatcher;
use App\Domain\Certificate\Events\CertificateIssued;
use App\Domain\Enrollment\Events\EnrollmentCompleted;
use App\Domain\Enrollment\Events\UserEnrolled;
use App\Domain\Shared\Contracts\DomainEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Pushes selected domain events to registered agent webhook endpoints.
 */
class DispatchAgentWebhooks implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(
        protected AgentWebhookDispatcher $dispatcher,
    ) {}

    public function handleUserEnrolled(UserEnrolled $event): void
    {
        $this->push($event, 'enrollment.created');
    }

    public function handleEnrollmentCompleted(EnrollmentCompleted $event): void
    {
        $this->push($event, 'enrollment.completed');
    }

    public function handleCertificateIssued(CertificateIssued $event): void
    {
        $this->push($event, 'certificate.issued');
    }

    protected function push(DomainEvent $event, string $eventName): void
    {
        $payload = [
            'event' => $eventName,
            'event_id' => $event->eventId,
            'occurred_at' => $event->occurredAt->format(DATE_ATOM),
            'data' => $event->getMetadata(),
        ];

        $this->dispatcher->dispatch($eventName, $payload, $event->eventId);
    }
}
