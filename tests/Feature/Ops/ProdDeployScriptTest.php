<?php

use Illuminate\Support\Facades\Process;

function gitGateRepo(): string
{
    $dir = sys_get_temp_dir().'/enterlms-git-gate-'.bin2hex(random_bytes(4));
    mkdir($dir);

    Process::path($dir)->run(['git', 'init', '-b', 'main'])->throw();
    Process::path($dir)->run(['git', 'config', 'user.email', 'ops@example.com'])->throw();
    Process::path($dir)->run(['git', 'config', 'user.name', 'Ops Test'])->throw();
    file_put_contents($dir.'/README', "init\n");
    Process::path($dir)->run(['git', 'add', 'README'])->throw();
    Process::path($dir)->run(['git', 'commit', '-m', 'init'])->throw();

    return $dir;
}

function runGitGate(string $function, string $dir): object
{
    $lib = base_path('scripts/prod/lib.sh');

    return Process::timeout(20)->run([
        'bash',
        '-lc',
        'source '.escapeshellarg($lib).' && '.$function.' '.escapeshellarg($dir),
    ]);
}

it('parses the deploy scripts', function (string $script) {
    $result = Process::timeout(10)->run(['bash', '-n', base_path($script)]);

    expect($result->exitCode())->toBe(0, $result->errorOutput());
})->with([
    'scripts/prod.sh',
    'scripts/prod/lib.sh',
    'scripts/prod/deploy.sh',
    'scripts/prod/health.sh',
    'scripts/prod/seed-academy.sh',
]);

it('does not print the local demo password as a production login', function () {
    $script = file_get_contents(base_path('scripts/prod/seed-academy.sh'));

    expect($script)
        ->toContain('Rotate every seeded user password')
        ->not->toContain('Login: admin@enterlms.test / password')
        ->not->toContain('password=password');
});

it('refuses deploy when the git tree is dirty', function () {
    $dir = gitGateRepo();
    file_put_contents($dir.'/README', "dirty\n");

    $result = runGitGate('require_clean_git', $dir);

    expect($result->exitCode())->not->toBe(0)
        ->and($result->output().$result->errorOutput())->toContain('working tree is dirty');
});

it('allows deploy when the git tree is clean', function () {
    $dir = gitGateRepo();

    $result = runGitGate('require_clean_git', $dir);

    expect($result->exitCode())->toBe(0, $result->output().$result->errorOutput());
});

it('refuses deploy when the branch has no upstream', function () {
    $dir = gitGateRepo();

    $result = runGitGate('require_git_upstream', $dir);

    expect($result->exitCode())->not->toBe(0)
        ->and($result->output().$result->errorOutput())->toContain('no upstream');
});

it('pushes HEAD before rsync and records REVISION on the server', function () {
    $deploy = file_get_contents(base_path('scripts/prod/deploy.sh'));

    expect($deploy)
        ->toContain('require_clean_git')
        ->toContain('push_release_git')
        ->toContain('write_release_revision')
        ->toContain('rsync');

    $exclude = file_get_contents(base_path('scripts/prod/rsync-exclude.txt'));
    expect($exclude)->toContain('REVISION');
});
