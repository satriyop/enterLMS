---
name: enteraksi-frontend
description: Vue 3 + Inertia + TypeScript patterns for Enteraksi LMS. Use when building Vue components, composables, TypeScript types, or frontend features.
triggers:
  - vue component
  - inertia page
  - frontend
  - composable
  - useComposable
  - typescript type
  - interface
  - model type
  - wayfinder
  - type-safe route
  - dark mode
  - tailwind
  - shadcn
  - status badge
  - constants
  - formatters
---

# Enteraksi Frontend Patterns

## Tech Stack

| Technology | Version | Purpose |
|------------|---------|---------|
| Vue | 3.x | Composition API (`<script setup>`) |
| TypeScript | 5.x | Type safety |
| Inertia.js | v2 | SPA bridge to Laravel |
| Tailwind CSS | v4 | Utility-first styling |
| Shadcn/vue | - | UI primitives (reka-ui based) |
| Lucide | - | Icons (`lucide-vue-next`) |
| Wayfinder | v0 | Type-safe Laravel routes |
| Pinia | - | Complex state management (limited use) |

## Directory Structure

```
resources/js/
├── app.ts                    # Inertia app setup
├── layouts/
│   ├── AppLayout.vue         # Main layout (sidebar)
│   └── AuthLayout.vue        # Auth pages layout
├── pages/                    # Inertia pages (mapped to routes)
│   ├── courses/              # Index, Create, Edit, Show, Detail, Browse
│   ├── assessments/          # Index, Create, Edit, Show, Attempt, Grade
│   ├── learning_paths/       # Index, Create, Edit, Show
│   ├── learner/              # Dashboard, learning path views
│   ├── admin/users/          # User management
│   ├── auth/                 # Login, Register, 2FA, etc.
│   └── settings/             # Profile, Password, Appearance
├── components/
│   ├── ui/                   # Shadcn components (button, card, input, etc.)
│   ├── crud/                 # CRUD page components (PageHeader, DataCard, etc.)
│   ├── features/shared/      # StatusBadge, shared feature components
│   └── {domain}/             # Domain-specific (courses, assessments, etc.)
├── composables/
│   ├── index.ts              # Barrel export
│   ├── data/                 # useCourse, useCourses, useEnrollment
│   ├── features/             # useVideoPlayer, useFileUpload, useGrading
│   └── ui/                   # useModal, useToast, useTabs, usePagination
├── stores/                   # Pinia stores (courseEditor, assessmentAttempt, lessonViewer)
├── types/
│   ├── index.d.ts            # Main type exports
│   └── models/               # Domain model types (common, course, enrollment, etc.)
├── lib/
│   ├── utils.ts              # cn(), debounce
│   ├── constants.ts          # Status colors, file limits, timing constants
│   ├── formatters.ts         # Label formatters (Indonesian), duration, currency
│   ├── icons.ts              # Icon mappings
│   └── string.ts             # String utilities
└── actions/                  # Wayfinder-generated route actions (auto-generated)
```

## Key Patterns

### 1. Page Props - Inline Interface Per Page

Each page defines its own Props interface. No shared `AppPageProps` wrapper:

```typescript
// pages/courses/Index.vue
interface CourseListItem {
    id: number;
    title: string;
    slug: string;
    status: string;
    lessons_count: number;
    // ...
}

interface Props {
    courses: {
        data: CourseListItem[];
        links: PaginationLink[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        status?: string;
        category_id?: string;
    };
    categories: Category[];
}

const props = defineProps<Props>();
```

### 2. Navigation & Filtering with Wayfinder + router.get()

Pages use `router.get()` with Wayfinder URLs for filtering and navigation:

```typescript
import { router } from '@inertiajs/vue3';
import { index, create, show } from '@/actions/App/Http/Controllers/CourseController';

// Filter with debounced search
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
let searchTimeout: ReturnType<typeof setTimeout>;

watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(index().url, {
            search: value,
            status: status.value,
        }, {
            preserveState: true,
            replace: true,
        });
    }, 300);
});

// Navigation
<Link :href="create().url">Buat Kursus</Link>
<Link :href="show.url(course.id)">{{ course.title }}</Link>

// Destructive actions
router.delete(destroy.url(course.id));
router.put(publish.url(course.id));
```

### 3. Wayfinder Route Actions

```typescript
// Import controller actions (tree-shakable)
import { show, store, index } from '@/actions/App/Http/Controllers/CourseController';

show(1)              // { url: '/courses/1', method: 'get' }
show.url(1)          // '/courses/1'
store.form()         // { action: '/courses', method: 'post' }
index({ query: { page: 2 } })

// Named routes
import { show as courseShow } from '@/routes/course';
```

### 4. Status Display: Constants + Formatters + StatusBadge

**Centralized status colors** in `lib/constants.ts`:
```typescript
export const COURSE_STATUS_COLORS: Record<CourseStatus, { bg: string; text: string; border: string }> = {
    draft: { bg: 'bg-gray-100 dark:bg-gray-800', text: 'text-gray-700 dark:text-gray-300', border: '...' },
    published: { bg: 'bg-green-100 dark:bg-green-900', text: '...', border: '...' },
    archived: { bg: 'bg-red-100 dark:bg-red-900', text: '...', border: '...' },
};
// Also: ENROLLMENT_STATUS_COLORS, DIFFICULTY_COLORS, ASSESSMENT_STATUS_COLORS,
//        ATTEMPT_STATUS_COLORS, VISIBILITY_COLORS, CONTENT_TYPE_COLORS
```

