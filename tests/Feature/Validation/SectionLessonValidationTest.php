<?php

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'content_manager']);
    $this->course = Course::factory()->draft()->create(['user_id' => $this->user->id]);
});

describe('Section Store Validation', function () {

    it('requires title', function () {
        $this->actingAs($this->user)
            ->post(route('courses.sections.store', $this->course), [
                'description' => 'Test description',
            ])
            ->assertSessionHasErrors('title');
    });

    it('validates title must be string', function () {
        $this->actingAs($this->user)
            ->post(route('courses.sections.store', $this->course), [
                'title' => 12345,
            ])
            ->assertSessionHasErrors('title');
    });

    it('validates title max length', function (string $title, bool $shouldFail) {
        $response = $this->actingAs($this->user)
            ->post(route('courses.sections.store', $this->course), [
                'title' => $title,
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('title');
        } else {
            $response->assertSessionDoesntHaveErrors('title');
        }
    })->with([
        'at max (255)' => [str_repeat('a', 255), false],
        'exceeds max (256)' => [str_repeat('a', 256), true],
    ]);

    it('allows nullable description', function () {
        $this->actingAs($this->user)
            ->post(route('courses.sections.store', $this->course), [
                'title' => 'Test Section',
            ])
            ->assertSessionDoesntHaveErrors('description');
    });

    it('validates description must be string', function () {
        $this->actingAs($this->user)
            ->post(route('courses.sections.store', $this->course), [
                'title' => 'Test Section',
                'description' => ['not', 'a', 'string'],
            ])
            ->assertSessionHasErrors('description');
    });

});

describe('Section Update Validation', function () {

    it('requires title on update', function () {
        $section = CourseSection::factory()->create(['course_id' => $this->course->id]);

        $this->actingAs($this->user)
            ->patch(route('sections.update', $section), [
                'title' => '',
            ])
            ->assertSessionHasErrors('title');
    });

    it('validates title max length on update', function (string $title, bool $shouldFail) {
        $section = CourseSection::factory()->create(['course_id' => $this->course->id]);

        $response = $this->actingAs($this->user)
            ->patch(route('sections.update', $section), [
                'title' => $title,
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('title');
        } else {
            $response->assertSessionDoesntHaveErrors('title');
        }
    })->with([
        'at max (255)' => [str_repeat('a', 255), false],
        'exceeds max (256)' => [str_repeat('a', 256), true],
    ]);

    it('allows nullable description on update', function () {
        $section = CourseSection::factory()->create(['course_id' => $this->course->id]);

        $this->actingAs($this->user)
            ->patch(route('sections.update', $section), [
                'title' => 'Updated Section',
            ])
            ->assertSessionDoesntHaveErrors('description');
    });

    it('allows nullable estimated_duration_minutes', function () {
        $section = CourseSection::factory()->create(['course_id' => $this->course->id]);

        $this->actingAs($this->user)
            ->patch(route('sections.update', $section), [
                'title' => 'Updated Section',
            ])
            ->assertSessionDoesntHaveErrors('estimated_duration_minutes');
    });

    it('validates estimated_duration_minutes must be integer', function () {
        $section = CourseSection::factory()->create(['course_id' => $this->course->id]);

        $this->actingAs($this->user)
            ->patch(route('sections.update', $section), [
                'title' => 'Updated Section',
                'estimated_duration_minutes' => 'not-a-number',
            ])
            ->assertSessionHasErrors('estimated_duration_minutes');
    });

    it('validates estimated_duration_minutes minimum value', function (int $minutes, bool $shouldFail) {
        $section = CourseSection::factory()->create(['course_id' => $this->course->id]);

        $response = $this->actingAs($this->user)
            ->patch(route('sections.update', $section), [
                'title' => 'Updated Section',
                'estimated_duration_minutes' => $minutes,
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('estimated_duration_minutes');
        } else {
            $response->assertSessionDoesntHaveErrors('estimated_duration_minutes');
        }
    })->with([
        'zero (invalid)' => [0, true],
        'negative (invalid)' => [-1, true],
        'one (valid)' => [1, false],
        'positive (valid)' => [60, false],
    ]);

});

describe('Lesson Store Validation', function () {

    beforeEach(function () {
        $this->section = CourseSection::factory()->create(['course_id' => $this->course->id]);
    });

    it('requires title', function () {
        $this->actingAs($this->user)
            ->post(route('sections.lessons.store', $this->section), [
                'content_type' => 'text',
            ])
            ->assertSessionHasErrors('title');
    });

    it('validates title must be string', function () {
        $this->actingAs($this->user)
            ->post(route('sections.lessons.store', $this->section), [
                'title' => 12345,
                'content_type' => 'text',
            ])
            ->assertSessionHasErrors('title');
    });

    it('validates title max length', function (string $title, bool $shouldFail) {
        $response = $this->actingAs($this->user)
            ->post(route('sections.lessons.store', $this->section), [
                'title' => $title,
                'content_type' => 'text',
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('title');
        } else {
            $response->assertSessionDoesntHaveErrors('title');
        }
    })->with([
        'at max (255)' => [str_repeat('a', 255), false],
        'exceeds max (256)' => [str_repeat('a', 256), true],
    ]);

    it('allows nullable description', function () {
        $this->actingAs($this->user)
            ->post(route('sections.lessons.store', $this->section), [
                'title' => 'Test Lesson',
                'content_type' => 'text',
            ])
            ->assertSessionDoesntHaveErrors('description');
    });

    it('requires content_type', function () {
        $this->actingAs($this->user)
            ->post(route('sections.lessons.store', $this->section), [
                'title' => 'Test Lesson',
            ])
            ->assertSessionHasErrors('content_type');
    });

    it('validates content_type enum', function (string $contentType, bool $shouldFail) {
        $response = $this->actingAs($this->user)
            ->post(route('sections.lessons.store', $this->section), [
                'title' => 'Test Lesson',
                'content_type' => $contentType,
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('content_type');
        } else {
            $response->assertSessionDoesntHaveErrors('content_type');
        }
    })->with([
        'text' => ['text', false],
        'video' => ['video', false],
        'audio' => ['audio', false],
        'document' => ['document', false],
        'youtube' => ['youtube', false],
        'conference' => ['conference', false],
        'invalid value' => ['pdf', true],
        'empty string' => ['', true],
    ]);

    it('allows nullable rich_content', function () {
        $this->actingAs($this->user)
            ->post(route('sections.lessons.store', $this->section), [
                'title' => 'Test Lesson',
                'content_type' => 'text',
            ])
            ->assertSessionDoesntHaveErrors('rich_content');
    });

    it('validates rich_content must be array', function () {
        $this->actingAs($this->user)
            ->post(route('sections.lessons.store', $this->section), [
                'title' => 'Test Lesson',
                'content_type' => 'text',
                'rich_content' => 'not-an-array',
            ])
            ->assertSessionHasErrors('rich_content');
    });

    it('allows nullable youtube_url', function () {
        $this->actingAs($this->user)
            ->post(route('sections.lessons.store', $this->section), [
                'title' => 'Test Lesson',
                'content_type' => 'youtube',
            ])
            ->assertSessionDoesntHaveErrors('youtube_url');
    });

    it('validates youtube_url must be valid URL', function () {
        $this->actingAs($this->user)
            ->post(route('sections.lessons.store', $this->section), [
                'title' => 'Test Lesson',
                'content_type' => 'youtube',
                'youtube_url' => 'not-a-url',
            ])
            ->assertSessionHasErrors('youtube_url');
    });

    it('validates youtube_url matches YouTube domain', function (string $url, bool $shouldFail) {
        $response = $this->actingAs($this->user)
            ->post(route('sections.lessons.store', $this->section), [
                'title' => 'Test Lesson',
                'content_type' => 'youtube',
                'youtube_url' => $url,
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('youtube_url');
        } else {
            $response->assertSessionDoesntHaveErrors('youtube_url');
        }
    })->with([
        'youtube.com' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', false],
        'youtu.be' => ['https://youtu.be/dQw4w9WgXcQ', false],
        'youtube without www' => ['https://youtube.com/watch?v=dQw4w9WgXcQ', false],
        'http protocol' => ['http://www.youtube.com/watch?v=dQw4w9WgXcQ', false],
        'vimeo (invalid)' => ['https://vimeo.com/123456', true],
        'non-youtube URL (invalid)' => ['https://www.example.com', true],
    ]);

    it('allows nullable conference_url', function () {
        $this->actingAs($this->user)
            ->post(route('sections.lessons.store', $this->section), [
                'title' => 'Test Lesson',
                'content_type' => 'conference',
            ])
            ->assertSessionDoesntHaveErrors('conference_url');
    });

    it('validates conference_url must be valid URL', function () {
        $this->actingAs($this->user)
            ->post(route('sections.lessons.store', $this->section), [
                'title' => 'Test Lesson',
                'content_type' => 'conference',
                'conference_url' => 'not-a-url',
            ])
            ->assertSessionHasErrors('conference_url');
    });

    it('allows nullable conference_type', function () {
        $this->actingAs($this->user)
            ->post(route('sections.lessons.store', $this->section), [
                'title' => 'Test Lesson',
                'content_type' => 'conference',
            ])
            ->assertSessionDoesntHaveErrors('conference_type');
    });

    it('validates conference_type enum', function (string $conferenceType, bool $shouldFail) {
        $response = $this->actingAs($this->user)
            ->post(route('sections.lessons.store', $this->section), [
                'title' => 'Test Lesson',
                'content_type' => 'conference',
                'conference_type' => $conferenceType,
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('conference_type');
        } else {
            $response->assertSessionDoesntHaveErrors('conference_type');
        }
    })->with([
        'zoom' => ['zoom', false],
        'google_meet' => ['google_meet', false],
        'other' => ['other', false],
        'invalid value' => ['microsoft_teams', true],
    ]);

    it('allows nullable estimated_duration_minutes', function () {
        $this->actingAs($this->user)
            ->post(route('sections.lessons.store', $this->section), [
                'title' => 'Test Lesson',
                'content_type' => 'text',
            ])
            ->assertSessionDoesntHaveErrors('estimated_duration_minutes');
    });

    it('validates estimated_duration_minutes must be integer', function () {
        $this->actingAs($this->user)
            ->post(route('sections.lessons.store', $this->section), [
                'title' => 'Test Lesson',
                'content_type' => 'text',
                'estimated_duration_minutes' => 'not-a-number',
            ])
            ->assertSessionHasErrors('estimated_duration_minutes');
    });

    it('validates estimated_duration_minutes minimum value', function (int $minutes, bool $shouldFail) {
        $response = $this->actingAs($this->user)
            ->post(route('sections.lessons.store', $this->section), [
                'title' => 'Test Lesson',
                'content_type' => 'text',
                'estimated_duration_minutes' => $minutes,
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('estimated_duration_minutes');
        } else {
            $response->assertSessionDoesntHaveErrors('estimated_duration_minutes');
        }
    })->with([
        'zero (invalid)' => [0, true],
        'negative (invalid)' => [-1, true],
        'one (valid)' => [1, false],
        'positive (valid)' => [60, false],
    ]);

    it('validates is_free_preview must be boolean', function () {
        $this->actingAs($this->user)
            ->post(route('sections.lessons.store', $this->section), [
                'title' => 'Test Lesson',
                'content_type' => 'text',
                'is_free_preview' => 'not-boolean',
            ])
            ->assertSessionHasErrors('is_free_preview');
    });

    it('accepts valid is_free_preview values', function ($value, bool $shouldFail) {
        $response = $this->actingAs($this->user)
            ->post(route('sections.lessons.store', $this->section), [
                'title' => 'Test Lesson',
                'content_type' => 'text',
                'is_free_preview' => $value,
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('is_free_preview');
        } else {
            $response->assertSessionDoesntHaveErrors('is_free_preview');
        }
    })->with([
        'true' => [true, false],
        'false' => [false, false],
        '1' => [1, false],
        '0' => [0, false],
        'invalid string' => ['yes', true],
    ]);

});

describe('Lesson Update Validation', function () {

    beforeEach(function () {
        $this->section = CourseSection::factory()->create(['course_id' => $this->course->id]);
        $this->lesson = Lesson::factory()->create(['course_section_id' => $this->section->id]);
    });

    it('requires title on update', function () {
        $this->actingAs($this->user)
            ->patch(route('lessons.update', $this->lesson), [
                'title' => '',
                'content_type' => 'text',
            ])
            ->assertSessionHasErrors('title');
    });

    it('validates title max length on update', function (string $title, bool $shouldFail) {
        $response = $this->actingAs($this->user)
            ->patch(route('lessons.update', $this->lesson), [
                'title' => $title,
                'content_type' => 'text',
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('title');
        } else {
            $response->assertSessionDoesntHaveErrors('title');
        }
    })->with([
        'at max (255)' => [str_repeat('a', 255), false],
        'exceeds max (256)' => [str_repeat('a', 256), true],
    ]);

    it('requires content_type on update', function () {
        $this->actingAs($this->user)
            ->patch(route('lessons.update', $this->lesson), [
                'title' => 'Updated Lesson',
            ])
            ->assertSessionHasErrors('content_type');
    });

    it('validates content_type enum on update', function (string $contentType, bool $shouldFail) {
        $response = $this->actingAs($this->user)
            ->patch(route('lessons.update', $this->lesson), [
                'title' => 'Updated Lesson',
                'content_type' => $contentType,
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('content_type');
        } else {
            $response->assertSessionDoesntHaveErrors('content_type');
        }
    })->with([
        'text' => ['text', false],
        'video' => ['video', false],
        'audio' => ['audio', false],
        'document' => ['document', false],
        'youtube' => ['youtube', false],
        'conference' => ['conference', false],
        'invalid value' => ['invalid-type', true],
    ]);

    it('allows nullable description on update', function () {
        $this->actingAs($this->user)
            ->patch(route('lessons.update', $this->lesson), [
                'title' => 'Updated Lesson',
                'content_type' => 'text',
            ])
            ->assertSessionDoesntHaveErrors('description');
    });

    it('validates youtube_url format on update', function (string $url, bool $shouldFail) {
        $response = $this->actingAs($this->user)
            ->patch(route('lessons.update', $this->lesson), [
                'title' => 'Updated Lesson',
                'content_type' => 'youtube',
                'youtube_url' => $url,
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('youtube_url');
        } else {
            $response->assertSessionDoesntHaveErrors('youtube_url');
        }
    })->with([
        'valid youtube.com' => ['https://www.youtube.com/watch?v=123', false],
        'valid youtu.be' => ['https://youtu.be/123', false],
        'invalid domain' => ['https://www.vimeo.com/123', true],
    ]);

    it('validates conference_type enum on update', function (string $conferenceType, bool $shouldFail) {
        $response = $this->actingAs($this->user)
            ->patch(route('lessons.update', $this->lesson), [
                'title' => 'Updated Lesson',
                'content_type' => 'conference',
                'conference_type' => $conferenceType,
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('conference_type');
        } else {
            $response->assertSessionDoesntHaveErrors('conference_type');
        }
    })->with([
        'zoom' => ['zoom', false],
        'google_meet' => ['google_meet', false],
        'other' => ['other', false],
        'invalid value' => ['webex', true],
    ]);

    it('validates estimated_duration_minutes minimum on update', function (int $minutes, bool $shouldFail) {
        $response = $this->actingAs($this->user)
            ->patch(route('lessons.update', $this->lesson), [
                'title' => 'Updated Lesson',
                'content_type' => 'text',
                'estimated_duration_minutes' => $minutes,
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('estimated_duration_minutes');
        } else {
            $response->assertSessionDoesntHaveErrors('estimated_duration_minutes');
        }
    })->with([
        'zero (invalid)' => [0, true],
        'one (valid)' => [1, false],
        'positive (valid)' => [90, false],
    ]);

    it('validates is_free_preview boolean on update', function ($value, bool $shouldFail) {
        $response = $this->actingAs($this->user)
            ->patch(route('lessons.update', $this->lesson), [
                'title' => 'Updated Lesson',
                'content_type' => 'text',
                'is_free_preview' => $value,
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('is_free_preview');
        } else {
            $response->assertSessionDoesntHaveErrors('is_free_preview');
        }
    })->with([
        'true' => [true, false],
        'false' => [false, false],
        '1' => [1, false],
        '0' => [0, false],
        'string "1"' => ['1', false],
        'string "0"' => ['0', false],
        'invalid string' => ['maybe', true],
    ]);

});
