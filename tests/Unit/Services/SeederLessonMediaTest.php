<?php

use App\Services\SeederLessonMedia;

it('generates a multi-page PDF that starts with a PDF header', function () {
    $pdf = app(SeederLessonMedia::class)->pdf('Glosarium Agen', [
        str_repeat('Agen menerima tujuan, memakai alat, dan bekerja dalam loop. ', 20),
        str_repeat('EnterLMS adalah academy, bukan control plane. ', 20),
    ]);

    expect($pdf)->toStartWith('%PDF-1.4');
    expect($pdf)->toContain('%%EOF');
    expect(substr_count($pdf, '/Type /Page'))->toBeGreaterThanOrEqual(2);
});
