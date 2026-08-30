export type AcademyFeature =
    | 'offerings'
    | 'facilitators'
    | 'attendance'
    | 'letter_grades'
    | 'academic_calendar'
    | 'sso';

export type AcademyLabel = 'offering' | 'facilitator' | 'learner';

export interface AcademyFeatures {
    offerings: boolean;
    facilitators: boolean;
    attendance: boolean;
    letter_grades: boolean;
    academic_calendar: boolean;
    sso: boolean;
}

export interface AcademyLabels {
    offering: string;
    facilitator: string;
    learner: string;
}

export interface AcademyIdentity {
    scheme: string;
    label: string;
}

/**
 * Shared Inertia academy payload. The preset name is intentionally absent
 * (ADR 010): branch on features and labels, never on academic vs corporate.
 */
export interface AcademyShared {
    features: AcademyFeatures;
    labels: AcademyLabels;
    identity: AcademyIdentity;
}
