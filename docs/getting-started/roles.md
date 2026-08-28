# Understanding User Roles

EnterLMS models **two** roles: `learner` and `lms_admin`.

ADR 007 collapsed the earlier seven (content manager, trainer, teaching assistant,
compliance officer, auditor, …) into these two. They are the only roles this
academy models.

## The two roles

| Role | Code | What they do |
|------|------|--------------|
| Learner | `learner` | Enroll, view lessons, take assessments, rate Courses, earn Certificates |
| LMS Admin | `lms_admin` | Everything a Learner does, plus: author and publish Courses and Learning Paths, grant Enrollment to Restricted Courses, grade, and report |

An LMS Admin may also be a Learner — the roles are not exclusive in practice, since
the founder takes their own Courses.

In this phase **LMS Admin is only the founder**. There is no second staff member to
be isolated from, which is why the policies below are broader than they would be in
a multi-tenant product.

## Checking a role

```php
$user->isLearner();
$user->isLmsAdmin();
```

Prefer the **capability** methods over the role check in authorization code:

```php
$user->canManageCourses();
$user->canManageLearningPaths();
$user->canGradeAssessments();
```

All three currently return `$this->isLmsAdmin()`. They exist as the seam where a
future role regains a grant without every policy having to be rewritten — see ADR 007.

In Vue, the role arrives on the shared Inertia props:

```ts
const page = usePage();
const isLmsAdmin = computed(() => page.props.auth.user?.role === 'lms_admin');
```

Use this to decide what to *show*. Never use it to decide what is *allowed* —
authorization belongs in a policy, and hiding a button is not access control.

## What the collapse cost

ADR 007 recorded two authorization boundaries that were given up deliberately:

- **Draft-only editing is gone.** `CoursePolicy::update()` returns `true` for any
  LMS Admin before the ownership branch is reached, so published Courses are no
  longer frozen to their author. The ownership branch is kept as the seam, but it
  is unreachable today.
- **Rating moderation is self-moderation.** The founder is both author and
  moderator, so they can delete ratings on their own Courses.

Neither is defensible in a multi-tenant product. Both are correct while the academy
has one operator. Do not "fix" them without an ADR that also phases in the second
role.

## Changing a user's role

```php
$user->update(['role' => 'lms_admin']);
```

Only `learner` and `lms_admin` are valid. The migration
`2026_08_13_165251_collapse_roles_to_learner_and_lms_admin` mapped every retired
role onto one of these; reintroducing an old string will fail the enum constraint.

## Related

- `CONTEXT.md` — the vocabulary
- [ADR 001](../adr/001-ai-first-class-lms.md) — the product (AI-first LMS)
- [ADR 005](../adr/005-identity-phasing.md) — local registration
- [ADR 007](../adr/007-tenang-conformance.md) — the role collapse and what it cost
