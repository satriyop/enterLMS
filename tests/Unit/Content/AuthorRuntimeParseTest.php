<?php

use App\Domain\Content\Services\AuthorRuntime;

it('parses a JSON Author Agent reply into reason and body_text', function () {
    $runtime = new AuthorRuntime;

    $parsed = $runtime->parse('{"reason":"Memisahkan istilah.","body_text":"Chatbot menjawab. Agen memakai alat."}');

    expect($parsed['reason'])->toBe('Memisahkan istilah.')
        ->and($parsed['body_text'])->toBe('Chatbot menjawab. Agen memakai alat.');
});

it('parses a fenced JSON reply', function () {
    $runtime = new AuthorRuntime;

    $parsed = $runtime->parse(<<<'TXT'
```json
{"reason":"Lebih jelas.","body_text":"Agen menerima tujuan."}
```
TXT);

    expect($parsed['body_text'])->toBe('Agen menerima tujuan.')
        ->and($parsed['reason'])->toBe('Lebih jelas.');
});

it('rejects a reply without body_text', function () {
    $runtime = new AuthorRuntime;

    $runtime->parse('{"reason":"Kosong."}');
})->throws(RuntimeException::class, 'Author runtime failed.');
