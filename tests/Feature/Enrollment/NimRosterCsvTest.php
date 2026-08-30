<?php

use App\Domain\Shared\Academy;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Offering;
use App\Models\User;
use Illuminate\Http\UploadedFile;

function rosterCsv(string $name): UploadedFile
{
    $path = base_path('tests/fixtures/rosters/'.$name);
    $contents = file_get_contents($path);

    expect($contents)->not->toBeFalse();

    return UploadedFile::fake()->createWithContent($name, (string) $contents);
}

beforeEach(function () {
    Academy::using('academic');

    $this->admin = User::factory()->create(['role' => 'lms_admin']);
    $this->course = Course::factory()->published()->restricted()->create();
    $this->kelasA = Offering::factory()->for($this->course)->create(['name' => 'Kelas A', 'code' => 'a']);
    $this->kelasB = Offering::factory()->for($this->course)->create(['name' => 'Kelas B', 'code' => 'b']);
});

it('grants enrollments from a nim and offering_code csv', function () {
    $one = User::factory()->learner()->create(['external_id' => '21001001']);
    $two = User::factory()->learner()->create(['external_id' => '21001002']);

    $this->actingAs($this->admin)
        ->post(route('courses.bulk-enroll', $this->course), [
            'file' => rosterCsv('happy.csv'),
        ])
        ->assertRedirect(route('courses.show', $this->course))
        ->assertSessionHas('success');

    expect(session('success'))->toContain('2 pengguna berhasil didaftarkan')
        ->and(Enrollment::query()->where('user_id', $one->id)->where('offering_id', $this->kelasA->id)->exists())->toBeTrue()
        ->and(Enrollment::query()->where('user_id', $two->id)->where('offering_id', $this->kelasB->id)->exists())->toBeTrue()
        ->and(Enrollment::query()->where('user_id', $one->id)->value('invited_by'))->toBe($this->admin->id);
});

it('reports an unknown nim as an error and does not invent a user', function () {
    $this->actingAs($this->admin)
        ->post(route('courses.bulk-enroll', $this->course), [
            'file' => rosterCsv('unknown_nim.csv'),
        ])
        ->assertRedirect(route('courses.show', $this->course))
        ->assertSessionHas('error');

    expect(session('error'))->toContain('NIM')
        ->and(session('error'))->toContain('tidak ditemukan')
        ->and(User::query()->where('external_id', '99999999')->exists())->toBeFalse()
        ->and(Enrollment::query()->where('course_id', $this->course->id)->exists())->toBeFalse();
});

it('rejects default offering_code when named offerings exist', function () {
    User::factory()->learner()->create(['external_id' => '21001001']);

    $this->actingAs($this->admin)
        ->post(route('courses.bulk-enroll', $this->course), [
            'file' => rosterCsv('default_when_named.csv'),
        ])
        ->assertRedirect(route('courses.show', $this->course))
        ->assertSessionHas('error');

    expect(session('error'))->toContain('default')
        ->and(Enrollment::query()->where('course_id', $this->course->id)->exists())->toBeFalse();
});

it('skips a learner already enrolled on that offering', function () {
    $learner = User::factory()->learner()->create(['external_id' => '21001001']);

    Enrollment::factory()->active()->create([
        'user_id' => $learner->id,
        'course_id' => $this->course->id,
        'offering_id' => $this->kelasA->id,
    ]);

    $this->actingAs($this->admin)
        ->post(route('courses.bulk-enroll', $this->course), [
            'file' => rosterCsv('duplicate.csv'),
        ])
        ->assertRedirect(route('courses.show', $this->course))
        ->assertSessionHas('success');

    expect(session('success'))->toContain('dilewati')
        ->and(Enrollment::query()->where('user_id', $learner->id)->where('course_id', $this->course->id)->count())->toBe(1);
});

it('rejects a second active enrollment on the same course from the roster', function () {
    $learner = User::factory()->learner()->create(['external_id' => '21001001']);

    Enrollment::factory()->active()->create([
        'user_id' => $learner->id,
        'course_id' => $this->course->id,
        'offering_id' => $this->kelasB->id,
    ]);

    $this->actingAs($this->admin)
        ->post(route('courses.bulk-enroll', $this->course), [
            'file' => rosterCsv('duplicate.csv'),
        ])
        ->assertRedirect(route('courses.show', $this->course))
        ->assertSessionHas('error');

    expect(session('error'))->toContain('sudah terdaftar aktif')
        ->and(Enrollment::query()->where('user_id', $learner->id)->where('offering_id', $this->kelasA->id)->exists())->toBeFalse();
});

it('grants a later offering after the first enrollment is completed', function () {
    $learner = User::factory()->learner()->create(['external_id' => '21001001']);

    $first = Enrollment::factory()->create([
        'user_id' => $learner->id,
        'course_id' => $this->course->id,
        'offering_id' => $this->kelasB->id,
        'status' => 'completed',
        'progress_percentage' => 100,
    ]);
    $first->update(['status' => 'completed']);

    $this->actingAs($this->admin)
        ->post(route('courses.bulk-enroll', $this->course), [
            'file' => rosterCsv('duplicate.csv'),
        ])
        ->assertRedirect(route('courses.show', $this->course))
        ->assertSessionHas('success');

    expect(Enrollment::query()->where('user_id', $learner->id)->where('offering_id', $this->kelasA->id)->exists())->toBeTrue();
});
