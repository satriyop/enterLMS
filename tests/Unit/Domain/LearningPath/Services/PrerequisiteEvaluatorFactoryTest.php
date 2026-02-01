<?php

/**
 * PrerequisiteEvaluatorFactory Unit Test
 *
 * Tests the factory that creates prerequisite evaluator instances based on
 * learning path configuration. Focuses on factory methods, registration, and
 * error handling.
 */

use App\Domain\LearningPath\Contracts\PrerequisiteEvaluatorContract;
use App\Domain\LearningPath\DTOs\PrerequisiteCheckResult;
use App\Domain\LearningPath\Services\PrerequisiteEvaluatorFactory;
use App\Domain\LearningPath\Strategies\ImmediatePreviousPrerequisiteEvaluator;
use App\Domain\LearningPath\Strategies\NoPrerequisiteEvaluator;
use App\Domain\LearningPath\Strategies\PricingAwarePrerequisiteEvaluator;
use App\Domain\LearningPath\Strategies\SequentialPrerequisiteEvaluator;
use App\Models\Course;
use App\Models\LearningPath;
use App\Models\LearningPathEnrollment;

describe('resolve() - Returns Correct Evaluator Instance', function () {
    it('returns SequentialPrerequisiteEvaluator for sequential type', function () {
        $factory = app(PrerequisiteEvaluatorFactory::class);

        $evaluator = $factory->resolve('sequential');

        expect($evaluator)->toBeInstanceOf(SequentialPrerequisiteEvaluator::class);
        expect($evaluator)->toBeInstanceOf(PrerequisiteEvaluatorContract::class);
    });

    it('returns ImmediatePreviousPrerequisiteEvaluator for immediate_previous type', function () {
        $factory = app(PrerequisiteEvaluatorFactory::class);

        $evaluator = $factory->resolve('immediate_previous');

        expect($evaluator)->toBeInstanceOf(ImmediatePreviousPrerequisiteEvaluator::class);
        expect($evaluator)->toBeInstanceOf(PrerequisiteEvaluatorContract::class);
    });

    it('returns NoPrerequisiteEvaluator for none type', function () {
        $factory = app(PrerequisiteEvaluatorFactory::class);

        $evaluator = $factory->resolve('none');

        expect($evaluator)->toBeInstanceOf(NoPrerequisiteEvaluator::class);
        expect($evaluator)->toBeInstanceOf(PrerequisiteEvaluatorContract::class);
    });

    it('returns PricingAwarePrerequisiteEvaluator for pricing_aware type', function () {
        $factory = app(PrerequisiteEvaluatorFactory::class);

        $evaluator = $factory->resolve('pricing_aware');

        expect($evaluator)->toBeInstanceOf(PricingAwarePrerequisiteEvaluator::class);
        expect($evaluator)->toBeInstanceOf(PrerequisiteEvaluatorContract::class);
    });

    it('throws InvalidArgumentException for unknown type', function () {
        $factory = app(PrerequisiteEvaluatorFactory::class);

        expect(fn () => $factory->resolve('invalid_mode'))
            ->toThrow(InvalidArgumentException::class, 'Unknown prerequisite evaluator type: invalid_mode');
    });

    it('throws InvalidArgumentException for empty string type', function () {
        $factory = app(PrerequisiteEvaluatorFactory::class);

        expect(fn () => $factory->resolve(''))
            ->toThrow(InvalidArgumentException::class);
    });

    it('throws InvalidArgumentException for null type', function () {
        $factory = app(PrerequisiteEvaluatorFactory::class);

        expect(fn () => $factory->resolve(null))
            ->toThrow(TypeError::class);
    });
});

describe('make() - Uses LearningPath Configuration', function () {
    it('uses learning path prerequisite_mode to resolve evaluator', function () {
        $factory = app(PrerequisiteEvaluatorFactory::class);

        $sequentialPath = LearningPath::factory()->create(['prerequisite_mode' => 'sequential']);
        $immediatePath = LearningPath::factory()->create(['prerequisite_mode' => 'immediate_previous']);
        $nonePath = LearningPath::factory()->create(['prerequisite_mode' => 'none']);
        $pricingPath = LearningPath::factory()->create(['prerequisite_mode' => 'pricing_aware']);

        expect($factory->make($sequentialPath))->toBeInstanceOf(SequentialPrerequisiteEvaluator::class);
        expect($factory->make($immediatePath))->toBeInstanceOf(ImmediatePreviousPrerequisiteEvaluator::class);
        expect($factory->make($nonePath))->toBeInstanceOf(NoPrerequisiteEvaluator::class);
        expect($factory->make($pricingPath))->toBeInstanceOf(PricingAwarePrerequisiteEvaluator::class);
    });

    it('defaults to sequential when prerequisite_mode is null', function () {
        $factory = app(PrerequisiteEvaluatorFactory::class);

        // Create path and set prerequisite_mode to null in memory
        $path = LearningPath::factory()->create(['prerequisite_mode' => 'sequential']);
        $path->prerequisite_mode = null;

        $evaluator = $factory->make($path);

        expect($evaluator)->toBeInstanceOf(SequentialPrerequisiteEvaluator::class);
    });

    it('uses config default when prerequisite_mode is null and config is set', function () {
        $factory = app(PrerequisiteEvaluatorFactory::class);

        // Temporarily set config
        config(['lms.learning_path.default_prerequisite_mode' => 'none']);

        $path = LearningPath::factory()->create(['prerequisite_mode' => 'sequential']);
        $path->prerequisite_mode = null;

        $evaluator = $factory->make($path);

        expect($evaluator)->toBeInstanceOf(NoPrerequisiteEvaluator::class);

        // Reset config
        config(['lms.learning_path.default_prerequisite_mode' => 'sequential']);
    });
});

