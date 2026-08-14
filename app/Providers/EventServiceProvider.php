<?php

namespace App\Providers;

use App\Domain\Agent\Listeners\DispatchAgentWebhooks;
use App\Domain\Assessment\Events\AssessmentAttemptStarted;
use App\Domain\Assessment\Events\AssessmentAttemptSubmitted;
use App\Domain\Assessment\Events\AssessmentGraded;
use App\Domain\Assessment\Listeners\UpdateProgressOnAssessmentGraded;
use App\Domain\Certificate\Events\CertificateIssued;
use App\Domain\Certificate\Listeners\IssueCertificateOnCompletion;
use App\Domain\Course\Events\CourseArchived;
use App\Domain\Course\Events\CoursePublished;
use App\Domain\Course\Events\CourseUnpublished;
use App\Domain\Course\Listeners\LogCourseLifecycleImpact;
use App\Domain\Enrollment\Events\CourseStarted;
use App\Domain\Enrollment\Events\EnrollmentCompleted;
use App\Domain\Enrollment\Events\UserDropped;
use App\Domain\Enrollment\Events\UserEnrolled;
use App\Domain\Enrollment\Events\UserReenrolled;
use App\Domain\Enrollment\Listeners\SendCompletionCongratulations;
use App\Domain\Enrollment\Listeners\SendWelcomeNotification;
use App\Domain\LearningPath\Events\CourseUnlockedInPath;
use App\Domain\LearningPath\Events\PathCompleted;
use App\Domain\LearningPath\Events\PathDropped;
use App\Domain\LearningPath\Events\PathEnrollmentCreated;
use App\Domain\LearningPath\Events\PathProgressUpdated;
use App\Domain\LearningPath\Listeners\SendPathCompletionCongratulations;
use App\Domain\LearningPath\Listeners\SendPathEnrollmentWelcome;
use App\Domain\LearningPath\Listeners\UpdatePathProgressOnCourseCompletion;
use App\Domain\LearningPath\Listeners\UpdatePathProgressOnCourseDrop;
use App\Domain\Progress\Events\LessonCompleted;
use App\Domain\Progress\Events\LessonDeleted;
use App\Domain\Progress\Events\ProgressUpdated;
use App\Domain\Progress\Listeners\RecalculateProgressOnLessonDeletion;
use App\Domain\Shared\Listeners\LogDomainEvent;
use App\Domain\Xapi\Listeners\RecordXapiOnAssessmentGraded;
use App\Domain\Xapi\Listeners\RecordXapiOnCourseStarted;
use App\Domain\Xapi\Listeners\RecordXapiOnEnrollmentCompleted;
use App\Domain\Xapi\Listeners\RecordXapiOnLessonCompleted;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * Array-callable listeners cannot live here — the parent declares this
     * property as class-string listeners only. DispatchAgentWebhooks handles
     * several events from one class, so it is registered in boot() instead.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Assessment Events
        AssessmentAttemptStarted::class => [
            LogDomainEvent::class,
        ],
        AssessmentAttemptSubmitted::class => [
            LogDomainEvent::class,
        ],
        AssessmentGraded::class => [
            LogDomainEvent::class,
            UpdateProgressOnAssessmentGraded::class,
            RecordXapiOnAssessmentGraded::class,
        ],

        // Course Events
        CoursePublished::class => [
            LogDomainEvent::class,
        ],
        CourseUnpublished::class => [
            LogDomainEvent::class,
            LogCourseLifecycleImpact::class,
        ],
        CourseArchived::class => [
            LogDomainEvent::class,
            LogCourseLifecycleImpact::class,
        ],

        // Enrollment Events
        UserEnrolled::class => [
            LogDomainEvent::class,
            SendWelcomeNotification::class,
        ],
        CourseStarted::class => [
            LogDomainEvent::class,
            RecordXapiOnCourseStarted::class,
        ],
        EnrollmentCompleted::class => [
            LogDomainEvent::class,
            SendCompletionCongratulations::class,
            IssueCertificateOnCompletion::class,
            UpdatePathProgressOnCourseCompletion::class,
            RecordXapiOnEnrollmentCompleted::class,
        ],
        CertificateIssued::class => [
            LogDomainEvent::class,
        ],
        UserDropped::class => [
            LogDomainEvent::class,
            UpdatePathProgressOnCourseDrop::class,
        ],
        UserReenrolled::class => [
            LogDomainEvent::class,
        ],

        // Progress Events
        LessonCompleted::class => [
            LogDomainEvent::class,
            RecordXapiOnLessonCompleted::class,
        ],
        LessonDeleted::class => [
            LogDomainEvent::class,
            RecalculateProgressOnLessonDeletion::class,
        ],
        ProgressUpdated::class => [
            // LogDomainEvent::class, // Too noisy - uncomment if needed
        ],

        // Learning Path Events
        PathEnrollmentCreated::class => [
            LogDomainEvent::class,
            SendPathEnrollmentWelcome::class,
        ],
        PathCompleted::class => [
            LogDomainEvent::class,
            SendPathCompletionCongratulations::class,
        ],
        PathDropped::class => [
            LogDomainEvent::class,
        ],
        CourseUnlockedInPath::class => [
            LogDomainEvent::class,
        ],
        PathProgressUpdated::class => [
            // LogDomainEvent::class, // Too noisy - uncomment if needed
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();

        // One class, several events: registered here because $listen is typed
        // for class-string listeners only.
        Event::listen(UserEnrolled::class, [DispatchAgentWebhooks::class, 'handleUserEnrolled']);
        Event::listen(EnrollmentCompleted::class, [DispatchAgentWebhooks::class, 'handleEnrollmentCompleted']);
        Event::listen(CertificateIssued::class, [DispatchAgentWebhooks::class, 'handleCertificateIssued']);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
