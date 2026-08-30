<?php

use App\Domain\Shared\Academy;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Offering;
use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    Academy::using('academic');

    $this->facilitator = User::factory()->create(['role' => 'learner']);
    $this->learner = User::factory()->learner()->create(['external_id' => '21001001']);
    $this->course = Course::factory()->published()->restricted()->create();
    $this->kelasA = Offering::factory()->for($this->course)->create([
        'name' => 'Kelas A',
        'code' => 'a',
        'facilitator_id' => $this->facilitator->id,
    ]);
    $this->kelasB = Offering::factory()->for($this->course)->create([
        'name' => 'Kelas B',
        'code' => 'b',
    ]);
});

it('lets a facilitator grant a learner onto their offering without being enrolled', function () {
    expect(Enrollment::query()->where('user_id', $this->facilitator->id)->exists())->toBeFalse();

    $this->actingAs($this->facilitator)
        ->post(route('courses.bulk-enroll', $this->course), [
            'user_ids' => [$this->learner->id],
            'offering_id' => $this->kelasA->id,
        ])
        ->assertRedirect(route('courses.show', $this->course));

    $enrollment = Enrollment::query()
        ->where('user_id', $this->learner->id)
        ->where('offering_id', $this->kelasA->id)
        ->first();

    expect($enrollment)->not->toBeNull()
        ->and($enrollment->invited_by)->toBe($this->facilitator->id);
});

it('forbids a facilitator from granting onto another offering', function () {
    $this->actingAs($this->facilitator)
        ->post(route('courses.bulk-enroll', $this->course), [
            'user_ids' => [$this->learner->id],
            'offering_id' => $this->kelasB->id,
        ])
        ->assertForbidden();

    expect(Enrollment::query()->where('user_id', $this->learner->id)->exists())->toBeFalse();
});

it('lets a facilitator import a nim roster only for offerings they facilitate', function () {
    $file = UploadedFile::fake()->createWithContent(
        'roster.csv',
        file_get_contents(base_path('tests/fixtures/rosters/happy.csv')),
    );

    $other = User::factory()->learner()->create(['external_id' => '21001002']);

    $this->actingAs($this->facilitator)
        ->post(route('courses.bulk-enroll', $this->course), [
            'file' => $file,
        ])
        ->assertRedirect(route('courses.show', $this->course));

    expect(Enrollment::query()->where('user_id', $this->learner->id)->where('offering_id', $this->kelasA->id)->exists())->toBeTrue()
        ->and(Enrollment::query()->where('user_id', $other->id)->where('offering_id', $this->kelasB->id)->exists())->toBeFalse();
});

it('forbids a facilitator from publishing a course', function () {
    $draft = Course::factory()->draft()->create();
    Offering::factory()->for($draft)->create([
        'facilitator_id' => $this->facilitator->id,
        'code' => 'a',
    ]);

    $this->actingAs($this->facilitator)
        ->post(route('courses.publish', $draft))
        ->assertForbidden();
});
