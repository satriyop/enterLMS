<?php

use App\Models\ScormPackage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Event::fake();
});

function createScormZip(string $manifestFixture = 'imsmanifest-12.xml'): UploadedFile
{
    $manifestPath = base_path("tests/fixtures/scorm/{$manifestFixture}");
    $zipPath = tempnam(sys_get_temp_dir(), 'scorm_').'.zip';

    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE);
    $zip->addFromString('imsmanifest.xml', file_get_contents($manifestPath));
    $zip->addFromString('index.html', '<html><body>SCORM Content</body></html>');
    $zip->close();

    return new UploadedFile($zipPath, 'test-package.zip', 'application/zip', null, true);
}

// --- Authorization ---

it('prevents guests from uploading SCORM packages', function () {
    $this->postJson(route('scorm.packages.store'))
        ->assertUnauthorized();
});

it('prevents learners from uploading SCORM packages', function () {
    $user = User::factory()->create(['role' => 'learner']);

    $this->actingAs($user)
        ->postJson(route('scorm.packages.store'), [
            'file' => createScormZip(),
        ])
        ->assertForbidden();
});

it('allows content managers to upload SCORM packages', function () {
    $user = User::factory()->create(['role' => 'content_manager']);

    $this->actingAs($user)
        ->postJson(route('scorm.packages.store'), [
            'file' => createScormZip(),
        ])
        ->assertCreated();
});

it('allows lms admins to upload SCORM packages', function () {
    $user = User::factory()->create(['role' => 'lms_admin']);

    $this->actingAs($user)
        ->postJson(route('scorm.packages.store'), [
            'file' => createScormZip(),
        ])
        ->assertCreated();
});

// --- Upload + Parse ---

it('uploads and parses a SCORM 1.2 package', function () {
    $user = User::factory()->create(['role' => 'content_manager']);

    $response = $this->actingAs($user)
        ->postJson(route('scorm.packages.store'), [
            'file' => createScormZip('imsmanifest-12.xml'),
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.version', '1.2')
        ->assertJsonPath('data.title', 'Modul Kepatuhan Anti Pencucian Uang')
        ->assertJsonPath('data.entry_point', 'index.html');

    $this->assertDatabaseHas('scorm_packages', [
        'version' => '1.2',
        'identifier' => 'SCORM12-TEST',
        'uploaded_by' => $user->id,
    ]);
});

it('uploads and parses a SCORM 2004 package', function () {
    $user = User::factory()->create(['role' => 'content_manager']);

    $response = $this->actingAs($user)
        ->postJson(route('scorm.packages.store'), [
            'file' => createScormZip('imsmanifest-2004.xml'),
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.version', '2004')
        ->assertJsonPath('data.title', 'Keamanan Siber untuk Perbankan')
        ->assertJsonPath('data.entry_point', 'module1/index.html');
});

it('dispatches ScormPackageUploaded event on successful upload', function () {
    $user = User::factory()->create(['role' => 'content_manager']);

    $this->actingAs($user)
        ->postJson(route('scorm.packages.store'), [
            'file' => createScormZip(),
        ])
        ->assertCreated();

    Event::assertDispatched(\App\Domain\Scorm\Events\ScormPackageUploaded::class);
});

// --- Validation ---

it('rejects non-zip files', function () {
    $user = User::factory()->create(['role' => 'content_manager']);
    $file = UploadedFile::fake()->create('not-a-zip.pdf', 100, 'application/pdf');

    $this->actingAs($user)
        ->postJson(route('scorm.packages.store'), [
            'file' => $file,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');
});

it('rejects upload without a file', function () {
    $user = User::factory()->create(['role' => 'content_manager']);

    $this->actingAs($user)
        ->postJson(route('scorm.packages.store'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');
});

it('rejects ZIP without imsmanifest.xml', function () {
    $user = User::factory()->create(['role' => 'content_manager']);

    $zipPath = tempnam(sys_get_temp_dir(), 'scorm_').'.zip';
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE);
    $zip->addFromString('index.html', '<html></html>');
    $zip->close();

    $file = new UploadedFile($zipPath, 'no-manifest.zip', 'application/zip', null, true);

    $this->actingAs($user)
        ->postJson(route('scorm.packages.store'), [
            'file' => $file,
        ])
        ->assertStatus(422);
});

// --- List ---

it('lists SCORM packages for authorized users', function () {
    $user = User::factory()->create(['role' => 'content_manager']);

    ScormPackage::factory()->count(3)->create(['uploaded_by' => $user->id]);

    $this->actingAs($user)
        ->getJson(route('scorm.packages.index'))
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('prevents learners from listing SCORM packages', function () {
    $user = User::factory()->create(['role' => 'learner']);

    $this->actingAs($user)
        ->getJson(route('scorm.packages.index'))
        ->assertForbidden();
});

// --- Delete ---

it('allows authorized users to delete SCORM packages', function () {
    $user = User::factory()->create(['role' => 'content_manager']);
    $package = ScormPackage::factory()->create(['uploaded_by' => $user->id]);

    $this->actingAs($user)
        ->deleteJson(route('scorm.packages.destroy', $package))
        ->assertOk();

    $this->assertSoftDeleted('scorm_packages', ['id' => $package->id]);
});

it('prevents learners from deleting SCORM packages', function () {
    $user = User::factory()->create(['role' => 'learner']);
    $package = ScormPackage::factory()->create();

    $this->actingAs($user)
        ->deleteJson(route('scorm.packages.destroy', $package))
        ->assertForbidden();
});
