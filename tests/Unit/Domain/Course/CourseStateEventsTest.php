<?php

use App\Domain\Course\Events\CourseArchived;
use App\Domain\Course\Events\CoursePublished;
use App\Domain\Course\Events\CourseUnpublished;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'lms_admin']);
});

describe('Course state event dispatching', function () {
    it('dispatches CoursePublished when publish() is called', function () {
        Event::fake([CoursePublished::class]);

        $course = Course::factory()->create(['status' => 'draft']);
        $course->publish($this->admin);

        Event::assertDispatched(CoursePublished::class, function (CoursePublished $event) use ($course) {
            return $event->course->id === $course->id
                && $event->previousStatus === 'draft'
                && $event->actorId === $this->admin->id;
        });
    });

    it('dispatches CourseUnpublished when unpublish() is called', function () {
        Event::fake([CourseUnpublished::class]);

        $course = Course::factory()->published()->create();
        $course->unpublish();

        Event::assertDispatched(CourseUnpublished::class, function (CourseUnpublished $event) use ($course) {
            return $event->course->id === $course->id
                && $event->previousStatus === 'published';
        });
    });

    it('dispatches CourseArchived when archive() is called', function () {
        Event::fake([CourseArchived::class]);

        $course = Course::factory()->published()->create();
        $course->archive();

        Event::assertDispatched(CourseArchived::class, function (CourseArchived $event) use ($course) {
            return $event->course->id === $course->id
                && $event->previousStatus === 'published';
        });
    });

    it('dispatches CoursePublished via updateStatus()', function () {
        Event::fake([CoursePublished::class]);

        $course = Course::factory()->create(['status' => 'draft']);
        $course->updateStatus('published', $this->admin);

        Event::assertDispatched(CoursePublished::class, function (CoursePublished $event) use ($course) {
            return $event->course->id === $course->id
                && $event->actorId === $this->admin->id;
        });
    });

    it('dispatches CourseUnpublished via updateStatus()', function () {
        Event::fake([CourseUnpublished::class]);

        $course = Course::factory()->published()->create();
        $course->updateStatus('draft', $this->admin);

        Event::assertDispatched(CourseUnpublished::class, function (CourseUnpublished $event) use ($course) {
            return $event->course->id === $course->id;
        });
    });

    it('dispatches CourseArchived via updateStatus()', function () {
        Event::fake([CourseArchived::class]);

        $course = Course::factory()->published()->create();
        $course->updateStatus('archived', $this->admin);

        Event::assertDispatched(CourseArchived::class, function (CourseArchived $event) use ($course) {
            return $event->course->id === $course->id;
        });
    });
});
