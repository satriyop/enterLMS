<?php

use App\Domain\Agent\Abilities\AgentAbility;
use App\Mcp\Servers\EnterLmsAgentServer;
use App\Mcp\Tools\Tutor\CommitTurnTool;
use App\Mcp\Tools\Tutor\GetPublishedLessonTool;
use App\Mcp\Tools\Tutor\ResolveChannelTool;
use App\Models\ChannelIdentity;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('resolves a linked WhatsApp phone to user_id with the runtime Bearer', function () {
    $runtime = User::factory()->lmsAdmin()->create();
    $learner = User::factory()->learner()->create();

    ChannelIdentity::factory()->whatsapp()->create([
        'user_id' => $learner->id,
        'identifier' => '6281234567890',
    ]);

    Sanctum::actingAs($runtime, AgentAbility::tutorRead());

    EnterLmsAgentServer::tool(ResolveChannelTool::class, [
        'channel' => 'whatsapp',
        'identifier' => '6281234567890',
    ])
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($learner) {
            $json->where('ok', true)
                ->where('data.user_id', $learner->id)
                ->where('data.channel', 'whatsapp')
                ->where('data.identifier', '6281234567890')
                ->etc();
        });
});

it('resolves a linked Telegram id to user_id', function () {
    $learner = User::factory()->learner()->create();

    ChannelIdentity::factory()->telegram()->create([
        'user_id' => $learner->id,
        'identifier' => '99887766',
    ]);

    Sanctum::actingAs(User::factory()->lmsAdmin()->create(), AgentAbility::tutorRead());

    EnterLmsAgentServer::tool(ResolveChannelTool::class, [
        'channel' => 'telegram',
        'identifier' => '99887766',
    ])
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($learner) {
            $json->where('ok', true)
                ->where('data.user_id', $learner->id)
                ->etc();
        });
});

it('errors when the channel identity is unlinked', function () {
    Sanctum::actingAs(User::factory()->lmsAdmin()->create(), AgentAbility::tutorRead());

    EnterLmsAgentServer::tool(ResolveChannelTool::class, [
        'channel' => 'whatsapp',
        'identifier' => '6280000000000',
    ])->assertSee('Identitas kanal belum tertaut ke Learner.');
});

it('refuses get-published-lesson when user_id does not match the linked channel', function () {
    $owner = User::factory()->learner()->create();
    $stranger = User::factory()->learner()->create();
    $course = Course::factory()->published()->public()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->text()->create([
        'course_section_id' => $section->id,
        'rich_content' => [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [['type' => 'text', 'text' => 'TUBUH_MISMATCH']],
            ]],
        ],
    ]);
    Enrollment::factory()->active()->create([
        'user_id' => $stranger->id,
        'course_id' => $course->id,
    ]);

    ChannelIdentity::factory()->whatsapp()->create([
        'user_id' => $owner->id,
        'identifier' => '6281111111111',
    ]);

    Sanctum::actingAs(User::factory()->lmsAdmin()->create(), AgentAbility::tutorRead());

    EnterLmsAgentServer::tool(GetPublishedLessonTool::class, [
        'user_id' => $stranger->id,
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
        'channel' => 'whatsapp',
        'identifier' => '6281111111111',
    ])
        ->assertSee('user_id tidak sesuai dengan identitas kanal ini.')
        ->assertDontSee('TUBUH_MISMATCH');
});

it('lets get-published-lesson through when user_id matches the linked channel', function () {
    $learner = User::factory()->learner()->create();
    $course = Course::factory()->published()->public()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->text()->create([
        'course_section_id' => $section->id,
        'title' => 'Apa itu agen',
    ]);
    Enrollment::factory()->active()->create([
        'user_id' => $learner->id,
        'course_id' => $course->id,
    ]);

    ChannelIdentity::factory()->whatsapp()->create([
        'user_id' => $learner->id,
        'identifier' => '6282222222222',
    ]);

    Sanctum::actingAs(User::factory()->lmsAdmin()->create(), AgentAbility::tutorRead());

    EnterLmsAgentServer::tool(GetPublishedLessonTool::class, [
        'user_id' => $learner->id,
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
        'channel' => 'whatsapp',
        'identifier' => '6282222222222',
    ])
        ->assertOk()
        ->assertStructuredContent(function ($json) use ($lesson) {
            $json->where('ok', true)
                ->where('data.lesson_id', $lesson->id)
                ->etc();
        });
});

it('refuses commit-turn when user_id does not match the linked channel', function () {
    $owner = User::factory()->learner()->create();
    $stranger = User::factory()->learner()->create();
    $course = Course::factory()->published()->public()->create();
    $section = CourseSection::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->text()->create(['course_section_id' => $section->id]);
    Enrollment::factory()->active()->create([
        'user_id' => $stranger->id,
        'course_id' => $course->id,
    ]);

    ChannelIdentity::factory()->telegram()->create([
        'user_id' => $owner->id,
        'identifier' => '12345',
    ]);

    Sanctum::actingAs(User::factory()->lmsAdmin()->create(), AgentAbility::tutorRead());

    EnterLmsAgentServer::tool(CommitTurnTool::class, [
        'user_id' => $stranger->id,
        'course_id' => $course->id,
        'lesson_id' => $lesson->id,
        'learner_message' => 'Halo',
        'tutor_message' => 'Tidak.',
        'channel' => 'telegram',
        'identifier' => '12345',
    ])->assertSee('user_id tidak sesuai dengan identitas kanal ini.');

    expect(\App\Models\ConversationTurn::query()->count())->toBe(0);
});

it('uses one runtime tutor.read Bearer rather than a per-Learner token', function () {
    $runtime = User::factory()->lmsAdmin()->create();
    $first = User::factory()->learner()->create();
    $second = User::factory()->learner()->create();

    ChannelIdentity::factory()->whatsapp()->create([
        'user_id' => $first->id,
        'identifier' => '6283333333333',
    ]);
    ChannelIdentity::factory()->whatsapp()->create([
        'user_id' => $second->id,
        'identifier' => '6284444444444',
    ]);

    Sanctum::actingAs($runtime, AgentAbility::tutorRead());

    EnterLmsAgentServer::tool(ResolveChannelTool::class, [
        'channel' => 'whatsapp',
        'identifier' => '6283333333333',
    ])->assertOk()->assertStructuredContent(fn ($json) => $json->where('data.user_id', $first->id)->etc());

    EnterLmsAgentServer::tool(ResolveChannelTool::class, [
        'channel' => 'whatsapp',
        'identifier' => '6284444444444',
    ])->assertOk()->assertStructuredContent(fn ($json) => $json->where('data.user_id', $second->id)->etc());

    expect($runtime->tokens()->count())->toBe(0);
});

it('denies resolve without tutor.read', function () {
    Sanctum::actingAs(User::factory()->learner()->create(), AgentAbility::freeFlow());

    EnterLmsAgentServer::tool(ResolveChannelTool::class, [
        'channel' => 'whatsapp',
        'identifier' => '6281234567890',
    ])->assertSee("ability 'tutor.read'");
});
