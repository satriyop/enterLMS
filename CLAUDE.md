# EnterLMS - Project Guidelines

> ⚠️ **NOT A PRODUCTION APPLICATION YET** — This is still development project. No need to consider backward compatibility when refactoring. Feel free to make breaking changes.

---

## 🛑 STOP - READ THIS BEFORE EVERY IMPLEMENTATION DECISION
**This codebase may not align the business workflow. Always consider what this code workflow belongs to, peruse the code diligently**

### Mandatory Questions Before Adding or Fixing Code

1. **How Does Laravel way to solve this?**
    - many use case already battery included using laravel convention and solution. Prioritize this.

2. **Am I following a pattern just because it exists here?**
   - Existing patterns may be WRONG. Question them.
   - "Consistency" with a bad pattern = more bad code
   - Ask: "If I started fresh, would I suggest it this way?"

3. **Is this going to solve the REAL FIXING ROOT CAUSE or just Fixing the SYMPTOM?**
   - Dont hide the underlying problem, dont be lazy, seek what causing this issue ?
   - again, always consider and questioning, what is the impact for the current business/domain workflow ?

### Mandatory Questions Before Adding Abstraction


## 🛑 STOP - READ THIS BEFORE EVERY REFACTORING/ARCHITECTURE DECISION

**DO NOT OVER-ENGINEERED.**

### Mandatory Questions Before Adding Abstraction

1. **Does Laravel already solve this?**
   - Response transformation? → Use `JsonResource`, NOT value objects + DTOs
   - Data validation? → Use `FormRequest`, NOT input DTOs
   - Query optimization? → Use `Resource::collection()` with eager loading, NOT custom transformers

2. **Am I following a pattern just because it exists here?**
   - Existing patterns may be WRONG. Question them.
   - "Consistency" with a bad pattern = more bad code
   - Ask: "If I started fresh, would I build it this way?"

3. **Is this solving a REAL problem or a HYPOTHETICAL one?**
   - Strategy pattern for 1 implementation = over-engineering
   - Value objects to "prevent models in responses" = Laravel Resources do this already
   - Contracts for services with 1 implementation = unnecessary ceremony

### What To Do Instead

| Don't | Do |
|-------|-----|
| Create ValueObject + DTO layers | Use `JsonResource` for API responses |
| Make thin models + fat services | Put behavior IN the model, services orchestrate |
| Add Strategy pattern for flexibility | Build the simple thing, refactor IF needed later |
| Create contracts for every service | Only abstract what you'll actually swap |
| Extend existing over-engineering | SIMPLIFY - delete code, merge layers |

### The ROUND9 Lesson (January 2026)

Claude proposed refactoring DTOs to use value objects. This made the codebase MORE complex when Laravel Resources would have solved the same problem in 10 lines. **Pattern consistency is not a virtue when the pattern is wrong.**

**When in doubt: Can we DELETE code instead of adding it?**

---

## Quick Reference: Skills Directory

This project has detailed architectural patterns documented in `.claude/skills/`:

| Skill | Use When |
|-------|----------|
| `enterlms-architecture` | Creating services, rich models, DTOs, JsonResource, DomainServiceProvider |
| `enterlms-state-machines` | Working with CourseState, EnrollmentState, status transitions |
| `enterlms-events` | Domain events, listeners, audit logging |
| `enterlms-strategies` | Grading strategies, prerequisite evaluators (progress is NOT a strategy — ADR 008) |
| `enterlms-testing` | Pest tests, factory states, policy tests, global helpers |
| `enterlms-frontend` | Vue 3 + Inertia pages, composables, TypeScript types, constants/formatters |
| `enterlms-crud` | CRUD pages, controllers, FormRequests, PageHeader/DataCard/FilterTabs |
| `enterlms-component-architecture` | Extracting Vue components, refactoring large pages |
| `enterlms-learning-path` | Learning path enrollment, cross-domain sync, progress tracking |
| `enterlms-n1-prevention` | N+1 queries, accessor traps, RequiresEagerLoading trait |
| `enterlms-batch-loading` | Batch loading, DB aggregation, replacing loop queries |
| `enterlms-eloquent-gotchas` | fresh() vs refresh(), transaction patterns, stale data |
| `enterlms-concurrency` | Race conditions, pessimistic locking, concurrent enrollment handling |
| `enterlms-resource-scoping` | Nested route authorization, scoped validation rules, ownership verification |
| `enterlms-policy-context` | Policy authorization with context DTOs, FormRequest authorize |
| `enterlms-db-indexing` | Slow queries, composite indexes, query optimization |
| `enterlms-debugging` | Root cause analysis, type errors, data transformation issues |
| `enterlms-phpstan-shapes` | PHPDoc array shapes, static analysis, fromArray type hints |

