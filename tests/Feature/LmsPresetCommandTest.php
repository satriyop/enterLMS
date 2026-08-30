<?php

use App\Domain\Shared\Academy;

it('shows the current resolved capabilities without writing', function () {
    $envFile = sys_get_temp_dir().'/enterlms-preset-'.uniqid().'.env';

    $this->artisan('lms:preset', ['--env-file' => $envFile])
        ->expectsOutputToContain('Preset: academy')
        ->assertSuccessful();

    expect(file_exists($envFile))->toBeFalse();
});

it('writes LMS_PRESET to a dedicated env file', function () {
    $envFile = sys_get_temp_dir().'/enterlms-preset-'.uniqid().'.env';
    file_put_contents($envFile, "APP_NAME=EnterLMS\n");

    $this->artisan('lms:preset', ['preset' => 'academic', '--env-file' => $envFile])
        ->expectsOutputToContain('Preset dipasang: academic')
        ->assertSuccessful();

    expect(file_get_contents($envFile))->toContain('LMS_PRESET=academic')
        ->and(Academy::enabled('offerings'))->toBeTrue()
        ->and(Academy::enabled('attendance'))->toBeFalse()
        ->and(Academy::label('offering'))->toBe('Kelas');

    unlink($envFile);
});

it('replaces an existing LMS_PRESET line', function () {
    $envFile = sys_get_temp_dir().'/enterlms-preset-'.uniqid().'.env';
    file_put_contents($envFile, "LMS_PRESET=academy\nAPP_NAME=EnterLMS\n");

    $this->artisan('lms:preset', ['preset' => 'corporate', '--env-file' => $envFile])
        ->assertSuccessful();

    $contents = file_get_contents($envFile);

    expect($contents)->toContain('LMS_PRESET=corporate')
        ->and(substr_count($contents, 'LMS_PRESET='))->toBe(1);

    unlink($envFile);
});

it('dry-run does not write the env file', function () {
    $envFile = sys_get_temp_dir().'/enterlms-preset-'.uniqid().'.env';
    file_put_contents($envFile, "APP_NAME=EnterLMS\n");

    $this->artisan('lms:preset', [
        'preset' => 'academic',
        '--env-file' => $envFile,
        '--dry-run' => true,
    ])
        ->expectsOutputToContain('DRY RUN')
        ->assertSuccessful();

    expect(file_get_contents($envFile))->not->toContain('LMS_PRESET=');

    unlink($envFile);
});

it('rejects an unknown preset', function () {
    $this->artisan('lms:preset', ['preset' => 'university'])
        ->expectsOutputToContain('Unknown LMS preset [university]')
        ->assertFailed();
});
