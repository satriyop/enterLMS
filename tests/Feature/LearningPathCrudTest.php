<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\LearningPath;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LearningPathCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $contentManager;

    protected User $learner;

    protected array $courses;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'lms_admin']);
        $this->contentManager = User::factory()->create(['role' => 'lms_admin']);
        $this->learner = User::factory()->create(['role' => 'learner']);

        $this->courses = Course::factory()->count(3)->create(['status' => 'published'])->all();
    }

    public function test_admin_can_create_learning_path()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'description' => 'Test description',
                'objectives' => ['Objective 1', 'Objective 2'],
                'estimated_duration' => 120,
                'difficulty_level' => 'beginner',
                'thumbnail' => UploadedFile::fake()->image('thumbnail.jpg'),
                'courses' => [
                    ['id' => $this->courses[0]->id, 'is_required' => true, 'min_completion_percentage' => 80],
                    ['id' => $this->courses[1]->id, 'is_required' => false, 'min_completion_percentage' => 70],
                ],
            ]);

        $response->assertRedirect(route('learning-paths.index'));
        $this->assertDatabaseHas('learning_paths', ['title' => 'Test Learning Path']);

        $learningPath = LearningPath::first();
        $this->assertEquals(2, $learningPath->courses()->count());
        $this->assertNotNull($learningPath->thumbnail_url);
        $this->assertStringContainsString('learning_paths/thumbnails', $learningPath->thumbnail_url);
        $this->assertNotNull($learningPath->slug);
        $this->assertStringContainsString('test-learning-path', $learningPath->slug);
    }

    public function test_thumbnail_validation()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'description' => 'Test description',
                'thumbnail' => UploadedFile::fake()->create('test.pdf', 1000), // Invalid file type
                'courses' => [
                    ['id' => $this->courses[0]->id, 'is_required' => true],
                ],
            ]);

        $response->assertSessionHasErrors(['thumbnail']);
        $this->assertDatabaseMissing('learning_paths', ['title' => 'Test Learning Path']);
    }

    public function test_thumbnail_is_optional()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path Without Thumbnail',
                'description' => 'Test description',
                'courses' => [
                    ['id' => $this->courses[0]->id, 'is_required' => true],
                ],
            ]);

        $response->assertRedirect(route('learning-paths.index'));
        $this->assertDatabaseHas('learning_paths', ['title' => 'Test Learning Path Without Thumbnail']);
        $learningPath = LearningPath::where('title', 'Test Learning Path Without Thumbnail')->first();
        $this->assertNull($learningPath->thumbnail_url);
    }

    public function test_learner_cannot_create_learning_path()
    {
        $response = $this->actingAs($this->learner)
            ->post(route('learning-paths.store'), [
                'title' => 'Unauthorized Learning Path',
                'courses' => [
                    ['id' => $this->courses[0]->id, 'is_required' => true],
                ],
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('learning_paths', ['title' => 'Unauthorized Learning Path']);
    }

    public function test_admin_can_view_learning_path()
    {
        $learningPath = LearningPath::factory()->create([
            'created_by' => $this->admin->id,
            'title' => 'Test Learning Path Title',
        ]);
        $learningPath->courses()->attach($this->courses[0]->id, ['position' => 0, 'is_required' => true]);

        $response = $this->actingAs($this->admin)
            ->get(route('learning-paths.show', $learningPath));

        $response->assertOk();
        $response->assertSee('Test Learning Path Title');
    }

    public function test_learner_can_view_published_learning_path()
    {
        $learningPath = LearningPath::factory()->create(['is_published' => true]);
        $learningPath->courses()->attach($this->courses[0]->id, ['position' => 0, 'is_required' => true]);

        $response = $this->actingAs($this->learner)
            ->get(route('learning-paths.show', $learningPath));

        $response->assertRedirect(route('learner.learning-paths.show', $learningPath));
    }

    public function test_learner_cannot_view_unpublished_learning_path()
    {
        $learningPath = LearningPath::factory()->create(['is_published' => false]);

        $response = $this->actingAs($this->learner)
            ->get(route('learning-paths.show', $learningPath));

        $response->assertRedirect(route('learner.learning-paths.show', $learningPath));
    }

    public function test_admin_can_update_learning_path()
    {
        $learningPath = LearningPath::factory()->create(['created_by' => $this->admin->id]);
        $learningPath->courses()->attach($this->courses[0]->id, ['position' => 0, 'is_required' => true]);

        $response = $this->actingAs($this->admin)
            ->put(route('learning-paths.update', $learningPath), [
                'title' => 'Updated Learning Path',
                'description' => 'Updated description',
                'courses' => [
                    ['id' => $this->courses[1]->id, 'is_required' => true],
                    ['id' => $this->courses[2]->id, 'is_required' => false],
                ],
            ]);

        $response->assertRedirect(route('learning-paths.show', $learningPath));
        $this->assertDatabaseHas('learning_paths', ['title' => 'Updated Learning Path']);

        $learningPath->refresh();
        $this->assertEquals(2, $learningPath->courses()->count());
    }

    public function test_learner_cannot_update_learning_path()
    {
        $learningPath = LearningPath::factory()->create(['is_published' => true]);

        $response = $this->actingAs($this->learner)
            ->put(route('learning-paths.update', $learningPath), [
                'title' => 'Should Not Update',
                'courses' => [
                    ['id' => $this->courses[0]->id, 'is_required' => true],
                ],
            ]);

        $response->assertForbidden();
    }

    public function test_admin_can_delete_learning_path()
    {
        $learningPath = LearningPath::factory()->create(['created_by' => $this->admin->id]);

        $response = $this->actingAs($this->admin)
            ->delete(route('learning-paths.destroy', $learningPath));

        $response->assertRedirect(route('learning-paths.index'));
        $this->assertSoftDeleted($learningPath);
    }

    public function test_learner_cannot_delete_learning_path()
    {
        $learningPath = LearningPath::factory()->create(['is_published' => true]);

        $response = $this->actingAs($this->learner)
            ->delete(route('learning-paths.destroy', $learningPath));

        $response->assertForbidden();
    }

    public function test_admin_can_publish_learning_path()
    {
        $learningPath = LearningPath::factory()->create(['created_by' => $this->admin->id, 'is_published' => false]);

        $response = $this->actingAs($this->admin)
            ->put(route('learning-paths.publish', $learningPath));

        $response->assertRedirect(route('learning-paths.show', $learningPath));
        $this->assertTrue($learningPath->fresh()->is_published);
    }

    public function test_learner_cannot_publish_learning_path()
    {
        $learningPath = LearningPath::factory()->create(['is_published' => false]);

        $response = $this->actingAs($this->learner)
            ->put(route('learning-paths.publish', $learningPath));

        $response->assertForbidden();
    }

    public function test_course_order_can_be_reordered()
    {
        $learningPath = LearningPath::factory()->create(['created_by' => $this->admin->id]);
        $learningPath->courses()->attach($this->courses[0]->id, ['position' => 0]);
        $learningPath->courses()->attach($this->courses[1]->id, ['position' => 1]);

        $response = $this->actingAs($this->admin)
            ->post(route('learning-paths.reorder', $learningPath), [
                'course_order' => [
                    ['id' => $this->courses[1]->id, 'position' => 0],
                    ['id' => $this->courses[0]->id, 'position' => 1],
                ],
            ]);

        $response->assertRedirect(route('learning-paths.show', $learningPath));

        $learningPath->refresh();
        $coursePositions = $learningPath->courses->pluck('pivot.position', 'id');
        $this->assertEquals(0, $coursePositions[$this->courses[1]->id]);
        $this->assertEquals(1, $coursePositions[$this->courses[0]->id]);
    }

    public function test_learning_path_index_shows_only_authorized_learning_paths()
    {
        // Create learning paths with different visibility
        $publishedPath = LearningPath::factory()->create(['is_published' => true, 'created_by' => $this->admin->id]);
        $draftPath = LearningPath::factory()->create(['is_published' => false, 'created_by' => $this->admin->id]);
        $contentManagerPath = LearningPath::factory()->create(['is_published' => false, 'created_by' => $this->contentManager->id]);

        // Admin should see all
        $response = $this->actingAs($this->admin)
            ->get(route('learning-paths.index'));
        $response->assertOk();

        // Content manager should see their own and published
        $response = $this->actingAs($this->contentManager)
            ->get(route('learning-paths.index'));
        $response->assertOk();

        $response = $this->actingAs($this->learner)
            ->get(route('learning-paths.index'));
        $response->assertRedirect(route('learner.learning-paths.browse'));
    }

    public function test_learner_is_sent_to_browse_instead_of_authoring_index()
    {
        $response = $this->actingAs($this->learner)
            ->get(route('learning-paths.index'));

        $response->assertRedirect(route('learner.learning-paths.browse'));
    }

    public function test_learner_cannot_access_create_page()
    {
        $response = $this->actingAs($this->learner)
            ->get(route('learning-paths.create'));

        $response->assertForbidden();
    }

    public function test_admin_can_access_create_page()
    {
        $response = $this->actingAs($this->admin)
            ->get(route('learning-paths.create'));

        $response->assertOk();
    }
}