**Always check relevant skills before implementing features.**

---

## EnterLMS Architecture Overview

```
app/
├── Domain/                      # Bounded Contexts (DDD)
│   ├── Assessment/              # Grading, strategies, attempts
│   ├── Course/                  # Course states, content
│   ├── Enrollment/              # Enrollment lifecycle, events
│   ├── LearningPath/            # Path enrollment, prerequisites
│   ├── Progress/                # Progress tracking, calculators
│   └── Shared/                  # Base DTOs, contracts, value objects
├── Http/Controllers/            # Thin controllers (delegate to services)
├── Models/                      # Eloquent models with state casts
├── Policies/                    # Authorization (role × state × ownership)
└── Providers/
    ├── DomainServiceProvider    # Service bindings, strategy tags
    └── EventServiceProvider     # Domain event → listener mappings
```

### Key Patterns Used
- **State Machines**: `spatie/laravel-model-states` for Course, Enrollment status
- **Strategy Pattern**: Grading strategies, prerequisite evaluators (tagged services). Progress calculation is a single plain class — see ADR 008
- **Domain Events**: `UserEnrolled`, `CoursePublished`, etc. with audit logging
- **Rich Models**: Models own state transitions (`$enrollment->drop()`, `$enrollment->complete()`)
- **Service Layer**: Services return Models, injected directly (no contracts for single-impl)
- **JsonResource**: `app/Http/Resources/` for API/Inertia transformation
- **Input DTOs**: Standalone readonly classes for service parameters

---

## Static Analysis with Larastan

This project uses **Larastan** (PHPStan for Laravel) for static type analysis. It catches bugs before runtime.

### Running PHPStan

```bash
# Run analysis (uses phpstan.neon config)
./vendor/bin/phpstan analyse

# Generate baseline for new errors to fix later
./vendor/bin/phpstan analyse --generate-baseline
```

### Benefits

| What It Catches | Example |
|-----------------|---------|
| Typos in array keys | `$data['progrss']` instead of `$data['progress']` |
| Type mismatches | Passing `string` to `DateTimeInterface` parameter |
| Missing properties | Accessing `$model->tittle` (typo) |
| Null safety issues | Calling method on possibly-null value |
| Wrong argument types | `notify(Course $course)` receiving `Model|null` |

### Array Shapes for DTOs

**Always add PHPDoc array shapes** to `fromArray()` methods in DTOs. This enables:
- IDE autocomplete for array keys
- Static analysis catches missing/wrong keys
- Self-documenting code

```php
/**
 * @param  array{
 *     user_id: int,
 *     course_id: int,
 *     invited_by?: int|null,
 *     enrolled_at?: string|null
 * }  $data
 */
public static function fromArray(array $data): static
```

For complex nested structures, use `@phpstan-type` aliases:

```php
/**
 * @phpstan-type ProgressDataArray array{
 *     id: int,
 *     enrollment_id: int,
 *     lesson_id: int,
 *     is_completed: bool,
 *     progress_percentage?: int
 * }
 */
final class ProgressResult extends DataTransferObject
{
    /**
     * @param  array{
     *     progress: ProgressDataArray,
     *     course_percentage: float
     * }  $data
     */
    public static function fromArray(array $data): static
```

### Baseline Strategy

The project uses a **baseline** (`phpstan-baseline.neon`) for existing errors:
- New code must pass PHPStan (no new errors)
- Existing errors are tracked and fixed incrementally
- Run `--generate-baseline` after fixing batches of errors

### Configuration

See `phpstan.neon` for:
- Analysis level (currently 5)
- Excluded paths
- Ignored error patterns

---

## Database Query Strategy (EnterLMS-Specific)

Use the right tool for the job - NOT a blanket "avoid DB::" rule:

| Scenario | Use | Why |
|----------|-----|-----|
| CRUD operations | Eloquent Models | Events, observers, casts needed |
| Single record fetch | `Model::find()` | Simple, triggers model events |
| Dashboards/Reports (100+ rows) | `DB::table()` | Avoids hydration overhead |
| Aggregations (SUM, AVG, COUNT) | `DB::table()` | Let database do math |
| Bulk inserts (seeding, imports) | `DB::table()->insert()` | Much faster |
| Complex joins for read-only display | `DB::table()` or `->toBase()` | Lightweight stdClass |

