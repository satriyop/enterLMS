<?php

use App\Models\Course;
use App\Models\LearningPath;

describe('Learning Path Store Validation', function () {

    it('requires title', function () {
        asAdmin()
            ->post(route('learning-paths.store'), [
                'courses' => [['id' => Course::factory()->published()->create()->id]],
            ])
            ->assertSessionHasErrors('title');
    });

    it('validates title max length', function (string $title, bool $shouldFail) {
        $course = Course::factory()->published()->create();

        $response = asAdmin()
            ->post(route('learning-paths.store'), [
                'title' => $title,
                'courses' => [['id' => $course->id]],
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

    it('validates description is string when provided', function () {
        $course = Course::factory()->published()->create();

        asAdmin()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'description' => ['invalid' => 'array'],
                'courses' => [['id' => $course->id]],
            ])
            ->assertSessionHasErrors('description');
    });

    it('accepts nullable description', function () {
        $course = Course::factory()->published()->create();

        asAdmin()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'description' => null,
                'courses' => [['id' => $course->id]],
            ])
            ->assertSessionDoesntHaveErrors('description');
    });

    it('validates objectives is array when provided', function () {
        $course = Course::factory()->published()->create();

        asAdmin()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'objectives' => 'not-an-array',
                'courses' => [['id' => $course->id]],
            ])
            ->assertSessionHasErrors('objectives');
    });

    it('validates objectives items are strings', function () {
        $course = Course::factory()->published()->create();

        asAdmin()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'objectives' => ['Valid objective', 123, ['nested' => 'array']],
                'courses' => [['id' => $course->id]],
            ])
            ->assertSessionHasErrors(['objectives.1', 'objectives.2']);
    });

    it('accepts valid objectives array', function () {
        $course = Course::factory()->published()->create();

        asAdmin()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'objectives' => ['Learn fundamentals', 'Master advanced concepts', 'Apply in practice'],
                'courses' => [['id' => $course->id]],
            ])
            ->assertSessionDoesntHaveErrors('objectives');
    });

    it('validates estimated_duration is integer when provided', function () {
        $course = Course::factory()->published()->create();

        asAdmin()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'estimated_duration' => 'not-a-number',
                'courses' => [['id' => $course->id]],
            ])
            ->assertSessionHasErrors('estimated_duration');
    });

    it('validates estimated_duration minimum value', function () {
        $course = Course::factory()->published()->create();

        asAdmin()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'estimated_duration' => 0,
                'courses' => [['id' => $course->id]],
            ])
            ->assertSessionHasErrors('estimated_duration');
    });

    it('accepts valid estimated_duration', function () {
        $course = Course::factory()->published()->create();

        asAdmin()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'estimated_duration' => 120,
                'courses' => [['id' => $course->id]],
            ])
            ->assertSessionDoesntHaveErrors('estimated_duration');
    });

    it('validates difficulty_level enum', function (string $level, bool $shouldFail) {
        $course = Course::factory()->published()->create();

        $response = asAdmin()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'difficulty_level' => $level,
                'courses' => [['id' => $course->id]],
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('difficulty_level');
        } else {
            $response->assertSessionDoesntHaveErrors('difficulty_level');
        }
    })->with([
        'beginner' => ['beginner', false],
        'intermediate' => ['intermediate', false],
        'advanced' => ['advanced', false],
        'expert' => ['expert', false],
        'invalid value' => ['master', true],
    ]);

    it('validates thumbnail is image', function () {
        $course = Course::factory()->published()->create();

        asAdmin()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'thumbnail' => 'not-a-file',
                'courses' => [['id' => $course->id]],
            ])
            ->assertSessionHasErrors('thumbnail');
    });

    it('requires courses array', function () {
        asAdmin()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
            ])
            ->assertSessionHasErrors('courses');
    });

    it('validates courses minimum count', function () {
        asAdmin()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'courses' => [],
            ])
            ->assertSessionHasErrors('courses');
    });

    it('requires course id in courses array', function () {
        asAdmin()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'courses' => [['is_required' => true]],
            ])
            ->assertSessionHasErrors('courses.0.id');
    });

    it('validates course exists', function () {
        asAdmin()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'courses' => [['id' => 99999]],
            ])
            ->assertSessionHasErrors('courses.0.id');
    });

    it('accepts valid course id', function () {
        $course = Course::factory()->published()->create();

        asAdmin()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'courses' => [['id' => $course->id]],
            ])
            ->assertSessionDoesntHaveErrors('courses.0.id');
    });

    it('validates is_required is boolean', function () {
        $course = Course::factory()->published()->create();

        asAdmin()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'courses' => [
                    [
                        'id' => $course->id,
                        'is_required' => 'not-a-boolean',
                    ],
                ],
            ])
            ->assertSessionHasErrors('courses.0.is_required');
    });

    it('validates prerequisites is array when provided', function () {
        $course = Course::factory()->published()->create();

        asAdmin()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'courses' => [
                    [
                        'id' => $course->id,
                        'prerequisites' => 'not-an-array',
                    ],
                ],
            ])
            ->assertSessionHasErrors('courses.0.prerequisites');
    });

    it('validates prerequisite course ids are integers', function () {
        $course = Course::factory()->published()->create();

        asAdmin()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'courses' => [
                    [
                        'id' => $course->id,
                        'prerequisites' => [
                            'completed_courses' => ['not-an-integer'],
                        ],
                    ],
                ],
            ])
            ->assertSessionHasErrors('courses.0.prerequisites.completed_courses.0');
    });

    it('validates min_completion_percentage is integer when provided', function () {
        $course = Course::factory()->published()->create();

        asAdmin()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'courses' => [
                    [
                        'id' => $course->id,
                        'min_completion_percentage' => 'not-a-number',
                    ],
                ],
            ])
            ->assertSessionHasErrors('courses.0.min_completion_percentage');
    });

    it('validates min_completion_percentage minimum value', function () {
        $course = Course::factory()->published()->create();

        asAdmin()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'courses' => [
                    [
                        'id' => $course->id,
                        'min_completion_percentage' => 0,
                    ],
                ],
            ])
            ->assertSessionHasErrors('courses.0.min_completion_percentage');
    });

    it('validates min_completion_percentage maximum value', function () {
        $course = Course::factory()->published()->create();

        asAdmin()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'courses' => [
                    [
                        'id' => $course->id,
                        'min_completion_percentage' => 101,
                    ],
                ],
            ])
            ->assertSessionHasErrors('courses.0.min_completion_percentage');
    });

    it('accepts valid min_completion_percentage', function (int $percentage) {
        $course = Course::factory()->published()->create();

        asAdmin()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'courses' => [
                    [
                        'id' => $course->id,
                        'min_completion_percentage' => $percentage,
                    ],
                ],
            ])
            ->assertSessionDoesntHaveErrors('courses.0.min_completion_percentage');
    })->with([1, 50, 100]);

    it('validates prerequisite courses must come before current course', function () {
        $course1 = Course::factory()->published()->create();
        $course2 = Course::factory()->published()->create();

        asAdmin()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'courses' => [
                    [
                        'id' => $course1->id,
                        'prerequisites' => [
                            'completed_courses' => [$course2->id], // Course 2 is prerequisite but comes after
                        ],
                    ],
                    [
                        'id' => $course2->id,
                    ],
                ],
            ])
            ->assertSessionHasErrors('courses.0.prerequisites');
    });

    it('accepts prerequisite courses that come before current course', function () {
        $course1 = Course::factory()->published()->create();
        $course2 = Course::factory()->published()->create();

        asAdmin()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'courses' => [
                    [
                        'id' => $course1->id,
                    ],
                    [
                        'id' => $course2->id,
                        'prerequisites' => [
                            'completed_courses' => [$course1->id], // Course 1 comes before
                        ],
                    ],
                ],
            ])
            ->assertSessionDoesntHaveErrors('courses.1.prerequisites.completed_courses');
    });

    it('only allows lms_admin to create learning paths', function () {
        $course = Course::factory()->published()->create();

        asAdmin()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'courses' => [['id' => $course->id]],
            ])
            ->assertSessionDoesntHaveErrors();
    });

    it('only allows content_manager to create learning paths', function () {
        $course = Course::factory()->published()->create();

        asContentManager()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'courses' => [['id' => $course->id]],
            ])
            ->assertSessionDoesntHaveErrors();
    });

    it('forbids learners from creating learning paths', function () {
        $course = Course::factory()->published()->create();

        asLearner()
            ->post(route('learning-paths.store'), [
                'title' => 'Test Learning Path',
                'courses' => [['id' => $course->id]],
            ])
            ->assertForbidden();
    });

});

