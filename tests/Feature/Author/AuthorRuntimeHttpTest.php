<?php

use App\Domain\Content\Services\AuthorRuntime;
use App\Models\ContentProposal;
use Illuminate\Support\Facades\Http;

it('posts the grounding body to the Author completions endpoint and never invents a proposal', function () {
    config()->set('author.runtime_url', 'http://author.test');
    config()->set('author.runtime_api_key', 'secret-author-key');
    config()->set('author.model', 'hermes');

    Http::fake([
        'http://author.test/v1/chat/completions' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => '{"reason":"Dari body_text.","body_text":"Chatbot menjawab. Agen memakai alat."}',
                ],
            ]],
        ], 200),
    ]);

    $proposal = ContentProposal::factory()->create([
        'instruction' => 'Perjelas bedanya chatbot dan agen.',
        'grounding_body' => 'Agen berbeda dari chatbot biasa.',
        'status' => ContentProposal::STATUS_ASKING,
    ]);

    $parsed = app(AuthorRuntime::class)->propose($proposal);

    expect($parsed['body_text'])->toBe('Chatbot menjawab. Agen memakai alat.')
        ->and($parsed['reason'])->toBe('Dari body_text.');

    Http::assertSent(function ($request) use ($proposal) {
        $payload = $request->data();

        return $request->url() === 'http://author.test/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer secret-author-key')
            && str_contains((string) data_get($payload, 'messages.0.content'), 'proposal_id: '.$proposal->id)
            && str_contains((string) data_get($payload, 'messages.0.content'), 'Agen berbeda dari chatbot biasa.')
            && data_get($payload, 'messages.1.content') === 'Perjelas bedanya chatbot dan agen.';
    });
});
