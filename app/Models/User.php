<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string $role
 * @property string|null $avatar
 * @property string|null $bio
 * @property string|null $remember_token
 * @property Carbon|null $two_factor_confirmed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Course> $courses
 * @property-read Collection<int, Enrollment> $enrollments
 * @property-read Collection<int, Course> $enrolledCourses
 * @property-read Collection<int, CourseInvitation> $courseInvitations
 * @property-read Collection<int, CourseInvitation> $pendingInvitations
 * @property-read Collection<int, CourseRating> $courseRatings
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * Available user roles in the system.
     *
     * Core roles:
     * - learner: Takes courses, completes assessments
     * - content_manager: Creates and manages course content
     * - trainer: Manages learners, grades assessments
     * - lms_admin: Full system administration
     *
     * Enterprise roles:
     * - compliance_officer: Manages compliance, views audit reports, can generate reports
     * - auditor: Read-only access to audit logs and compliance reports
     * - teaching_assistant: Limited trainer capabilities (view enrollments, assist grading)
     */
    public const ROLES = [
        'learner',
        'content_manager',
        'trainer',
        'lms_admin',
        'compliance_officer',
        'auditor',
        'teaching_assistant',
    ];

    /**
     * Roles that can manage courses (create, edit, publish).
     */
    public const COURSE_MANAGER_ROLES = [
        'content_manager',
        'trainer',
        'lms_admin',
    ];

    /**
     * Roles that can view compliance/audit data.
     */
    public const COMPLIANCE_ROLES = [
        'lms_admin',
        'compliance_officer',
        'auditor',
    ];

    /**
     * Roles that can grade assessments.
     */
    public const GRADING_ROLES = [
        'trainer',
        'lms_admin',
        'teaching_assistant',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function enrolledCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'enrollments')
            ->withPivot(['status', 'progress_percentage', 'enrolled_at', 'started_at', 'completed_at', 'last_lesson_id'])
            ->withTimestamps();
    }

    public function courseInvitations(): HasMany
    {
        return $this->hasMany(CourseInvitation::class);
    }

    public function pendingInvitations(): HasMany
    {
        return $this->hasMany(CourseInvitation::class)
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function courseRatings(): HasMany
    {
        return $this->hasMany(CourseRating::class);
    }

    public function isLearner(): bool
    {
        return $this->role === 'learner';
    }

    public function isContentManager(): bool
    {
        return $this->role === 'content_manager';
    }

    public function isTrainer(): bool
    {
        return $this->role === 'trainer';
    }

    public function isLmsAdmin(): bool
    {
        return $this->role === 'lms_admin';
    }

    public function isComplianceOfficer(): bool
    {
        return $this->role === 'compliance_officer';
    }

    public function isAuditor(): bool
    {
        return $this->role === 'auditor';
    }

    public function isTeachingAssistant(): bool
    {
        return $this->role === 'teaching_assistant';
    }

    public function canManageCourses(): bool
    {
        return in_array($this->role, self::COURSE_MANAGER_ROLES, true);
    }

    /**
     * Check if user can manage learning paths.
     */
    public function canManageLearningPaths(): bool
    {
        return in_array($this->role, ['content_manager', 'lms_admin'], true);
    }

    /**
     * Check if user can view compliance/audit reports.
     */
    public function canViewCompliance(): bool
    {
        return in_array($this->role, self::COMPLIANCE_ROLES, true);
    }

    /**
     * Check if user can grade assessments.
     */
    public function canGradeAssessments(): bool
    {
        return in_array($this->role, self::GRADING_ROLES, true);
    }

    /**
     * Check if user has a specific role or one of the given roles.
     *
     * @param  string|array<int, string>  $role
     */
    public function hasRole(string|array $role): bool
    {
        if (is_array($role)) {
            return in_array($this->role, $role, true);
        }

        return $this->role === $role;
    }
}
