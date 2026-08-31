<?php

it('ships an author skill for the Author Agent on enterlms-author', function () {
    $path = base_path('.hermes/skills/author/SKILL.md');

    expect($path)->toBeFile();

    $body = file_get_contents($path);
    expect($body)->toBeString();

    expect($body)
        ->toContain('name: author')
        ->toContain('enterlms-author')
        ->toContain('author.read')
        ->toContain('--author-read')
        ->toContain('get-author-lesson')
        ->toContain('propose-content')
        ->toContain('proposal_id')
        ->toContain('body_text')
        ->toContain('Content Proposal')
        ->toContain('Bahasa Indonesia')
        ->toContain('Never');
});

it('tells the author skill to refuse Tutor and LMS Agent doors', function () {
    $body = file_get_contents(base_path('.hermes/skills/author/SKILL.md'));

    expect($body)
        ->toContain('tutor.read')
        ->toContain('--free-flow')
        ->toContain('enroll-course')
        ->toContain('mark-lesson-complete')
        ->toContain('commit-turn')
        ->toContain('get-published-lesson')
        ->toContain('enterlms-tutor')
        ->toContain('--tutor-read');
});
