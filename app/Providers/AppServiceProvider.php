<?php

namespace App\Providers;

use App\Models\Certificate;
use App\Models\ContentProposal;
use App\Models\Conversation;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Media;
use App\Models\Question;
use App\Models\User;
use App\Policies\CertificatePolicy;
use App\Policies\ContentProposalPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\CoursePolicy;
use App\Policies\CourseSectionPolicy;
use App\Policies\LearningPathPolicy;
use App\Policies\LessonPolicy;
use App\Policies\LessonProgressPolicy;
use App\Policies\MediaPolicy;
use App\Policies\QuestionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        RateLimiter::for('mcp', function (Request $request) {
            return Limit::perMinute(120)->by((string) ($request->user()?->id ?: $request->ip()));
        });

        Gate::policy(Certificate::class, CertificatePolicy::class);
        Gate::policy(ContentProposal::class, ContentProposalPolicy::class);
        Gate::policy(Conversation::class, ConversationPolicy::class);
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(CourseSection::class, CourseSectionPolicy::class);
        Gate::policy(Lesson::class, LessonPolicy::class);
        Gate::policy(LessonProgress::class, LessonProgressPolicy::class);
        Gate::policy(LearningPath::class, LearningPathPolicy::class);
        Gate::policy(Media::class, MediaPolicy::class);
        Gate::policy(Question::class, QuestionPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        // Compliance reporting gates
        Gate::define('viewComplianceReports', function (User $user) {
            return $user->canViewCompliance();
        });
    }
}
