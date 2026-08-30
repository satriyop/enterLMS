<?php

/**
 * Named capability bundles for a dedicated install (ADR 010).
 *
 * Domain code must not read these names. It checks Academy::enabled()
 * and Academy::label(). Every preset must declare the same feature keys.
 */

return [

    /*
    | Current product: AI-first academy, two roles, local registration.
    */
    'academy' => [
        'features' => [
            'offerings' => false,
            'facilitators' => false,
            'attendance' => false,
            'letter_grades' => false,
            'academic_calendar' => false,
            'sso' => false,
        ],
        'labels' => [
            'offering' => 'Offering',
            'facilitator' => 'Facilitator',
            'learner' => 'Learner',
        ],
        'identity' => [
            'scheme' => 'email',
            'label' => 'Email',
        ],
    ],

    /*
    | University / school. Override identity (NIM vs NISN) and labels
    | (Dosen vs Guru, Mahasiswa vs Siswa) via env — not a third preset.
    */
    'academic' => [
        'features' => [
            'offerings' => true,
            'facilitators' => true,
            'attendance' => false,
            'letter_grades' => false,
            'academic_calendar' => false,
            'sso' => false,
        ],
        'labels' => [
            'offering' => 'Kelas',
            'facilitator' => 'Dosen',
            'learner' => 'Mahasiswa',
        ],
        'identity' => [
            'scheme' => 'nim',
            'label' => 'NIM',
        ],
    ],

    /*
    | Bank / vendor / corporate L&D.
    */
    'corporate' => [
        'features' => [
            'offerings' => true,
            'facilitators' => true,
            'attendance' => false,
            'letter_grades' => false,
            'academic_calendar' => false,
            'sso' => true,
        ],
        'labels' => [
            'offering' => 'Batch',
            'facilitator' => 'PIC',
            'learner' => 'Karyawan',
        ],
        'identity' => [
            'scheme' => 'employee_id',
            'label' => 'NIP',
        ],
    ],

];
