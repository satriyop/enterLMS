<?php

use App\Domain\Tutor\Services\TutorRuntime;
use App\Models\Conversation;
use App\Models\ConversationTurn;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

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

beforeEach(function () {
    config()->set('tutor.runtime_url', '');
    config()->set('tutor.hermes_profile', '');
});

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
        ->not->toContain('-p')
        ->not->toContain('gateway')
        ->not->toContain('serve');

    $courseId = $conversation->enrollment()->value('course_id');

    expect($stdin)->toContain('conversation_id: '.$conversation->id)
        ->toContain('user_id: '.(string) $conversation->enrollment->user_id)
        ->toContain('course_id: '.$courseId)
        ->toContain('lesson_id: '.$conversation->lesson_id)
        ->toContain('Call get-published-lesson with this user_id, course_id, and lesson_id.')
        ->toContain('Learner: Apa bedanya agen dengan chatbot?')
        ->toContain('Pertanyaan lama');

    @unlink($binary);
    @unlink($argvPath);
    @unlink($stdinPath);
});

it('passes -p when a Hermes profile is configured', function () {
    $argvPath = sys_get_temp_dir().'/enterlms-hermes-profile-argv-'.uniqid('', true).'.txt';

    $binary = writeFakeHermes(<<<SH
#!/bin/sh
printf '%s\\n' "\$@" > {$argvPath}
cat >/dev/null
echo "Tutor reply from runtime."
SH);

    config()->set('tutor.hermes_binary', $binary);
    config()->set('tutor.hermes_profile', 'enterlms-tutor');

    (new TutorRuntime)->completeTurn(tutorRuntimeConversation(), 'Halo');

    $argv = trim((string) file_get_contents($argvPath));

    expect($argv)->toContain("-p\nenterlms-tutor\nchat")
        ->toContain('--skills')
        ->toContain('tutor');

    @unlink($binary);
    @unlink($argvPath);
});

it('runs Hermes from a Laravel-built prompt without loading Conversation on the runtime host', function () {
    $argvPath = sys_get_temp_dir().'/enterlms-hermes-prompt-argv-'.uniqid('', true).'.txt';
    $stdinPath = sys_get_temp_dir().'/enterlms-hermes-prompt-stdin-'.uniqid('', true).'.txt';

    $binary = writeFakeHermes(<<<SH
#!/bin/sh
printf '%s\\n' "\$@" > {$argvPath}
cat > {$stdinPath}
echo "Tutor reply from runtime."
SH);

    config()->set('tutor.hermes_binary', $binary);
    config()->set('tutor.hermes_profile', 'enterlms-tutor');

    $reply = (new TutorRuntime)->completeTurnFromPrompt(
        'enterlms-conversation-9',
        "Course id: 9\nLearner: Halo",
    );

    expect($reply)->toBe('Tutor reply from runtime.')
        ->and(trim((string) file_get_contents($argvPath)))->toContain('enterlms-conversation-9')
        ->and((string) file_get_contents($stdinPath))->toContain('Learner: Halo');

    @unlink($binary);
    @unlink($argvPath);
    @unlink($stdinPath);
});

it('rejects a Hermes profile name that is not a profile id', function () {
    $binary = writeFakeHermes(<<<'SH'
#!/bin/sh
echo "should not run"
SH);

    config()->set('tutor.hermes_binary', $binary);
    config()->set('tutor.hermes_profile', '-Q');

    expect(fn () => (new TutorRuntime)->completeTurn(tutorRuntimeConversation(), 'Halo'))
        ->toThrow(RuntimeException::class, 'Tutor runtime is not configured.');

    @unlink($binary);
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

it('raises the PHP time limit so FPM can wait for Hermes', function () {
    Http::fake([
        'http://127.0.0.1:8642/v1/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'ok']]],
        ], 200),
    ]);

    config()->set('tutor.runtime_url', 'http://127.0.0.1:8642');
    config()->set('tutor.runtime_api_key', 'secret-test');
    config()->set('tutor.timeout_seconds', 90);

    (new TutorRuntime)->completeTurn(tutorRuntimeConversation(), 'Halo');

    expect((int) ini_get('max_execution_time'))->toBeGreaterThanOrEqual(90);

    set_time_limit(0);
});

it('turns a sidecar timeout into a runtime failure', function () {
    Http::fake(function () {
        throw new ConnectionException('cURL error 28: Operation timed out');
    });

    config()->set('tutor.runtime_url', 'http://127.0.0.1:8642');
    config()->set('tutor.runtime_api_key', 'secret-test');

    expect(fn () => (new TutorRuntime)->completeTurn(tutorRuntimeConversation(), 'Halo'))
        ->toThrow(RuntimeException::class, 'Tutor runtime failed.');
});

it('posts Conversation history as chat completions messages when runtime_url is set', function () {
    Http::fake([
        'http://127.0.0.1:8642/v1/chat/completions' => Http::response([
            'choices' => [['message' => ['role' => 'assistant', 'content' => 'Jawaban dari API.']]],
        ], 200),
    ]);

    config()->set('tutor.runtime_url', 'http://127.0.0.1:8642');
    config()->set('tutor.runtime_api_key', 'secret-test');
    config()->set('tutor.model', 'hermes');
    config()->set('tutor.hermes_binary', '');

    $conversation = tutorRuntimeConversation();
    $reply = (new TutorRuntime)->completeTurn($conversation, 'Halo');

    expect($reply)->toBe('Jawaban dari API.');

    Http::assertSent(function ($request) use ($conversation) {
        $messages = $request['messages'] ?? [];
        $roles = array_column($messages, 'role');
        $contents = array_column($messages, 'content');

        return $request->url() === 'http://127.0.0.1:8642/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer secret-test')
            && $request['model'] === 'hermes'
            && $roles === ['system', 'user', 'user']
            && str_contains((string) $contents[0], 'user_id: '.(string) $conversation->enrollment->user_id)
            && str_contains((string) $contents[0], 'course_id: '.(string) $conversation->enrollment->course_id)
            && str_contains((string) $contents[0], 'lesson_id: '.$conversation->lesson_id)
            && str_contains((string) $contents[0], 'Call get-published-lesson with this user_id, course_id, and lesson_id.')
            && $contents[1] === 'Pertanyaan lama'
            && $contents[2] === 'Halo'
            && ! array_key_exists('prompt', $request->data())
            && ! array_key_exists('session', $request->data());
    });
});

it('puts academy-read body_text on chat completions so overlay does not wait for MCP user_id', function () {
    Http::fake([
        'http://127.0.0.1:8642/v1/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'Agen berbeda dari chatbot.']]],
        ], 200),
    ]);

    config()->set('tutor.runtime_url', 'http://127.0.0.1:8642');
    config()->set('tutor.runtime_api_key', 'secret-test');
    config()->set('tutor.hermes_binary', '');

    $conversation = tutorRuntimeConversation();
    $body = 'Agen menerima tujuan dan memakai alat.';

    (new TutorRuntime)->completeTurn($conversation, 'Apa bedanya agen dengan chatbot?', $body);

    Http::assertSent(function ($request) use ($conversation, $body) {
        $system = (string) ($request['messages'][0]['content'] ?? '');

        return str_contains($system, 'user_id: '.(string) $conversation->enrollment->user_id)
            && str_contains($system, 'course_id: '.(string) $conversation->enrollment->course_id)
            && str_contains($system, 'lesson_id: '.$conversation->lesson_id)
            && str_contains($system, 'body_text:')
            && str_contains($system, $body)
            && ! str_contains($system, 'Call get-published-lesson');
    });
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