**Rule of thumb**: If you don't need model events/casts/relations, use `DB::table()` for better performance.

---

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.14
- inertiajs/inertia-laravel (INERTIA) - v2
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/wayfinder (WAYFINDER) - v0
- larastan/larastan (LARASTAN) - v3
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v3
- phpunit/phpunit (PHPUNIT) - v11
- @inertiajs/vue3 (INERTIA) - v2
- tailwindcss (TAILWINDCSS) - v4
- vue (VUE) - v3
- @laravel/vite-plugin-wayfinder (WAYFINDER) - v0
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== inertia-laravel/core rules ===

## Inertia Core

- Inertia.js components should be placed in the `resources/js/Pages` directory unless specified differently in the JS bundler (vite.config.js).
- Use `Inertia::render()` for server-side routing instead of traditional Blade views.
- Use `search-docs` for accurate guidance on all things Inertia.

<code-snippet lang="php" name="Inertia::render Example">
// routes/web.php example
Route::get('/users', function () {
    return Inertia::render('Users/Index', [
        'users' => User::all()
    ]);
});
</code-snippet>


=== inertia-laravel/v2 rules ===

## Inertia v2

- Make use of all Inertia features from v1 & v2. Check the documentation before making any changes to ensure we are taking the correct approach.

### Inertia v2 New Features
- Polling
- Prefetching
- Deferred props
- Infinite scrolling using merging props and `WhenVisible`
- Lazy loading data on scroll

### Deferred Props & Empty States
- When using deferred props on the frontend, you should add a nice empty state with pulsing / animated skeleton.

### Inertia Form General Guidance
- The recommended way to build forms when using Inertia is with the `<Form>` component - a useful example is below. Use `search-docs` with a query of `form component` for guidance.
- Forms can also be built using the `useForm` helper for more programmatic control, or to follow existing conventions. Use `search-docs` with a query of `useForm helper` for guidance.
- `resetOnError`, `resetOnSuccess`, and `setDefaultsOnSuccess` are available on the `<Form>` component. Use `search-docs` with a query of 'form component resetting' for guidance.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== wayfinder/core rules ===

## Laravel Wayfinder

Wayfinder generates TypeScript functions and types for Laravel controllers and routes which you can import into your client side code. It provides type safety and automatic synchronization between backend routes and frontend code.

### Development Guidelines
- Always use `search-docs` to check wayfinder correct usage before implementing any features.
- Always Prefer named imports for tree-shaking (e.g., `import { show } from '@/actions/...'`)
- Avoid default controller imports (prevents tree-shaking)
- Run `php artisan wayfinder:generate` after route changes if Vite plugin isn't installed

### Feature Overview
- Form Support: Use `.form()` with `--with-form` flag for HTML form attributes — `<form {...store.form()}>` → `action="/posts" method="post"`
- HTTP Methods: Call `.get()`, `.post()`, `.patch()`, `.put()`, `.delete()` for specific methods — `show.head(1)` → `{ url: "/posts/1", method: "head" }`
- Invokable Controllers: Import and invoke directly as functions. For example, `import StorePost from '@/actions/.../StorePostController'; StorePost()`
- Named Routes: Import from `@/routes/` for non-controller routes. For example, `import { show } from '@/routes/post'; show(1)` for route name `post.show`
- Parameter Binding: Detects route keys (e.g., `{post:slug}`) and accepts matching object properties — `show("my-post")` or `show({ slug: "my-post" })`
- Query Merging: Use `mergeQuery` to merge with `window.location.search`, set values to `null` to remove — `show(1, { mergeQuery: { page: 2, sort: null } })`
- Query Parameters: Pass `{ query: {...} }` in options to append params — `show(1, { query: { page: 1 } })` → `"/posts/1?page=1"`
- Route Objects: Functions return `{ url, method }` shaped objects — `show(1)` → `{ url: "/posts/1", method: "get" }`
- URL Extraction: Use `.url()` to get URL string — `show.url(1)` → `"/posts/1"`

### Example Usage

<code-snippet name="Wayfinder Basic Usage" lang="typescript">
    // Import controller methods (tree-shakable)
    import { show, store, update } from '@/actions/App/Http/Controllers/PostController'

    // Get route object with URL and method...
    show(1) // { url: "/posts/1", method: "get" }

    // Get just the URL...
    show.url(1) // "/posts/1"

    // Use specific HTTP methods...
    show.get(1) // { url: "/posts/1", method: "get" }
    show.head(1) // { url: "/posts/1", method: "head" }

    // Import named routes...
    import { show as postShow } from '@/routes/post' // For route name 'post.show'
    postShow(1) // { url: "/posts/1", method: "get" }