describe('getAvailableTypes() - Returns Available Evaluator Types', function () {
    it('returns all four built-in evaluator types', function () {
        $factory = app(PrerequisiteEvaluatorFactory::class);

        $types = $factory->getAvailableTypes();

        expect($types)->toBeArray();
        expect($types)->toHaveCount(4);
        expect($types)->toHaveKeys(['sequential', 'immediate_previous', 'none', 'pricing_aware']);
    });

    it('returns Indonesian labels for each type', function () {
        $factory = app(PrerequisiteEvaluatorFactory::class);

        $types = $factory->getAvailableTypes();

        expect($types['sequential'])->toBe('Berurutan (Semua sebelumnya harus selesai)');
        expect($types['immediate_previous'])->toBe('Hanya kursus sebelumnya');
        expect($types['none'])->toBe('Tanpa prasyarat');
        expect($types['pricing_aware'])->toBe('Dengan pertimbangan harga (Mode komersial)');
    });

    it('labels are not empty strings', function () {
        $factory = app(PrerequisiteEvaluatorFactory::class);

        $types = $factory->getAvailableTypes();

        foreach ($types as $label) {
            expect($label)->not->toBeEmpty();
        }
    });
});

describe('register() - Adds Custom Evaluator', function () {
    it('registers custom evaluator and makes it resolvable', function () {
        $factory = app(PrerequisiteEvaluatorFactory::class);

        // Create a test double using anonymous class
        $customEvaluatorClass = new class implements PrerequisiteEvaluatorContract
        {
            public function evaluate(
                LearningPathEnrollment $enrollment,
                Course $course
            ): PrerequisiteCheckResult {
                return PrerequisiteCheckResult::met();
            }

            public function getName(): string
            {
                return 'custom';
            }
        };

        $factory->register('custom', get_class($customEvaluatorClass));

        $evaluator = $factory->resolve('custom');

        expect($evaluator)->toBeInstanceOf(PrerequisiteEvaluatorContract::class);
        expect($evaluator->getName())->toBe('custom');
    });

    it('allows overriding existing evaluator type', function () {
        $factory = app(PrerequisiteEvaluatorFactory::class);

        // Create a custom evaluator to override 'sequential'
        $customEvaluatorClass = new class implements PrerequisiteEvaluatorContract
        {
            public function evaluate(
                LearningPathEnrollment $enrollment,
                Course $course
            ): PrerequisiteCheckResult {
                return PrerequisiteCheckResult::met();
            }

            public function getName(): string
            {
                return 'custom_sequential';
            }
        };

        $factory->register('sequential', get_class($customEvaluatorClass));

        $evaluator = $factory->resolve('sequential');

        expect($evaluator->getName())->toBe('custom_sequential');
    });

    it('throws InvalidArgumentException when registering non-implementing class', function () {
        $factory = app(PrerequisiteEvaluatorFactory::class);

        // Create a class that does NOT implement PrerequisiteEvaluatorContract
        $invalidClass = new class
        {
            public function someMethod(): void
            {
                // Not implementing the contract
            }
        };

        expect(fn () => $factory->register('invalid', get_class($invalidClass)))
            ->toThrow(InvalidArgumentException::class, 'Evaluator class must implement PrerequisiteEvaluatorContract');
    });

    it('throws InvalidArgumentException for non-existent class', function () {
        $factory = app(PrerequisiteEvaluatorFactory::class);

        expect(fn () => $factory->register('invalid', 'NonExistentClass'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('registered evaluator is accessible via make() method', function () {
        $factory = app(PrerequisiteEvaluatorFactory::class);

        // Create a test double using anonymous class
        $customEvaluatorClass = new class implements PrerequisiteEvaluatorContract
        {
            public function evaluate(
                LearningPathEnrollment $enrollment,
                Course $course
            ): PrerequisiteCheckResult {
                return PrerequisiteCheckResult::met();
            }

            public function getName(): string
            {
                return 'custom_make';
            }
        };

        $factory->register('custom_make', get_class($customEvaluatorClass));

        $path = LearningPath::factory()->create(['prerequisite_mode' => 'sequential']);
        $path->prerequisite_mode = 'custom_make';

        $evaluator = $factory->make($path);

        expect($evaluator->getName())->toBe('custom_make');
    });
});

describe('Integration with Service Container', function () {
    it('resolves evaluators from service container', function () {
        $factory = app(PrerequisiteEvaluatorFactory::class);

        $evaluator1 = $factory->resolve('sequential');
        $evaluator2 = $factory->resolve('sequential');

        // Each resolve should create a new instance (not singleton)
        expect($evaluator1)->not->toBe($evaluator2);
        expect($evaluator1)->toBeInstanceOf(SequentialPrerequisiteEvaluator::class);
        expect($evaluator2)->toBeInstanceOf(SequentialPrerequisiteEvaluator::class);
    });

    it('factory itself is bound as singleton in container', function () {
        $factory1 = app(PrerequisiteEvaluatorFactory::class);
        $factory2 = app(PrerequisiteEvaluatorFactory::class);

        // Factory is registered as singleton in DomainServiceProvider
        expect($factory1)->toBe($factory2);
        expect($factory1)->toBeInstanceOf(PrerequisiteEvaluatorFactory::class);
    });
});
