<?php

it('ships a tutor skill for overlay and Telegram on enterlms-tutor', function () {
    $path = base_path('.hermes/skills/tutor/SKILL.md');

    expect($path)->toBeFile();

    $body = file_get_contents($path);
    expect($body)->toBeString();

    expect($body)
        ->toContain('name: tutor')
        ->toContain('hermes -p enterlms-tutor chat')
        ->toContain('-s tutor')
        ->toContain('tutor.read')
        ->toContain('--tutor-read')
        ->toContain('enterlms-conversation-')
        ->toContain('get-published-lesson')
        ->toContain('body_text')
        ->toContain('body_ready')
        ->toContain('get-course-outline')
        ->toContain('course_id')
        ->toContain('user_id')
        ->toContain('resolve')
        ->toContain('get-focus')
        ->toContain('set-focus')
        ->toContain('list-focusable-lessons')
        ->toContain('must_pick')
        ->toContain('commit-turn')
        ->toContain('Bahasa Indonesia')
        ->toContain('Lesson URL')
        ->toContain('Laravel holds the')
        ->toContain('lsptdi-ops')
        ->toContain('not `hermes serve`');
});

it('tells the tutor skill to refuse live OpenClaw and not enroll or complete', function () {
    $body = file_get_contents(base_path('.hermes/skills/tutor/SKILL.md'));

    expect($body)
        ->toContain('OpenClaw')
        ->toContain('enroll-course')
        ->toContain('mark-lesson-complete')
        ->toContain('--free-flow')
        ->toContain('not a console')
        ->toContain('PDF body, not the teaser')
        ->toContain('ignore `body_html`')
        ->toContain('Do not paste the whole body')
        ->toContain('skill_manage')
        ->and($body)->toContain('Never')
        ->and($body)->toContain('tutor.read');
});

it('requires Telegram resolve then commit-turn before any reply', function () {
    $body = file_get_contents(base_path('.hermes/skills/tutor/SKILL.md'));

    expect($body)->toBeString();

    expect($body)
        ->toContain('Telegram')
        ->toContain('resolve')
        ->toContain('get-focus')
        ->toContain('Do not send a Telegram reply unless `commit-turn` succeeds.')
        ->toContain('never the display name');

    $overlayPos = strpos($body, '## Overlay');
    $messagingPos = strpos($body, '## Messaging (Telegram)');
    $resolvePos = strpos($body, '`resolve` `channel=telegram`');
    $commitPos = strpos($body, '`commit-turn` with `learner_message` then `tutor_message`');

    expect($overlayPos)->toBeInt();
    expect($messagingPos)->toBeInt()->toBeGreaterThan($overlayPos);
    expect($resolvePos)->toBeInt()->toBeGreaterThan($messagingPos);
    expect($commitPos)->toBeInt()->toBeGreaterThan($resolvePos);
});