</code-snippet>


### Wayfinder + Inertia
If your application uses the `<Form>` component from Inertia, you can use Wayfinder to generate form action and method automatically.
<code-snippet name="Wayfinder Form Component (Vue)" lang="vue">

<Form v-bind="store.form()"><input name="title" /></Form>

</code-snippet>


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== pest/core rules ===

## Pest

### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `php artisan make:test --pest <name>`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

### Running Tests
- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest Example Asserting postJson Response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

### Mocking
- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets
- Use datasets in Pest to simplify tests which have a lot of duplicated data. This is often the case when testing validation rules, so consider going with this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>


=== inertia-vue/core rules ===

## Inertia + Vue

- Vue components must have a single root element.
- Use `router.visit()` or `<Link>` for navigation instead of traditional links.

<code-snippet name="Inertia Client Navigation" lang="vue">

    import { Link } from '@inertiajs/vue3'
    <Link href="/">Home</Link>

</code-snippet>


=== inertia-vue/v2/forms rules ===

## Inertia + Vue Forms

<code-snippet name="`<Form>` Component Example" lang="vue">

<Form
    action="/users"
    method="post"
    #default="{
        errors,
        hasErrors,
        processing,
        progress,
        wasSuccessful,
        recentlySuccessful,
        setError,
        clearErrors,
        resetAndClearErrors,
        defaults,
        isDirty,
        reset,
        submit,
  }"
>
    <input type="text" name="name" />

    <div v-if="errors.name">
        {{ errors.name }}
    </div>

    <button type="submit" :disabled="processing">
        {{ processing ? 'Creating...' : 'Create User' }}
    </button>

    <div v-if="wasSuccessful">User created successfully!</div>
</Form>

</code-snippet>


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v4 rules ===

## Tailwind 4

- Always use Tailwind CSS v4 - do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, configuration is CSS-first using the `@theme` directive — no separate `tailwind.config.js` file is needed.
<code-snippet name="Extending Theme in CSS" lang="css">
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
</code-snippet>

- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>


### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option - use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.


=== .ai/general-workflow rules ===

# General workflow
1. understand guidelines (general-requirement, implementation-workflow).
2. understand the choosen user story we are working on. ({user-story}-story.md).
3. verify the user story (you might want to questioning to clarify).
4. refine the user story by creating new version of user story(refined-{filename}.md).
5. create specification based on the general-requirement & refined user story.
5. understand & verify the initial specification.
6. refine the specification by creating new version (refined-{filename}.md).


=== .ai/general-requirement rules ===

# General Requirement

## Read CONTEXT.md first

`CONTEXT.md` at the repo root is the authority on what this product is, who uses
it, and what is out of scope. `docs/adr/` holds the decisions and the reasoning
behind them.

Read `CONTEXT.md` before any implementation decision. The product decision is
`docs/adr/001-ai-first-class-lms.md`.

**Do not restate its contents here.** This file is composed into `CLAUDE.md` and
loaded into every session automatically, so a domain fact copied into it keeps
being asserted long after the decision that changed it — and it outranks the
correct file, because only the copy is loaded.

This file carries only requirements that hold regardless of what the domain is.
Anything that would need editing if the product were repositioned again belongs
in `CONTEXT.md` or an ADR, not here.

Scope is enforced, not merely documented — see `tests/Feature/Docs/`.

## Build requirements

- Primary language is Bahasa Indonesia, including validation messages. Seed data
  uses Indonesian names and context.
- Responsive UI is mandatory; every page must work on mobile.
- Every feature ships with tests. See the test enforcement rules in `CLAUDE.md`.
- All input is validated and sanitised. Authorization is enforced by policy,
  never by hiding UI.
- The UI conforms to the Tenang design system: semantic tokens and editorial
  type, no stock Tailwind hues (ADR 007). Gate:
  `tests/Feature/Design/TenangConformanceTest.php`.
- Prefer deleting code over adding abstraction. See the anti-over-engineering
  rules at the top of `CLAUDE.md`.


=== .ai/implementation-workflow rules ===

# Implementation Workflow Guideline
1. create database migration.
2. create database seeder on relevant user story.
3. create route when needed.
3. create user interface component for relevant front end tech stack.
4. create tesk.


=== laravel/fortify rules ===

## Laravel Fortify

Fortify is a headless authentication backend that provides authentication routes and controllers for Laravel applications.

