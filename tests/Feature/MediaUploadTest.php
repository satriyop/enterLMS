<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_guests_cannot_upload_media(): void
    {
        $response = $this->postJson('/media', [
            'file' => UploadedFile::fake()->create('video.mp4', 1024, 'video/mp4'),
            'mediable_type' => 'lesson',
            'mediable_id' => 1,
            'collection_name' => 'video',
        ]);

        $response->assertStatus(401);
    }

    public function test_learners_cannot_upload_media(): void
    {
        $user = User::factory()->create(['role' => 'learner']);

        $response = $this->actingAs($user)->postJson('/media', [
            'file' => UploadedFile::fake()->create('video.mp4', 1024, 'video/mp4'),
            'mediable_type' => 'lesson',
            'mediable_id' => 1,
            'collection_name' => 'video',
        ]);

        $response->assertForbidden();
    }

    public function test_lms_admins_can_upload_to_any_lesson(): void
    {
        $owner = User::factory()->create(['role' => 'lms_admin']);
        $admin = User::factory()->create(['role' => 'lms_admin']);
        $course = Course::factory()->draft()->create(['user_id' => $owner->id]);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create([
            'course_section_id' => $section->id,
            'content_type' => 'video',
        ]);

        $file = UploadedFile::fake()->create('video.mp4', 1024, 'video/mp4');

        $response = $this->actingAs($admin)->postJson('/media', [
            'file' => $file,
            'mediable_type' => 'lesson',
            'mediable_id' => $lesson->id,
            'collection_name' => 'video',
        ]);

        $response->assertStatus(201);
    }

    public function test_invalid_collection_name_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'lms_admin']);
        $course = Course::factory()->draft()->create(['user_id' => $user->id]);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['course_section_id' => $section->id]);

        $file = UploadedFile::fake()->create('file.txt', 1024);

        $response = $this->actingAs($user)->postJson('/media', [
            'file' => $file,
            'mediable_type' => 'lesson',
            'mediable_id' => $lesson->id,
            'collection_name' => 'invalid_collection',
        ]);

        $response->assertUnprocessable();
    }

    public function test_lms_admins_can_delete_any_media(): void
    {
        $owner = User::factory()->create(['role' => 'lms_admin']);
        $admin = User::factory()->create(['role' => 'lms_admin']);
        $course = Course::factory()->draft()->create(['user_id' => $owner->id]);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['course_section_id' => $section->id]);

        $media = Media::factory()->video()->create([
            'mediable_type' => Lesson::class,
            'mediable_id' => $lesson->id,
        ]);

        Storage::disk('public')->put($media->path, 'fake content');

        $response = $this->actingAs($admin)->deleteJson("/media/{$media->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    public function test_media_file_is_stored_in_correct_path(): void
    {
        $user = User::factory()->create(['role' => 'lms_admin']);
        $course = Course::factory()->draft()->create(['user_id' => $user->id]);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create([
            'course_section_id' => $section->id,
            'content_type' => 'video',
        ]);

        $file = UploadedFile::fake()->create('video.mp4', 1024, 'video/mp4');

        $response = $this->actingAs($user)->postJson('/media', [
            'file' => $file,
            'mediable_type' => 'lesson',
            'mediable_id' => $lesson->id,
            'collection_name' => 'video',
        ]);

        $response->assertStatus(201);

        $media = Media::where('mediable_id', $lesson->id)->first();
        $this->assertStringContains("lessons/{$lesson->id}/video", $media->path);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_media_model_attributes_are_correctly_set(): void
    {
        $user = User::factory()->create(['role' => 'lms_admin']);
        $course = Course::factory()->draft()->create(['user_id' => $user->id]);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create([
            'course_section_id' => $section->id,
            'content_type' => 'video',
        ]);

        $file = UploadedFile::fake()->create('my-video.mp4', 10240, 'video/mp4');

        $response = $this->actingAs($user)->postJson('/media', [
            'file' => $file,
            'mediable_type' => 'lesson',
            'mediable_id' => $lesson->id,
            'collection_name' => 'video',
        ]);

        $media = Media::where('mediable_id', $lesson->id)->first();

        $this->assertEquals('my-video', $media->name);
        $this->assertEquals('my-video.mp4', $media->file_name);
        $this->assertEquals('video/mp4', $media->mime_type);
        $this->assertEquals('public', $media->disk);
        $this->assertTrue($media->is_video);
        $this->assertFalse($media->is_audio);
        $this->assertFalse($media->is_document);
    }

    public function test_upload_to_nonexistent_lesson_returns_404(): void
    {
        $user = User::factory()->create(['role' => 'lms_admin']);

        $file = UploadedFile::fake()->create('video.mp4', 1024, 'video/mp4');

        $response = $this->actingAs($user)->postJson('/media', [
            'file' => $file,
            'mediable_type' => 'lesson',
            'mediable_id' => 99999,
            'collection_name' => 'video',
        ]);

        $response->assertNotFound();
    }

    protected function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'"
        );
    }
}