describe('Learning Path Update Validation', function () {

    it('requires title on update', function () {
        $learningPath = LearningPath::factory()->create();
        $course = Course::factory()->published()->create();

        asAdmin()
            ->put(route('learning-paths.update', $learningPath), [
                'title' => '',
                'courses' => [['id' => $course->id]],
            ])
            ->assertSessionHasErrors('title');
    });

    it('validates title max length on update', function (string $title, bool $shouldFail) {
        $learningPath = LearningPath::factory()->create();
        $course = Course::factory()->published()->create();

        $response = asAdmin()
            ->put(route('learning-paths.update', $learningPath), [
                'title' => $title,
                'courses' => [['id' => $course->id]],
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

    it('requires courses array on update', function () {
        $learningPath = LearningPath::factory()->create();

        asAdmin()
            ->put(route('learning-paths.update', $learningPath), [
                'title' => 'Updated Learning Path',
            ])
            ->assertSessionHasErrors('courses');
    });

    it('validates courses minimum count on update', function () {
        $learningPath = LearningPath::factory()->create();

        asAdmin()
            ->put(route('learning-paths.update', $learningPath), [
                'title' => 'Updated Learning Path',
                'courses' => [],
            ])
            ->assertSessionHasErrors('courses');
    });

    it('validates difficulty_level enum on update', function (string $level, bool $shouldFail) {
        $learningPath = LearningPath::factory()->create();
        $course = Course::factory()->published()->create();

        $response = asAdmin()
            ->put(route('learning-paths.update', $learningPath), [
                'title' => 'Updated Learning Path',
                'difficulty_level' => $level,
                'courses' => [['id' => $course->id]],
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('difficulty_level');
        } else {
            $response->assertSessionDoesntHaveErrors('difficulty_level');
        }
    })->with([
        'beginner' => ['beginner', false],
        'intermediate' => ['intermediate', false],
        'advanced' => ['advanced', false],
        'expert' => ['expert', false],
        'invalid value' => ['master', true],
    ]);

    it('validates estimated_duration minimum value on update', function () {
        $learningPath = LearningPath::factory()->create();
        $course = Course::factory()->published()->create();

        asAdmin()
            ->put(route('learning-paths.update', $learningPath), [
                'title' => 'Updated Learning Path',
                'estimated_duration' => 0,
                'courses' => [['id' => $course->id]],
            ])
            ->assertSessionHasErrors('estimated_duration');
    });

    it('validates course exists on update', function () {
        $learningPath = LearningPath::factory()->create();

        asAdmin()
            ->put(route('learning-paths.update', $learningPath), [
                'title' => 'Updated Learning Path',
                'courses' => [['id' => 99999]],
            ])
            ->assertSessionHasErrors('courses.0.id');
    });

    it('validates min_completion_percentage range on update', function (int $percentage, bool $shouldFail) {
        $learningPath = LearningPath::factory()->create();
        $course = Course::factory()->published()->create();

        $response = asAdmin()
            ->put(route('learning-paths.update', $learningPath), [
                'title' => 'Updated Learning Path',
                'courses' => [
                    [
                        'id' => $course->id,
                        'min_completion_percentage' => $percentage,
                    ],
                ],
            ]);

        if ($shouldFail) {
            $response->assertSessionHasErrors('courses.0.min_completion_percentage');
        } else {
            $response->assertSessionDoesntHaveErrors('courses.0.min_completion_percentage');
        }
    })->with([
        'below minimum (0)' => [0, true],
        'at minimum (1)' => [1, false],
        'mid range (50)' => [50, false],
        'at maximum (100)' => [100, false],
        'above maximum (101)' => [101, true],
    ]);

    it('validates prerequisite courses must come before current course on update', function () {
        $learningPath = LearningPath::factory()->create();
        $course1 = Course::factory()->published()->create();
        $course2 = Course::factory()->published()->create();

        asAdmin()
            ->put(route('learning-paths.update', $learningPath), [
                'title' => 'Updated Learning Path',
                'courses' => [
                    [
                        'id' => $course1->id,
                        'prerequisites' => [
                            'completed_courses' => [$course2->id], // Course 2 comes after
                        ],
                    ],
                    [
                        'id' => $course2->id,
                    ],
                ],
            ])
            ->assertSessionHasErrors('courses.0.prerequisites');
    });

    it('accepts valid update with all fields', function () {
        $learningPath = LearningPath::factory()->create();
        $course1 = Course::factory()->published()->create();
        $course2 = Course::factory()->published()->create();

        asAdmin()
            ->put(route('learning-paths.update', $learningPath), [
                'title' => 'Updated Learning Path',
                'description' => 'Updated description',
                'objectives' => ['Updated objective 1', 'Updated objective 2'],
                'estimated_duration' => 240,
                'difficulty_level' => 'advanced',
                'courses' => [
                    [
                        'id' => $course1->id,
                        'is_required' => true,
                    ],
                    [
                        'id' => $course2->id,
                        'is_required' => false,
                        'prerequisites' => [
                            'completed_courses' => [$course1->id],
                        ],
                        'min_completion_percentage' => 80,
                    ],
                ],
            ])
            ->assertSessionDoesntHaveErrors();
    });

});