**Before implementing any authentication features, use the `search-docs` tool to get the latest docs for that specific feature.**

### Configuration & Setup
- Check `config/fortify.php` to see what's enabled. Use `search-docs` for detailed information on specific features.
- Enable features by adding them to the `'features' => []` array: `Features::registration()`, `Features::resetPasswords()`, etc.
- To see the all Fortify registered routes, use the `list-routes` tool with the `only_vendor: true` and `action: "Fortify"` parameters.
- Fortify includes view routes by default (login, register). Set `'views' => false` in the configuration file to disable them if you're handling views yourself.

### Customization
- Views can be customized in `FortifyServiceProvider`'s `boot()` method using `Fortify::loginView()`, `Fortify::registerView()`, etc.
- Customize authentication logic with `Fortify::authenticateUsing()` for custom user retrieval / validation.
- Actions in `app/Actions/Fortify/` handle business logic (user creation, password reset, etc.). They're fully customizable, so you can modify them to change feature behavior.

## Available Features
- `Features::registration()` for user registration.
- `Features::emailVerification()` to verify new user emails.
- `Features::twoFactorAuthentication()` for 2FA with QR codes and recovery codes.
  - Add options: `['confirmPassword' => true, 'confirm' => true]` to require password confirmation and OTP confirmation before enabling 2FA.
- `Features::updateProfileInformation()` to let users update their profile.
- `Features::updatePasswords()` to let users change their passwords.
- `Features::resetPasswords()` for password reset via email.
</laravel-boost-guidelines>

---

## Quick File Reference

| Need to... | Look at |
|------------|---------|
| Add a service binding | `app/Providers/DomainServiceProvider.php` |
| Register event listeners | `app/Providers/EventServiceProvider.php` |
| Add middleware | `bootstrap/app.php` |
| See factory states | `database/factories/{Model}Factory.php` |
| Add global test helpers | `tests/Pest.php` |
| See page component patterns | `resources/js/pages/courses/` |
| See CRUD components | `resources/js/components/crud/` |
| Add TypeScript types | `resources/js/types/models/` |
| See composable patterns | `resources/js/composables/` |
| See status colors/labels | `resources/js/lib/constants.ts`, `resources/js/lib/formatters.ts` |
| Add API/Inertia resource | `app/Http/Resources/` |
| Configure static analysis | `phpstan.neon`, `phpstan-baseline.neon` |
| See DTO array shape examples | `app/Domain/Progress/DTOs/ProgressResult.php` |

## Common Gotchas

1. **Vite build required** - After changing frontend code, run `npm run build` for production or ensure `npm run dev` is running
2. **Wayfinder regenerate** - After adding routes, run `php artisan wayfinder:generate` (or restart Vite dev server)
3. **Policy not found** - Check `AuthServiceProvider` or use `Gate::policy()` in provider
4. **State mutation bug** - NEVER use `$model->state = SomeState::$name; $model->save();` — corrupts state! Use `$model->update(['state' => SomeState::class])` or `->transitionTo()`
5. **Race conditions** - Wrap enrollment + invitation updates in single `DB::transaction()` with `lockForUpdate()`. Catch `QueryException` (code 1062) as fallback
6. **Test factories** - Use states like `->published()`, `->draft()` instead of manually setting attributes
7. **FormRequest authorize** - Return `Gate::allows('action', $model)`, not just `true`
8. **Indonesian messages** - All validation messages should be in Bahasa Indonesia
9. **PHPStan errors** - New code must pass `./vendor/bin/phpstan analyse`. Add array shapes to DTOs' `fromArray()` methods
10. **Nested route scoping** - Route model binding doesn't auto-scope children to parents. Always verify ownership (`$child->parent_id === $parent->id`) or use `Rule::exists()->where()` for submitted IDs
11. **RequiresEagerLoading throws in tests** - Models using `RequiresEagerLoading` trait throw when accessing counts/averages without eager loading. Reload with `Course::withCount('lessons')->find($id)` in tests
12. **Services return Models** - Services return Eloquent models, not DTOs. Controllers transform using `JsonResource` for API/Inertia responses

## Agent skills

### Issue tracker

GitHub Issues for `satriyop/enterLMS` via the `gh` CLI. See `docs/agents/issue-tracker.md`.

### Triage labels

Default vocabulary: `needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human`, `wontfix`. See `docs/agents/triage-labels.md`.

### Domain docs

Single-context: one `CONTEXT.md` at the repo root and ADRs in `docs/adr/`. See `docs/agents/domain.md`.
