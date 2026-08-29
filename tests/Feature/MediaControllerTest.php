<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\Media;
use App\Models\User;
use App\Services\SeederLessonMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature tests for MediaController.
 *
 * Tests media upload and deletion authorization for courses and lessons.
 * Critical for ensuring proper access control to media operations.
 *
 * Test Matrix:
 * - User Roles: learner, content_manager, trainer, lms_admin
 * - Mediable Types: course, lesson
 * - Course Ownership: owner vs non-owner
 * - Course Status: draft, published
 * - Collection Names: default, video, audio, document, thumbnail
 */
class MediaControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $lmsAdmin;

    private User $contentManager;

    private User $otherContentManager;

    private User $learner;

    private User $trainer;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->lmsAdmin = User::factory()->create(['role' => 'lms_admin']);
        $this->contentManager = User::factory()->create(['role' => 'lms_admin']);
        $this->otherContentManager = User::factory()->create(['role' => 'lms_admin']);
        $this->learner = User::factory()->create(['role' => 'learner']);
        $this->trainer = User::factory()->create(['role' => 'lms_admin']);
    }

    // ========== Authorization Tests ==========

    public function test_learner_cannot_upload_media(): void
    {
        $course = Course::factory()->create(['user_id' => $this->contentManager->id]);

        $response = $this->actingAs($this->learner)->postJson('/media', [
            'file' => UploadedFile::fake()->image('photo.jpg'),
            'mediable_type' => 'course',
            'mediable_id' => $course->id,
            'collection_name' => 'thumbnail',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_upload_media_to_any_course(): void
    {
        $course = Course::factory()->published()->create([
            'user_id' => $this->contentManager->id,
        ]);

        $file = UploadedFile::fake()->image('thumbnail.jpg');

        $response = $this->actingAs($this->lmsAdmin)->postJson('/media', [
            'file' => $file,
            'mediable_type' => 'course',
            'mediable_id' => $course->id,
            'collection_name' => 'thumbnail',
        ]);

        $response->assertStatus(201);
    }

    // ========== Validation Tests ==========

    public function test_upload_validates_required_fields(): void
    {
        $response = $this->actingAs($this->contentManager)->postJson('/media', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['file', 'mediable_type', 'mediable_id']);
    }

    public function test_upload_validates_file_field(): void
    {
        $course = Course::factory()->create(['user_id' => $this->contentManager->id]);

        $response = $this->actingAs($this->contentManager)->postJson('/media', [
            'file' => 'not-a-file',
            'mediable_type' => 'course',
            'mediable_id' => $course->id,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_upload_validates_mediable_type(): void
    {
        $course = Course::factory()->create(['user_id' => $this->contentManager->id]);

        $response = $this->actingAs($this->contentManager)->postJson('/media', [
            'file' => UploadedFile::fake()->image('photo.jpg'),
            'mediable_type' => 'invalid_type',
            'mediable_id' => $course->id,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['mediable_type']);
    }

    public function test_upload_validates_collection_name(): void
    {
        $course = Course::factory()->create(['user_id' => $this->contentManager->id]);

        $response = $this->actingAs($this->contentManager)->postJson('/media', [
            'file' => UploadedFile::fake()->image('photo.jpg'),
            'mediable_type' => 'course',
            'mediable_id' => $course->id,
            'collection_name' => 'invalid_collection',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['collection_name']);
    }

    public function test_upload_with_invalid_mediable_id_returns_404(): void
    {
        $response = $this->actingAs($this->contentManager)->postJson('/media', [
            'file' => UploadedFile::fake()->image('photo.jpg'),
            'mediable_type' => 'course',
            'mediable_id' => 99999,
            'collection_name' => 'thumbnail',
        ]);

        $response->assertNotFound();
        $response->assertJson([
            'message' => 'Model tidak ditemukan.',
        ]);
    }

    // ========== Lesson Upload Tests ==========

    public function test_admin_can_upload_media_to_any_lesson(): void
    {
        $course = Course::factory()->create(['user_id' => $this->contentManager->id]);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['course_section_id' => $section->id]);

        $file = UploadedFile::fake()->create('video.mp4', 1024, 'video/mp4');

        $response = $this->actingAs($this->lmsAdmin)->postJson('/media', [
            'file' => $file,
            'mediable_type' => 'lesson',
            'mediable_id' => $lesson->id,
            'collection_name' => 'video',
        ]);

        $response->assertStatus(201);
    }

    // ========== Collection-Specific Tests ==========

    public function test_upload_video_with_correct_mime_type(): void
    {
        $course = Course::factory()->create([
            'user_id' => $this->contentManager->id,
            'status' => 'draft',
        ]);

        $file = UploadedFile::fake()->create('video.mp4', 1024, 'video/mp4');

        $response = $this->actingAs($this->contentManager)->postJson('/media', [
            'file' => $file,
            'mediable_type' => 'course',
            'mediable_id' => $course->id,
            'collection_name' => 'video',
        ]);

        $response->assertStatus(201);
    }

    public function test_upload_audio_with_correct_mime_type(): void
    {
        $course = Course::factory()->create([
            'user_id' => $this->contentManager->id,
            'status' => 'draft',
        ]);

        $file = UploadedFile::fake()->create('audio.mp3', 1024, 'audio/mpeg');

        $response = $this->actingAs($this->contentManager)->postJson('/media', [
            'file' => $file,
            'mediable_type' => 'course',
            'mediable_id' => $course->id,
            'collection_name' => 'audio',
        ]);

        $response->assertStatus(201);
    }

    public function test_upload_document_with_correct_mime_type(): void
    {
        $course = Course::factory()->create([
            'user_id' => $this->contentManager->id,
            'status' => 'draft',
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->contentManager)->postJson('/media', [
            'file' => $file,
            'mediable_type' => 'course',
            'mediable_id' => $course->id,
            'collection_name' => 'document',
        ]);

        $response->assertCreated();

        $media = Media::query()->find($response->json('media.id'));
        $this->assertNotNull($media);
        $this->assertSame('', $media->storedBodyText());
        $this->assertSame('failed', $media->custom_properties['body_capture'] ?? null);
    }

    public function test_upload_generated_pdf_stores_captured_body_text(): void
    {
        $course = Course::factory()->draft()->create([
            'user_id' => $this->lmsAdmin->id,
        ]);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create([
            'course_section_id' => $section->id,
            'content_type' => 'document',
        ]);

        $binary = (new SeederLessonMedia)->pdf(
            'Glosarium Pengenalan Agen AI',
            ['OpenClaw: runtime agen. Di academy ini ia adalah subjek Course terbatas.'],
        );
        $file = UploadedFile::fake()->createWithContent('glosarium.pdf', $binary);

        $response = $this->actingAs($this->lmsAdmin)->postJson('/media', [
            'file' => $file,
            'mediable_type' => 'lesson',
            'mediable_id' => $lesson->id,
            'collection_name' => 'document',
        ]);

        $response->assertCreated();

        $media = Media::query()->find($response->json('media.id'));
        $this->assertNotNull($media);
        $this->assertStringContainsString('OpenClaw: runtime agen', $media->storedBodyText());
        $this->assertSame('ready', $media->custom_properties['body_capture'] ?? null);
    }

    public function test_upload_thumbnail_with_correct_mime_type(): void
    {
        $course = Course::factory()->create([
            'user_id' => $this->contentManager->id,
            'status' => 'draft',
        ]);

        $file = UploadedFile::fake()->image('thumbnail.jpg');

        $response = $this->actingAs($this->contentManager)->postJson('/media', [
            'file' => $file,
            'mediable_type' => 'course',
            'mediable_id' => $course->id,
            'collection_name' => 'thumbnail',
        ]);

        $response->assertStatus(201);
    }

    // ========== Delete Media Tests ==========

    public function test_delete_media_requires_course_update_permission(): void
    {
        $course = Course::factory()->create([
            'user_id' => $this->contentManager->id,
            'status' => 'draft',
        ]);
        $media = Media::factory()->thumbnail()->create([
            'mediable_type' => Course::class,
            'mediable_id' => $course->id,
        ]);

        Storage::disk('public')->put($media->path, 'fake content');

        $response = $this->actingAs($this->contentManager)->deleteJson("/media/{$media->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($media->path);
    }

    public function test_admin_can_delete_media(): void
    {
        $course = Course::factory()->create(['user_id' => $this->contentManager->id]);
        $media = Media::factory()->thumbnail()->create([
            'mediable_type' => Course::class,
            'mediable_id' => $course->id,
        ]);

        Storage::disk('public')->put($media->path, 'fake content');

        $response = $this->actingAs($this->lmsAdmin)->deleteJson("/media/{$media->id}");

        $response->assertOk();
        $response->assertJson([
            'message' => 'File berhasil dihapus.',
        ]);
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    public function test_learner_cannot_delete_media(): void
    {
        $course = Course::factory()->create(['user_id' => $this->contentManager->id]);
        $media = Media::factory()->thumbnail()->create([
            'mediable_type' => Course::class,
            'mediable_id' => $course->id,
        ]);

        $response = $this->actingAs($this->learner)->deleteJson("/media/{$media->id}");

        $response->assertForbidden();
    }

    // ========== File Storage Tests ==========

    public function test_media_file_is_stored_in_correct_path_for_course(): void
    {
        $course = Course::factory()->create([
            'user_id' => $this->contentManager->id,
            'status' => 'draft',
        ]);

        $file = UploadedFile::fake()->image('thumbnail.jpg');

        $response = $this->actingAs($this->contentManager)->postJson('/media', [
            'file' => $file,
            'mediable_type' => 'course',
            'mediable_id' => $course->id,
            'collection_name' => 'thumbnail',
        ]);

        $response->assertStatus(201);

        $media = Media::where('mediable_id', $course->id)->first();
        $this->assertStringContainsString("courses/{$course->id}/thumbnail", $media->path);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_media_file_is_stored_in_correct_path_for_lesson(): void
    {
        $course = Course::factory()->create([
            'user_id' => $this->contentManager->id,
            'status' => 'draft',
        ]);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['course_section_id' => $section->id]);

        $file = UploadedFile::fake()->create('video.mp4', 1024, 'video/mp4');

        $response = $this->actingAs($this->contentManager)->postJson('/media', [
            'file' => $file,
            'mediable_type' => 'lesson',
            'mediable_id' => $lesson->id,
            'collection_name' => 'video',
        ]);

        $response->assertStatus(201);

        $media = Media::where('mediable_id', $lesson->id)->first();
        $this->assertStringContainsString("lessons/{$lesson->id}/video", $media->path);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_media_attributes_are_correctly_set(): void
    {
        $course = Course::factory()->create([
            'user_id' => $this->contentManager->id,
            'status' => 'draft',
        ]);

        $file = UploadedFile::fake()->create('my-document.pdf', 2048, 'application/pdf');

        $response = $this->actingAs($this->contentManager)->postJson('/media', [
            'file' => $file,
            'mediable_type' => 'course',
            'mediable_id' => $course->id,
            'collection_name' => 'document',
        ]);

        $response->assertStatus(201);

        $media = Media::where('mediable_id', $course->id)->first();

        $this->assertEquals('my-document', $media->name);
        $this->assertEquals('my-document.pdf', $media->file_name);
        $this->assertEquals('application/pdf', $media->mime_type);
        $this->assertEquals('public', $media->disk);
        $this->assertEquals('document', $media->collection_name);
        $this->assertTrue($media->is_document);
        $this->assertFalse($media->is_video);
        $this->assertFalse($media->is_audio);
        $this->assertFalse($media->is_image);
    }

    // ========== Guest Access Tests ==========

    public function test_guest_cannot_upload_media(): void
    {
        $course = Course::factory()->create();

        $response = $this->postJson('/media', [
            'file' => UploadedFile::fake()->image('photo.jpg'),
            'mediable_type' => 'course',
            'mediable_id' => $course->id,
        ]);

        $response->assertStatus(401);
    }

    public function test_guest_cannot_delete_media(): void
    {
        $course = Course::factory()->create();
        $media = Media::factory()->thumbnail()->create([
            'mediable_type' => Course::class,
            'mediable_id' => $course->id,
        ]);

        $response = $this->deleteJson("/media/{$media->id}");

        $response->assertStatus(401);
    }
}
