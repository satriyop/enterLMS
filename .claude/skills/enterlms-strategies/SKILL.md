---
name: enterlms-strategies
description: Strategy pattern implementations for EnterLMS. Use when creating pluggable algorithms like grading strategies or prerequisite evaluators. Progress calculation is NOT a strategy (ADR 008).
triggers:
  - strategy pattern
  - create strategy
  - grading strategy
  - calculation strategy
  - strategy resolver
  - strategy factory
  - multiple choice grading
  - assessment grading
  - configurable strategy
---

# EnterLMS Strategy Patterns

> ### Read this before copying any progress-calculator example below
>
> **Progress calculation is no longer a strategy** (ADR 008). The contract, the
> factory, and the `LessonBased` and `Weighted` calculators were deleted;
> `AssessmentInclusiveProgressCalculator` is a plain class.
>
> The examples in this file were rewritten onto grading and prerequisite
> evaluation, which are live. Progress remains here only as the cautionary tale:
> its factory selected on a per-Course column that was never migrated, so it
> always returned the config default and two of its three strategies were
> unreachable for the life of the codebase.
>
> **A strategy needs a selector that exists** — a migrated column, or config
> something actually sets. Not just a `match` arm. Grading passes that test
> (a Question's type is real data); prerequisite evaluation passes it.
>
> For a factory that is genuinely in use, read `PrerequisiteEvaluatorFactory`.

## ⚠️ YAGNI Warning: When NOT to Use Strategy Pattern

**Strategy pattern adds complexity.** Before creating a new strategy system, ask:

| Question | If No → Don't Use Strategy |
|----------|----------------------------|
| Will there be 3+ interchangeable algorithms? | Simple if/switch is fine |
| Can the algorithm be selected at runtime? | Hardcode or config value |
| Does business require user/admin to choose? | Just implement one way |
| Are algorithms genuinely different? | Extract method, not pattern |

### ROUND10 Lesson

We evaluated existing strategies and kept them because:
- **Progress calculators** - 3 algorithms, config-driven selection, genuinely different
- **Grading strategies** - 4+ question types, runtime selection by `question_type`
- **Prerequisite evaluators** - User-configurable per learning path

We **would NOT** create strategy pattern for:
- Single implementation with "maybe future variations"
- Config that just enables/disables a feature
- Simple flag-based behavior (`if ($strict) {...}`)

### Red Flags (Over-Engineering)

```php
// ❌ YAGNI: Only one implementation, "future-proofing"
interface NotificationSenderContract { }
class EmailNotificationSender implements NotificationSenderContract { }
// No SMS, Push, etc. implementations → Just use a class!

// ❌ YAGNI: Strategy for boolean flag
interface AuditStrategyContract { }
class FullAuditStrategy implements AuditStrategyContract { }
class NoAuditStrategy implements AuditStrategyContract { }
// Just use: if ($shouldAudit) { audit(); }

// ✅ GOOD: Multiple real algorithms, selected by data the domain already has
interface GradingStrategyContract { }
class MultipleChoiceGradingStrategy implements GradingStrategyContract { }
class TrueFalseGradingStrategy implements GradingStrategyContract { }
class ShortAnswerGradingStrategy implements GradingStrategyContract { }
// A Question's type picks the strategy — the selector is real.
```

> **Progress calculation used to be the example here, and it was wrong.**
> Three calculators existed, but the thing meant to choose between them —
> a per-Course column — was never migrated, so only one ever ran. Before
> adding a strategy, check that the selector actually exists in the
> database or the config, not just in the resolver. See ADR 008.

---

## When to Use This Skill

- Implementing pluggable algorithms (grading, progress calculation)
- Creating configurable business logic
- Adding new strategies to existing systems
- Understanding the strategy resolver/factory pattern
- Working with tagged service container bindings

## Existing Strategy Systems

| System | Purpose | Strategies |
|--------|---------|------------|
| Grading | Grade assessment answers | MultipleChoice, TrueFalse, ShortAnswer, Manual |
| Prerequisites | Evaluate learning path unlock | Sequential, ImmediatePrevious, NoPrerequisite |

Progress calculation is **not** a strategy system. `AssessmentInclusiveProgressCalculator`
is a plain class — see ADR 008.

## Key Patterns

### 1. Strategy Contract (Interface)

```php
// app/Domain/Assessment/Contracts/GradingStrategyContract.php
namespace App\Domain\Assessment\Contracts;

use App\Domain\Assessment\DTOs\GradingResult;
use App\Models\Question;

interface GradingStrategyContract
{
    /**
     * Check if this strategy can grade the given question.
     */
    public function supports(Question $question): bool;

    /**
     * Grade the answer and return a result.
     */
    public function grade(Question $question, mixed $answer): GradingResult;

    /**
     * Get the question types this strategy handles.
     */
    public function getHandledTypes(): array;
}
```

### 2. Strategy Implementation

```php
// app/Domain/Assessment/Strategies/MultipleChoiceGradingStrategy.php
namespace App\Domain\Assessment\Strategies;

use App\Domain\Assessment\Contracts\GradingStrategyContract;
use App\Domain\Assessment\DTOs\GradingResult;
use App\Models\Question;

class MultipleChoiceGradingStrategy implements GradingStrategyContract
{
    public function supports(Question $question): bool
    {
        return in_array($question->question_type, $this->getHandledTypes());
    }

    public function getHandledTypes(): array
    {
        return ['multiple_choice', 'single_choice'];
    }

    public function grade(Question $question, mixed $answer): GradingResult
    {
        $selectedIds = is_array($answer) ? $answer : [$answer];
        $selectedIds = array_map('intval', $selectedIds);

        $correctOptionIds = $question->options()
            ->where('is_correct', true)
            ->pluck('id')
            ->toArray();

        sort($selectedIds);
        sort($correctOptionIds);

        $isCorrect = $selectedIds === $correctOptionIds;

        if ($isCorrect) {
            return GradingResult::correct(
                points: $question->points,
                feedback: 'Jawaban benar!'
            );
        }

        // Partial credit logic for multiple correct answers...
        return GradingResult::incorrect(
            maxPoints: $question->points,
            feedback: 'Jawaban salah.'
        );
    }
}
```

### 3. Strategy Resolver (Runtime Selection)

```php
// app/Domain/Assessment/Services/GradingStrategyResolver.php
namespace App\Domain\Assessment\Services;

use App\Domain\Assessment\Contracts\GradingStrategyContract;
use App\Domain\Assessment\Contracts\GradingStrategyResolverContract;
use App\Models\Question;
use Illuminate\Support\Collection;

class GradingStrategyResolver implements GradingStrategyResolverContract
{
    /** @var Collection<int, GradingStrategyContract> */
    protected Collection $strategies;

    public function __construct(iterable $strategies)
    {
        $this->strategies = collect($strategies);
    }

    public function resolve(Question $question): ?GradingStrategyContract
    {
        return $this->strategies->first(
            fn (GradingStrategyContract $strategy) => $strategy->supports($question)
        );
    }

    public function getAllStrategies(): Collection
    {
        return $this->strategies;
    }

    public function getSupportedTypes(): array
    {
        return $this->strategies
            ->flatMap(fn ($strategy) => $strategy->getHandledTypes())
            ->unique()
            ->values()
            ->toArray();
    }
}
```

### 4. Strategy Factory (Config-Driven)

```php
// app/Domain/LearningPath/Services/PrerequisiteEvaluatorFactory.php
namespace App\Domain\LearningPath\Services;

use App\Domain\LearningPath\Contracts\PrerequisiteEvaluatorContract;
use App\Domain\LearningPath\Strategies\ImmediatePreviousPrerequisiteEvaluator;
use App\Domain\LearningPath\Strategies\NoPrerequisiteEvaluator;
use App\Domain\LearningPath\Strategies\SequentialPrerequisiteEvaluator;
use App\Models\LearningPath;
use InvalidArgumentException;

class PrerequisiteEvaluatorFactory
{
    /** @var array<string, class-string<PrerequisiteEvaluatorContract>> */
    private array $evaluators = [
        'sequential' => SequentialPrerequisiteEvaluator::class,
        'immediate_previous' => ImmediatePreviousPrerequisiteEvaluator::class,
        'none' => NoPrerequisiteEvaluator::class,
    ];

    /**
     * The selector is `learning_paths.prerequisite_mode` — a real, migrated
     * column. That is what makes this a factory rather than dead ceremony.
     */
    public function make(LearningPath $path): PrerequisiteEvaluatorContract
    {
        $type = $path->prerequisite_mode
            ?? config('lms.learning_path.default_prerequisite_mode', 'sequential');

        return $this->resolve($type);
    }

    public function resolve(string $type): PrerequisiteEvaluatorContract
    {
        if (! isset($this->evaluators[$type])) {
            throw new InvalidArgumentException("Unknown prerequisite evaluator type: {$type}");
        }

        return app($this->evaluators[$type]);
    }
}
```

> **Unknown type throws.** The deleted `ProgressCalculatorFactory` used
> `match` with a `default =>` arm, so a typo silently produced a working
> calculator of the wrong kind. Prefer an explicit failure.

### 5. Contract + Implementation (Prerequisite Evaluator)

```php
// app/Domain/LearningPath/Contracts/PrerequisiteEvaluatorContract.php
namespace App\Domain\LearningPath\Contracts;

use App\Models\Course;
use App\Models\LearningPathEnrollment;

interface PrerequisiteEvaluatorContract
{
    /** Whether the learner may start this Course yet. */
    public function isUnlocked(LearningPathEnrollment $enrollment, Course $course): bool;

    /** Snake_case identifier matching the `prerequisite_mode` column. */
    public function getName(): string;
}
```

Each implementation answers the same question a different way — `sequential`
requires every earlier Course, `immediate_previous` only the one before, `none`
unlocks everything. A Path picks between them through `prerequisite_mode`.

> A `getName()` on a strategy only earns its place when something maps names to
> classes. The deleted progress calculators had one with nothing reading it.

### 6. Result DTOs with Factory Methods

```php
// app/Domain/Assessment/DTOs/GradingResult.php
namespace App\Domain\Assessment\DTOs;

final readonly class GradingResult
{
    public function __construct(
        public bool $isCorrect,
        public float $score,
        public float $maxScore,
        public ?string $feedback = null,
        public array $metadata = [],
    ) {}

    // Factory methods for common cases
    public static function correct(float $points, ?string $feedback = null): static
    {
        return new static(
            isCorrect: true,
            score: $points,
            maxScore: $points,
            feedback: $feedback,
        );
    }

    public static function incorrect(float $maxPoints, ?string $feedback = null): static
    {
        return new static(
            isCorrect: false,
            score: 0,
            maxScore: $maxPoints,
            feedback: $feedback,
        );
    }

    public static function partial(float $score, float $maxScore, ?string $feedback = null): static
    {
        return new static(
            isCorrect: $score > 0,
            score: $score,
            maxScore: $maxScore,
            feedback: $feedback,
        );
    }

    public function getPercentage(): float
    {
        return $this->maxScore > 0
            ? round(($this->score / $this->maxScore) * 100, 2)
            : 0;
    }
}
```

### 7. DomainServiceProvider Registration

```php
// app/Providers/DomainServiceProvider.php
protected function registerGradingStrategies(): void
{
    // Tag all grading strategies
    $this->app->tag([
        MultipleChoiceGradingStrategy::class,
        TrueFalseGradingStrategy::class,
        ShortAnswerGradingStrategy::class,
        ManualGradingStrategy::class,
    ], 'grading.strategies');

    // Register the resolver with tagged strategies
    $this->app->singleton(GradingStrategyResolverContract::class, function ($app) {
        return new GradingStrategyResolver(
            $app->tagged('grading.strategies')
        );
    });
}

protected function registerPrerequisiteEvaluators(): void
{
    // Tag all evaluators
    $this->app->tag([
        SequentialPrerequisiteEvaluator::class,
        ImmediatePreviousPrerequisiteEvaluator::class,
        NoPrerequisiteEvaluator::class,
    ], 'learning_path.prerequisite_evaluators');

    // Factory as singleton — it resolves per-Path, so there is no default binding
    $this->app->singleton(PrerequisiteEvaluatorFactory::class);
}
```

> Progress calculation has **no** registration block. `ProgressTrackingService`
> type-hints `AssessmentInclusiveProgressCalculator` directly and the container
> autowires it — no tag, no bind, no factory (ADR 008).

## Resolver vs Factory Pattern

| Pattern | Use When | Example |
|---------|----------|---------|
| **Resolver** | Multiple strategies, one selected at runtime based on input | GradingStrategyResolver - picks strategy by question type |
| **Factory** | Selection driven by stored data or config | PrerequisiteEvaluatorFactory - picks by `learning_paths.prerequisite_mode` |

## Gotchas & Best Practices

1. **Use `supports()` method** - Strategies should self-identify
2. **Return Result DTOs** - Not raw values, for consistency
3. **Factory methods on Result DTOs** - `::correct()`, `::partial()`, `::incorrect()`
4. **Tag strategies** - Use `$this->app->tag()` for resolver injection
5. **Config-driven defaults** - `config('lms.setting_name')` with fallback
6. **Factories get singletons** - `$this->app->singleton(Factory::class)`
7. **Strategies are transient** - No singleton needed, created fresh

## Adding a New Strategy

1. Create strategy class implementing contract
2. Add to tag array in DomainServiceProvider
3. (If factory) Add case to match expression
4. Write tests for the new strategy

```php
// 1. Create strategy
class MyNewGradingStrategy implements GradingStrategyContract
{
    public function supports(Question $question): bool
    {
        return $question->question_type === 'my_type';
    }

    public function getHandledTypes(): array
    {
        return ['my_type'];
    }

    public function grade(Question $question, mixed $answer): GradingResult
    {
        // Implementation
    }
}

// 2. Register in DomainServiceProvider
$this->app->tag([
    // existing...
    MyNewGradingStrategy::class,  // add
], 'grading.strategies');
```

## Quick Reference

```bash
# Files to reference
app/Domain/Assessment/Contracts/GradingStrategyContract.php
app/Domain/Assessment/Services/GradingStrategyResolver.php
app/Domain/Assessment/Strategies/MultipleChoiceGradingStrategy.php
app/Domain/LearningPath/Services/PrerequisiteEvaluatorFactory.php
app/Domain/LearningPath/Strategies/SequentialPrerequisiteEvaluator.php
app/Providers/DomainServiceProvider.php
```

## Decision Checklist: Strategy vs Simple Code

Before implementing strategy pattern, answer YES to at least 3:

- [ ] **Multiple algorithms exist TODAY** (not "might need later")
- [ ] **Algorithms are interchangeable** with same interface
- [ ] **Selection happens at runtime** (not build time)
- [ ] **Business requires configurability** (admin/user choice)
- [ ] **Each strategy has different logic** (not just config flags)

If fewer than 3 checked → use simple code:
- `if/else` or `match` expression
- Config value with single implementation
- Method extraction, not pattern
