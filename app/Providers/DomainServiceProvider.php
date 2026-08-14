<?php

namespace App\Providers;

use App\Domain\Assessment\Services\GradingStrategyResolver;
use App\Domain\Assessment\Strategies\ManualGradingStrategy;
use App\Domain\Assessment\Strategies\MultipleChoiceGradingStrategy;
use App\Domain\Assessment\Strategies\ShortAnswerGradingStrategy;
use App\Domain\Assessment\Strategies\TrueFalseGradingStrategy;
use App\Domain\LearningPath\Services\PrerequisiteEvaluatorFactory;
use App\Domain\LearningPath\Strategies\ImmediatePreviousPrerequisiteEvaluator;
use App\Domain\LearningPath\Strategies\NoPrerequisiteEvaluator;
use App\Domain\LearningPath\Strategies\SequentialPrerequisiteEvaluator;
// Observability Services
use App\Domain\Shared\Services\DomainLogger;
use App\Domain\Shared\Services\HealthCheckService;
use App\Domain\Shared\Services\LogContext;
use App\Domain\Shared\Services\MetricsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerGradingStrategies();
        $this->registerPrerequisiteEvaluators();
        $this->registerObservabilityServices();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers if needed
    }

    /**
     * Register grading strategies and resolver.
     */
    protected function registerGradingStrategies(): void
    {
        // Tag all grading strategies
        $this->app->tag([
            MultipleChoiceGradingStrategy::class,
            TrueFalseGradingStrategy::class,
            ShortAnswerGradingStrategy::class,
            ManualGradingStrategy::class,
        ], 'grading.strategies');

        // Register the strategy resolver
        $this->app->singleton(GradingStrategyResolver::class, function ($app) {
            return new GradingStrategyResolver(
                $app->tagged('grading.strategies')
            );
        });
    }

    /**
     * Register prerequisite evaluator strategies for Learning Paths.
     */
    protected function registerPrerequisiteEvaluators(): void
    {
        // Tag all evaluators
        $this->app->tag([
            SequentialPrerequisiteEvaluator::class,
            ImmediatePreviousPrerequisiteEvaluator::class,
            NoPrerequisiteEvaluator::class,
        ], 'learning_path.prerequisite_evaluators');

        // Register the factory as singleton
        $this->app->singleton(PrerequisiteEvaluatorFactory::class);
    }

    /**
     * Register observability services (logging, metrics, health checks).
     */
    protected function registerObservabilityServices(): void
    {
        // LogContext as singleton (persists through request)
        $this->app->singleton(LogContext::class);

        // DomainLogger with domain-specific channel
        $this->app->singleton(DomainLogger::class, function ($app) {
            return new DomainLogger(
                $app->make(LogContext::class),
                Log::channel('domain')
            );
        });

        // MetricsService as singleton
        $this->app->singleton(MetricsService::class);

        // HealthCheckService as singleton
        $this->app->singleton(HealthCheckService::class);
    }
}
