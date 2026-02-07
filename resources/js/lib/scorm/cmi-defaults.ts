/**
 * Default CMI data model values for SCORM 1.2 and SCORM 2004.
 */

export const CMI_DEFAULTS_12: Record<string, string> = {
    'cmi.core.lesson_status': 'not attempted',
    'cmi.core.score.raw': '',
    'cmi.core.score.min': '',
    'cmi.core.score.max': '',
    'cmi.core.lesson_location': '',
    'cmi.core.total_time': '0000:00:00.00',
    'cmi.core.session_time': '0000:00:00.00',
    'cmi.core.entry': '',
    'cmi.core.exit': '',
    'cmi.suspend_data': '',
    'cmi.core.credit': 'credit',
    'cmi.core.lesson_mode': 'normal',
};

export const CMI_DEFAULTS_2004: Record<string, string> = {
    'cmi.completion_status': 'not attempted',
    'cmi.success_status': 'unknown',
    'cmi.score.raw': '',
    'cmi.score.min': '',
    'cmi.score.max': '',
    'cmi.score.scaled': '',
    'cmi.location': '',
    'cmi.total_time': 'PT0S',
    'cmi.session_time': 'PT0S',
    'cmi.entry': '',
    'cmi.exit': '',
    'cmi.suspend_data': '',
    'cmi.credit': 'credit',
    'cmi.mode': 'normal',
    'cmi.completion_threshold': '',
    'cmi.scaled_passing_score': '',
};

/** SCORM 1.2 read-only elements that cannot be set by content */
export const READ_ONLY_12 = new Set([
    'cmi.core.student_id',
    'cmi.core.student_name',
    'cmi.core.credit',
    'cmi.core.entry',
    'cmi.core.total_time',
    'cmi.core.lesson_mode',
    'cmi.launch_data',
]);

/** SCORM 2004 read-only elements that cannot be set by content */
export const READ_ONLY_2004 = new Set([
    'cmi.learner_id',
    'cmi.learner_name',
    'cmi.credit',
    'cmi.entry',
    'cmi.total_time',
    'cmi.mode',
    'cmi.launch_data',
    'cmi.completion_threshold',
    'cmi.scaled_passing_score',
]);