**Indonesian label formatters** in `lib/formatters.ts`:
```typescript
courseStatusLabel('published')    // 'Dipublikasikan'
enrollmentStatusLabel('active')  // 'Aktif'
difficultyLabel('beginner')      // 'Pemula'
visibilityLabel('public')        // 'Publik'
contentTypeLabel('video')        // 'Video'
formatDuration(90, 'long')       // '1 jam 30 menit'
formatCurrency(50000)            // 'Rp 50.000'
```

**StatusBadge component** (`components/features/shared/StatusBadge.vue`):
```typescript
interface Props {
    type: 'course' | 'enrollment' | 'difficulty' | 'assessment' | 'attempt' | 'visibility';
    status: string;
    size?: 'sm' | 'md' | 'lg';
    showIcon?: boolean;
}
// Usage: <StatusBadge type="course" :status="course.status" />
```

**Inline badges via DataCard**:
```typescript
const statusBadge = (courseStatus: string) => {
    switch (courseStatus) {
        case 'published': return { label: 'Terbit', variant: 'default' as const };
        case 'draft': return { label: 'Draft', variant: 'secondary' as const };
        case 'archived': return { label: 'Arsip', variant: 'outline' as const };
        default: return { label: courseStatus, variant: 'secondary' as const };
    }
};

<DataCard :badges="[statusBadge(course.status)]" />
```

### 5. Composable Organization

```typescript
// Data composables - API/data fetching logic
import { useCourse, useCourses, useEnrollment } from '@/composables';

// Feature composables - domain-specific logic
import { useVideoPlayer, useFileUpload, useGrading } from '@/composables';

// UI composables - generic UI utilities
import { useModal, useToast, useTabs, usePagination, useConfirmation } from '@/composables';
```

Composable pattern:
```typescript
export function useLessonProgress(options: { courseId: number; lessonId: number }) {
    const isCompleted = ref(false);
    const isSaving = ref(false);

    const saveProgress = async (page: number, total: number) => { /* ... */ };

    onUnmounted(() => { /* cleanup */ });

    return { isCompleted, isSaving, saveProgress };
}
```

### 6. TypeScript Types

Types organized by model in `types/models/`:
```typescript
// types/models/common.ts - Shared types
export type CourseStatus = 'draft' | 'published' | 'archived';
export type EnrollmentStatus = 'pending' | 'active' | 'completed' | 'suspended' | 'cancelled';
export type DifficultyLevel = 'beginner' | 'intermediate' | 'advanced';

// types/models/course.ts - Course-specific
export interface Course { id: number; title: string; status: CourseStatus; /* ... */ }
export interface CourseListItem { /* lighter type for lists */ }

// Type guards exported from types
export function isActive(enrollment: Enrollment): boolean { /* ... */ }
export function isCompleted(enrollment: Enrollment): boolean { /* ... */ }
```

### 7. CRUD Component Layer

Presentational components for list pages:
- `PageHeader` - Title + breadcrumbs + action slot
- `DataCard` - Card for grid/list items (title, badges, meta, actions)
- `FilterTabs` - Status filter tabs
- `SearchInput` - Search field
- `Pagination` - Pagination controls
- `FormSection` - Form section wrapper
- `EmptyState` - Empty state with icon + action button

### 8. Pinia Stores (Limited Use)

Only for complex editing interfaces:
```typescript
// stores/courseEditor.ts - Course editing state
// stores/assessmentAttempt.ts - Assessment attempt tracking
// stores/lessonViewer.ts - Lesson viewing state
```

Simple page state uses `ref()` + `watch()` directly. Provide/Inject for deep component trees.

### 9. API Resources (Backend → Frontend)

JsonResource in `app/Http/Resources/`:
```php
class LearningPathBrowseResource extends JsonResource
{
    public static $wrap = null;  // REQUIRED for Inertia

    public function toArray(Request $request): array { /* ... */ }
}

// CRITICAL: For paginated data, use ->through() not ::collection()
'learningPaths' => $paginatedPaths->through(
    fn ($path) => (new LearningPathBrowseResource($path))->resolve()
),
```

### 10. Layout Pattern

```vue
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });
</script>
```

### 11. Dark Mode

All components include `dark:` variants:
```html
<div class="bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
```

## Constants Reference

| Constant | Location | Purpose |
|----------|----------|---------|
| `COURSE_STATUS_COLORS` | `lib/constants.ts` | Tailwind classes per status |
| `ENROLLMENT_STATUS_COLORS` | `lib/constants.ts` | Enrollment badge colors |
| `DEBOUNCE` | `lib/constants.ts` | search: 300ms, autosave: 1000ms |
| `STORAGE_KEYS` | `lib/constants.ts` | localStorage key names |
| `BREAKPOINTS` | `lib/constants.ts` | Tailwind breakpoint values |

## Gotchas

1. **No Inertia Form component usage** - Pages use `router.get()` / `router.post()` directly
2. **Wayfinder for ALL routes** - Never hardcode URLs
3. **`$wrap = null` on Resources** - Required for Inertia compatibility
4. **`->through()` for pagination** - `::collection()` loses pagination metadata with `$wrap = null`
5. **Indonesian labels everywhere** - All user-facing text in Bahasa Indonesia
6. **Dark mode on everything** - Every new component needs `dark:` variants
7. **cn() for conditional classes** - Never string concatenation
