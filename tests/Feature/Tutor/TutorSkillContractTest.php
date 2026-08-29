<?php

it('ships a tutor skill that preloads independently of the Telegram gateway', function () {
    $path = base_path('.hermes/skills/tutor/SKILL.md');

    expect($path)->toBeFile();

    $body = file_get_contents($path);
    expect($body)->toBeString();

    expect($body)
        ->toContain('name: tutor')
        ->toContain('hermes chat')
        ->toContain('-s tutor')
        ->toContain('tutor.read')
        ->toContain('--tutor-read')
        ->toContain('enterlms-conversation-')
        ->toContain('get-published-lesson')
        ->toContain('body_text')
        ->toContain('body_ready')
        ->toContain('get-course-outline')
        ->toContain('course_id')
        ->toContain('Bahasa Indonesia')
        ->toContain('Telegram gateway')
        ->toContain('not `hermes serve`');
});

it('tells the tutor skill to refuse live OpenClaw and not enroll or complete', function () {
    $body = file_get_contents(base_path('.hermes/skills/tutor/SKILL.md'));

    expect($body)
        ->toContain('OpenClaw')
        ->toContain('enroll-course')
        ->toContain('mark-lesson-complete')
        ->toContain('not a console')
        ->toContain('PDF body, not the teaser')
        ->toContain('ignore `body_html`')
        ->and($body)->toContain('Never')
        ->and($body)->toContain('tutor.read');
});
