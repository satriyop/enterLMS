<?php

use App\Domain\Shared\Academy;
use App\Domain\Shared\AcademyPresetCatalog;

describe('AcademyPresetCatalog', function () {
    it('declares the same feature keys on every preset', function () {
        $bundles = AcademyPresetCatalog::bundles();
        $names = AcademyPresetCatalog::names();

        expect($names)->toBe(['academy', 'academic', 'corporate']);

        $keys = array_keys($bundles['academy']['features']);

        foreach ($bundles as $preset => $bundle) {
            expect(array_keys($bundle['features']))->toBe($keys, "preset [{$preset}] feature keys");
            expect(array_keys($bundle['labels']))->toBe(['offering', 'facilitator', 'learner']);
            expect($bundle['identity'])->toHaveKeys(['scheme', 'label']);
        }
    });

    it('rejects an unknown preset', function () {
        AcademyPresetCatalog::resolve('university');
    })->throws(InvalidArgumentException::class, 'Unknown LMS preset [university]');

    it('rejects an unknown feature override', function () {
        AcademyPresetCatalog::resolve('academy', ['forum' => true]);
    })->throws(InvalidArgumentException::class, 'Unknown academy feature [forum]');

    it('null feature overrides leave the preset default', function () {
        $resolved = AcademyPresetCatalog::resolve('academic', ['attendance' => null]);

        expect($resolved['features']['attendance'])->toBeFalse();
    });

    it('applies a school identity on the academic preset without a new preset name', function () {
        $resolved = AcademyPresetCatalog::resolve(
            'academic',
            [],
            ['facilitator' => 'Guru', 'learner' => 'Siswa'],
            ['scheme' => 'nisn', 'label' => 'NISN'],
        );

        expect($resolved['preset'])->toBe('academic')
            ->and($resolved['labels']['offering'])->toBe('Kelas')
            ->and($resolved['labels']['facilitator'])->toBe('Guru')
            ->and($resolved['labels']['learner'])->toBe('Siswa')
            ->and($resolved['identity']['scheme'])->toBe('nisn')
            ->and($resolved['identity']['label'])->toBe('NISN')
            ->and($resolved['features']['offerings'])->toBeTrue()
            ->and($resolved['features']['facilitators'])->toBeTrue()
            ->and($resolved['features']['attendance'])->toBeFalse();
    });
});

describe('Academy', function () {
    it('boots as the academy preset with market capabilities off', function () {
        expect(Academy::preset())->toBe('academy')
            ->and(Academy::enabled('offerings'))->toBeFalse()
            ->and(Academy::enabled('facilitators'))->toBeFalse()
            ->and(Academy::enabled('attendance'))->toBeFalse()
            ->and(Academy::enabled('letter_grades'))->toBeFalse()
            ->and(Academy::enabled('academic_calendar'))->toBeFalse()
            ->and(Academy::enabled('sso'))->toBeFalse()
            ->and(Academy::identityScheme())->toBe('email')
            ->and(Academy::label('learner'))->toBe('Learner');
    });

    it('turns academic capabilities on without the caller naming the preset in a feature check', function () {
        Academy::using('academic');

        expect(Academy::enabled('offerings'))->toBeTrue()
            ->and(Academy::enabled('facilitators'))->toBeTrue()
            ->and(Academy::enabled('attendance'))->toBeFalse()
            ->and(Academy::enabled('letter_grades'))->toBeFalse()
            ->and(Academy::enabled('academic_calendar'))->toBeFalse()
            ->and(Academy::enabled('sso'))->toBeFalse()
            ->and(Academy::label('offering'))->toBe('Kelas')
            ->and(Academy::label('facilitator'))->toBe('Dosen')
            ->and(Academy::identityScheme())->toBe('nim');
    });

    it('turns corporate capabilities on with completion-shaped evidence defaults', function () {
        Academy::using('corporate');

        expect(Academy::enabled('offerings'))->toBeTrue()
            ->and(Academy::enabled('facilitators'))->toBeTrue()
            ->and(Academy::enabled('sso'))->toBeTrue()
            ->and(Academy::enabled('attendance'))->toBeFalse()
            ->and(Academy::enabled('letter_grades'))->toBeFalse()
            ->and(Academy::enabled('academic_calendar'))->toBeFalse()
            ->and(Academy::label('offering'))->toBe('Batch')
            ->and(Academy::label('facilitator'))->toBe('PIC')
            ->and(Academy::identityScheme())->toBe('employee_id');
    });

    it('overrides a single capability without switching preset', function () {
        Academy::using('academic', ['attendance' => true]);

        expect(Academy::enabled('attendance'))->toBeTrue()
            ->and(Academy::enabled('letter_grades'))->toBeFalse()
            ->and(Academy::label('offering'))->toBe('Kelas');
    });

    it('throws on an unknown feature name so typos cannot silently disable', function () {
        Academy::enabled('forum');
    })->throws(InvalidArgumentException::class, 'Unknown academy feature [forum]');

    it('omits the preset name from the Inertia payload', function () {
        Academy::using('academic');

        $payload = Academy::toInertia();

        expect($payload)->not->toHaveKey('preset')
            ->and($payload['features']['offerings'])->toBeTrue()
            ->and($payload['features']['attendance'])->toBeFalse()
            ->and($payload['labels']['offering'])->toBe('Kelas')
            ->and($payload['identity']['scheme'])->toBe('nim');
    });
});
