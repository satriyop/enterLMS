<?php

use App\Domain\Tutor\Services\TutorRuntime;
use App\Models\Conversation;
use App\Models\ConversationTurn;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;

function tutorRuntimeConversation(): Conversation
{
    $course = Course::factory()->published()->public()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->text()->create([
        'course_section_id' => $section->id,
        'title' => 'Apa itu agen',
        'description' => 'JANGAN_KUTIP_STUB_INI',
        'rich_content' => [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => 'JANGAN_KUTIP_STUB_INI']],
            ]],
        ],
    ]);
    $user = User::factory()->learner()->create();
    $enrollment = Enrollment::factory()->active()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
    ]);

    $conversation = Conversation::query()->create([
        'enrollment_id' => $enrollment->id,
        'lesson_id' => $lesson->id,
    ]);

    ConversationTurn::query()->create([
        'conversation_id' => $conversation->id,
        'role' => ConversationTurn::ROLE_LEARNER,
        'body' => 'Pertanyaan lama',
    ]);

    return $conversation->fresh(['turns']);
}

function writeFakeHermes(string $script): string
{
    $path = sys_get_temp_dir().'/enterlms-fake-hermes-'.uniqid('', true);
    file_put_contents($path, $script);
    chmod($path, 0755);

    return $path;
}

it('returns stdout from the configured hermes binary and does not excerpt the Lesson stub', function () {
    $binary = writeFakeHermes(<<<'SH'
#!/bin/sh
echo "Tutor reply from runtime."
SH);

    config()->set('tutor.hermes_binary', $binary);

    $reply = (new TutorRuntime)->completeTurn(tutorRuntimeConversation(), 'Apa bedanya agen dengan chatbot?');

    expect($reply)->toBe('Tutor reply from runtime.')
        ->and($reply)->not->toContain('JANGAN_KUTIP_STUB_INI');

    @unlink($binary);
});

it('passes chat -Q, tutor skill, and Conversation id to hermes', function () {
    $argvPath = sys_get_temp_dir().'/enterlms-hermes-argv-'.uniqid('', true).'.txt';
    $stdinPath = sys_get_temp_dir().'/enterlms-hermes-stdin-'.uniqid('', true).'.txt';

    $binary = writeFakeHermes(<<<SH
#!/bin/sh
printf '%s\\n' "\$@" > {$argvPath}
cat > {$stdinPath}
echo "Tutor reply from runtime."
SH);

    config()->set('tutor.hermes_binary', $binary);
    config()->set('tutor.skill', 'tutor');

    $conversation = tutorRuntimeConversation();
    (new TutorRuntime)->completeTurn($conversation, 'Apa bedanya agen dengan chatbot?');

    $argv = trim((string) file_get_contents($argvPath));
    $stdin = (string) file_get_contents($stdinPath);

    expect($argv)->toContain('chat')
        ->toContain('-Q')
        ->toContain('--skills')
        ->toContain('tutor')
        ->toContain('--continue')
        ->toContain('enterlms-conversation-'.$conversation->id)
        ->not->toContain('gateway')
        ->not->toContain('serve');

    $courseId = $conversation->enrollment()->value('course_id');

    expect($stdin)->toContain('Conversation id: '.$conversation->id)
        ->toContain('Course id: '.$courseId)
        ->toContain('Lesson id: '.$conversation->lesson_id)
        ->toContain('Call get-published-lesson and get-course-outline with this course_id.')
        ->toContain('Learner: Apa bedanya agen dengan chatbot?')
        ->toContain('Pertanyaan lama');

    @unlink($binary);
    @unlink($argvPath);
    @unlink($stdinPath);
});

it('strips Hermes session chrome so the Learner never sees the runtime', function () {
    $binary = writeFakeHermes(<<<'SH'
#!/bin/sh
printf '%s\n' "Berdasarkan Lesson ini: agen berbeda dari chatbot."
printf '%s\n' ""
printf '%s\n' "Session 20260829_x found but has no messages. Starting fresh."
printf '%s\n' ""
printf '%s\n' "session_id: 20260829_x"
SH);

    config()->set('tutor.hermes_binary', $binary);

    $reply = (new TutorRuntime)->completeTurn(tutorRuntimeConversation(), 'Halo');

    expect($reply)->toBe('Berdasarkan Lesson ini: agen berbeda dari chatbot.')
        ->and($reply)->not->toContain('session_id')
        ->and($reply)->not->toContain('Hermes');

    @unlink($binary);
});

it('keeps a pedagogical reply that names OpenAI and does not treat it as a failed runtime', function () {
    $binary = writeFakeHermes(<<<'SH'
#!/bin/sh
echo "Agen AI bukan ChatGPT atau OpenAI; academy ini bukan control plane."
SH);

    config()->set('tutor.hermes_binary', $binary);

    $reply = (new TutorRuntime)->completeTurn(tutorRuntimeConversation(), 'Apa bedanya dengan OpenAI?');

    expect($reply)->toContain('OpenAI')
        ->and($reply)->toContain('bukan control plane');

    @unlink($binary);
});

it('throws when Hermes stdout is a traceback', function () {
    $binary = writeFakeHermes(<<<'SH'
#!/bin/sh
echo "Traceback (most recent call last):"
echo "  File hermes"
exit 0
SH);

    config()->set('tutor.hermes_binary', $binary);

    expect(fn () => (new TutorRuntime)->completeTurn(tutorRuntimeConversation(), 'Halo'))
        ->toThrow(RuntimeException::class, 'Tutor runtime failed.');

    @unlink($binary);
});

it('throws when the hermes binary is missing', function () {
    config()->set('tutor.hermes_binary', '');

    expect(fn () => (new TutorRuntime)->completeTurn(tutorRuntimeConversation(), 'Halo'))
        ->toThrow(RuntimeException::class, 'Tutor runtime is not configured.');
});

it('throws when the hermes process fails', function () {
    $binary = writeFakeHermes(<<<'SH'
#!/bin/sh
echo "runtime down" >&2
exit 1
SH);

    config()->set('tutor.hermes_binary', $binary);

    expect(fn () => (new TutorRuntime)->completeTurn(tutorRuntimeConversation(), 'Halo'))
        ->toThrow(RuntimeException::class, 'Tutor runtime failed.');

    @unlink($binary);
});
