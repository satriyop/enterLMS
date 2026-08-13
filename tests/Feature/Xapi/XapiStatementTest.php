<?php

use App\Models\User;
use App\Models\XapiStatement;

// --- Store Statements ---

it('stores an xAPI statement via API', function () {
    $user = User::factory()->create(['role' => 'learner']);

    $response = $this->actingAs($user)
        ->postJson(route('api.xapi.statements.store'), [
            'verb_id' => 'http://adlnet.gov/expapi/verbs/completed',
            'verb_display' => 'completed',
            'object_id' => 'http://enterlms.test/activities/lesson/1',
            'object_name' => 'Pengenalan AML',
            'actor_id' => $user->id,
            'result_completion' => true,
            'source' => 'native',
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.verb.id', 'http://adlnet.gov/expapi/verbs/completed')
        ->assertJsonPath('data.verb.display.en-US', 'completed')
        ->assertJsonPath('data.object.id', 'http://enterlms.test/activities/lesson/1');

    $this->assertDatabaseHas('xapi_statements', [
        'verb_id' => 'http://adlnet.gov/expapi/verbs/completed',
        'object_id' => 'http://enterlms.test/activities/lesson/1',
        'actor_id' => $user->id,
        'source' => 'native',
    ]);
});

it('auto-resolves actor info from user ID', function () {
    $user = User::factory()->create([
        'role' => 'learner',
        'name' => 'Budi Santoso',
        'email' => 'budi@enterlms.test',
    ]);

    $this->actingAs($user)
        ->postJson(route('api.xapi.statements.store'), [
            'verb_id' => 'http://adlnet.gov/expapi/verbs/launched',
            'verb_display' => 'launched',
            'object_id' => 'http://enterlms.test/activities/course/1',
            'actor_id' => $user->id,
        ])->assertCreated();

    $statement = XapiStatement::latest('id')->first();

    expect($statement)
        ->actor_name->toBe('Budi Santoso')
        ->actor_mbox->toBe('mailto:budi@enterlms.test');
});

it('stores a statement with score results', function () {
    $user = User::factory()->create(['role' => 'learner']);

    $this->actingAs($user)
        ->postJson(route('api.xapi.statements.store'), [
            'verb_id' => 'http://adlnet.gov/expapi/verbs/scored',
            'verb_display' => 'scored',
            'object_id' => 'http://enterlms.test/activities/assessment/5',
            'object_name' => 'Quiz Keamanan Siber',
            'actor_id' => $user->id,
            'result_score_raw' => 85.0,
            'result_score_min' => 0,
            'result_score_max' => 100,
            'result_score_scaled' => 0.85,
            'result_success' => true,
        ])->assertCreated();

    $statement = XapiStatement::latest('id')->first();

    expect($statement)
        ->result_score_raw->toBe('85.00')
        ->result_success->toBeTrue();
});

it('generates unique statement IDs', function () {
    $user = User::factory()->create(['role' => 'learner']);

    $payload = [
        'verb_id' => 'http://adlnet.gov/expapi/verbs/experienced',
        'verb_display' => 'experienced',
        'object_id' => 'http://enterlms.test/activities/lesson/1',
    ];

    $this->actingAs($user)
        ->postJson(route('api.xapi.statements.store'), $payload)->assertCreated();
    $this->actingAs($user)
        ->postJson(route('api.xapi.statements.store'), $payload)->assertCreated();

    $ids = XapiStatement::pluck('statement_id')->toArray();
    expect($ids)->toHaveCount(2);
    expect($ids[0])->not->toBe($ids[1]);
});

// --- Validation ---

it('rejects unauthenticated requests', function () {
    $this->postJson(route('api.xapi.statements.store'), [
        'verb_id' => 'http://adlnet.gov/expapi/verbs/completed',
        'verb_display' => 'completed',
        'object_id' => 'http://enterlms.test/activities/lesson/1',
    ])->assertUnauthorized();
});

it('requires verb_id and object_id', function () {
    $user = User::factory()->create(['role' => 'learner']);

    $this->actingAs($user)
        ->postJson(route('api.xapi.statements.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['verb_id', 'verb_display', 'object_id']);
});

it('rejects invalid verb IRI', function () {
    $user = User::factory()->create(['role' => 'learner']);

    $this->actingAs($user)
        ->postJson(route('api.xapi.statements.store'), [
            'verb_id' => 'not-a-valid-url',
            'verb_display' => 'completed',
            'object_id' => 'http://enterlms.test/activities/lesson/1',
        ])->assertUnprocessable()
        ->assertJsonValidationErrors('verb_id');
});

it('rejects invalid source value', function () {
    $user = User::factory()->create(['role' => 'learner']);

    $this->actingAs($user)
        ->postJson(route('api.xapi.statements.store'), [
            'verb_id' => 'http://adlnet.gov/expapi/verbs/completed',
            'verb_display' => 'completed',
            'object_id' => 'http://enterlms.test/activities/lesson/1',
            'source' => 'invalid_source',
        ])->assertUnprocessable()
        ->assertJsonValidationErrors('source');
});

// --- Query / Index ---

it('lists xAPI statements', function () {
    $user = User::factory()->create(['role' => 'lms_admin']);

    XapiStatement::factory()->count(5)->create(['actor_id' => $user->id]);

    $this->actingAs($user)
        ->getJson(route('api.xapi.statements.index'))
        ->assertOk()
        ->assertJsonCount(5, 'statements');
});

it('filters statements by verb', function () {
    $user = User::factory()->create(['role' => 'lms_admin']);

    XapiStatement::factory()->completed()->count(3)->create();
    XapiStatement::factory()->scored()->count(2)->create();

    $response = $this->actingAs($user)
        ->getJson(route('api.xapi.statements.index', [
            'verb' => 'http://adlnet.gov/expapi/verbs/completed',
        ]));

    $response->assertOk()->assertJsonCount(3, 'statements');
});

it('filters statements by source', function () {
    $user = User::factory()->create(['role' => 'lms_admin']);

    XapiStatement::factory()->count(3)->create(['source' => 'native']);
    XapiStatement::factory()->fromScorm()->count(2)->create();

    $response = $this->actingAs($user)
        ->getJson(route('api.xapi.statements.index', [
            'source' => 'scorm',
        ]));

    $response->assertOk()->assertJsonCount(2, 'statements');
});

it('filters statements by course context', function () {
    $user = User::factory()->create(['role' => 'lms_admin']);

    XapiStatement::factory()->count(3)->create(['context_course_id' => 10]);
    XapiStatement::factory()->count(2)->create(['context_course_id' => 20]);

    $response = $this->actingAs($user)
        ->getJson(route('api.xapi.statements.index', [
            'course_id' => 10,
        ]));

    $response->assertOk()->assertJsonCount(3, 'statements');
});

it('limits query results', function () {
    $user = User::factory()->create(['role' => 'lms_admin']);

    XapiStatement::factory()->count(10)->create();

    $response = $this->actingAs($user)
        ->getJson(route('api.xapi.statements.index', [
            'limit' => 3,
        ]));

    $response->assertOk()->assertJsonCount(3, 'statements');
});
