/**
 * Question Bank type definitions
 *
 * Matches the QuestionBankItem and QuestionBankOption models.
 */

import type { SoftDeletes, UserId, QuestionType } from './common';
import type { UserSummary } from './user';

// =============================================================================
// Visibility and Difficulty
// =============================================================================

export type QuestionBankVisibility = 'private' | 'department' | 'public';
export type DifficultyLevel = 'beginner' | 'intermediate' | 'advanced';

// =============================================================================
// Question Bank Item Types
// =============================================================================

/**
 * Full QuestionBankItem model - matches database columns and model accessors.
 */
export interface QuestionBankItem extends SoftDeletes {
    id: number;
    user_id: UserId;
    question_text: string;
    question_type: QuestionType;
    default_points: number;
    feedback: string | null;
    correct_answer: string | null;
    case_sensitive: boolean;
    grading_rubric: string | null;
    difficulty_level: DifficultyLevel | null;
    visibility: QuestionBankVisibility;
    times_used: number;

    // Labels (model accessors)
    question_type_label?: string;
    difficulty_label?: string | null;
    visibility_label?: string;

    // Relations (conditionally loaded)
    user?: UserSummary;
    options?: QuestionBankOption[];
    tags?: Tag[];

    // Counts
    options_count?: number;
    derived_questions_count?: number;
}

/**
 * Question bank option.
 */
export interface QuestionBankOption extends SoftDeletes {
    id: number;
    question_bank_item_id: number;
    option_text: string;
    is_correct: boolean;
    feedback: string | null;
    order: number;
}

/**
 * Tag for categorizing questions.
 */
export interface Tag {
    id: number;
    name: string;
    slug: string;
}

// =============================================================================
// Form Data Types
// =============================================================================

/**
 * Data for creating a question bank item.
 */
export interface CreateQuestionBankItemData {
    question_text: string;
    question_type: QuestionType;
    default_points: number;
    feedback?: string | null;
    correct_answer?: string | null;
    case_sensitive?: boolean;
    grading_rubric?: string | null;
    difficulty_level?: DifficultyLevel | null;
    visibility: QuestionBankVisibility;
    options?: CreateQuestionBankOptionData[];
    tag_ids?: number[];
}

/**
 * Data for updating a question bank item.
 */
export interface UpdateQuestionBankItemData extends CreateQuestionBankItemData {
    options?: Array<CreateQuestionBankOptionData & { id?: number }>;
}

/**
 * Data for creating a question bank option.
 */
export interface CreateQuestionBankOptionData {
    option_text: string;
    is_correct: boolean;
    feedback?: string | null;
    order?: number;
}

/**
 * Data for importing questions to an assessment.
 */
export interface ImportQuestionsData {
    question_bank_item_ids: number[];
}

// =============================================================================
// Filter Types
// =============================================================================

/**
 * Query parameters for filtering question bank items.
 */
export interface QuestionBankFilters {
    search?: string;
    type?: QuestionType;
    difficulty?: DifficultyLevel;
    visibility?: QuestionBankVisibility;
    tag_id?: number;
    mine?: boolean;
}

// =============================================================================
// Permission Types
// =============================================================================

/**
 * Permissions for question bank item actions.
 */
export interface QuestionBankItemPermissions {
    update: boolean;
    delete: boolean;
    duplicate: boolean;
}

// =============================================================================
// Helper Functions
// =============================================================================

/**
 * Get visibility label in Indonesian.
 */
export function getVisibilityLabel(visibility: QuestionBankVisibility): string {
    const labels: Record<QuestionBankVisibility, string> = {
        private: 'Pribadi',
        department: 'Departemen',
        public: 'Publik',
    };
    return labels[visibility];
}

/**
 * Get difficulty label in Indonesian.
 */
export function getDifficultyLabel(difficulty: DifficultyLevel | null): string | null {
    if (!difficulty) return null;
    const labels: Record<DifficultyLevel, string> = {
        beginner: 'Pemula',
        intermediate: 'Menengah',
        advanced: 'Lanjutan',
    };
    return labels[difficulty];
}

/**
 * Get visibility color class for badges.
 */
export function getVisibilityColor(visibility: QuestionBankVisibility): string {
    const colors: Record<QuestionBankVisibility, string> = {
        private: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
        department: 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
        public: 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
    };
    return colors[visibility];
}

/**
 * Get difficulty color class for badges.
 */
export function getDifficultyColor(difficulty: DifficultyLevel | null): string {
    if (!difficulty) return 'bg-gray-100 text-gray-600';
    const colors: Record<DifficultyLevel, string> = {
        beginner: 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
        intermediate: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
        advanced: 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
    };
    return colors[difficulty];
}
