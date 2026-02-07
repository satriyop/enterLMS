<?php

use App\Domain\Scorm\DTOs\ManifestData;
use App\Domain\Scorm\Exceptions\ManifestParsingException;
use App\Domain\Scorm\Services\ManifestParser;

beforeEach(function () {
    $this->parser = new ManifestParser;
});

it('parses a valid SCORM 1.2 manifest', function () {
    $xml = file_get_contents(base_path('tests/fixtures/scorm/imsmanifest-12.xml'));

    $result = $this->parser->parse($xml);

    expect($result)
        ->toBeInstanceOf(ManifestData::class)
        ->version->toBe('1.2')
        ->identifier->toBe('SCORM12-TEST')
        ->title->toBe('Modul Kepatuhan Anti Pencucian Uang')
        ->entryPoint->toBe('index.html')
        ->schemaVersion->toBe('1.2');
});

it('parses a valid SCORM 2004 manifest', function () {
    $xml = file_get_contents(base_path('tests/fixtures/scorm/imsmanifest-2004.xml'));

    $result = $this->parser->parse($xml);

    expect($result)
        ->toBeInstanceOf(ManifestData::class)
        ->version->toBe('2004')
        ->identifier->toBe('SCORM2004-TEST')
        ->title->toBe('Keamanan Siber untuk Perbankan')
        ->entryPoint->toBe('module1/index.html')
        ->schemaVersion->toBe('2004 4th Edition');
});

it('extracts metadata with resource and item counts', function () {
    $xml = file_get_contents(base_path('tests/fixtures/scorm/imsmanifest-2004.xml'));

    $result = $this->parser->parse($xml);

    expect($result->metadata)
        ->toBeArray()
        ->toHaveKey('resource_count', 2)
        ->toHaveKey('item_count', 2)
        ->toHaveKey('schema_version', '2004 4th Edition');
});

it('extracts schema name from metadata', function () {
    $xml = file_get_contents(base_path('tests/fixtures/scorm/imsmanifest-12.xml'));

    $result = $this->parser->parse($xml);

    expect($result->metadata)->toHaveKey('schema', 'ADL SCORM');
});

it('throws on invalid XML', function () {
    $this->parser->parse('<not valid xml>>>');
})->throws(ManifestParsingException::class);

it('throws on missing entry point resource', function () {
    $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<manifest identifier="TEST">
    <metadata>
        <schemaversion>1.2</schemaversion>
    </metadata>
    <organizations default="ORG-1">
        <organization identifier="ORG-1">
            <title>Test</title>
        </organization>
    </organizations>
    <resources>
    </resources>
</manifest>
XML;

    $this->parser->parse($xml);
})->throws(ManifestParsingException::class);

it('defaults to version 1.2 when no schemaversion is present', function () {
    $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<manifest identifier="LEGACY">
    <organizations default="ORG-1">
        <organization identifier="ORG-1">
            <title>Legacy Package</title>
        </organization>
    </organizations>
    <resources>
        <resource identifier="RES-1" type="webcontent" href="index.html">
            <file href="index.html"/>
        </resource>
    </resources>
</manifest>
XML;

    $result = $this->parser->parse($xml);

    expect($result->version)->toBe('1.2');
    expect($result->entryPoint)->toBe('index.html');
});

it('detects 2004 by namespace when schemaversion is missing', function () {
    $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<manifest identifier="NS-2004"
    xmlns="http://www.imsglobal.org/xsd/imscp_v1p1"
    xmlns:adlseq="http://www.adlnet.org/xsd/adlseq_v1p3">
    <organizations default="ORG-1">
        <organization identifier="ORG-1">
            <title>Detected 2004</title>
            <item identifier="ITEM-1" identifierref="RES-1">
                <adlseq:rollupRules/>
            </item>
        </organization>
    </organizations>
    <resources>
        <resource identifier="RES-1" type="webcontent" href="start.html">
            <file href="start.html"/>
        </resource>
    </resources>
</manifest>
XML;

    $result = $this->parser->parse($xml);

    expect($result->version)->toBe('2004');
});

it('falls back to identifier when title is missing', function () {
    $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<manifest identifier="FALLBACK-ID">
    <organizations default="ORG-1">
        <organization identifier="ORG-1">
        </organization>
    </organizations>
    <resources>
        <resource identifier="RES-1" type="webcontent" href="index.html">
            <file href="index.html"/>
        </resource>
    </resources>
</manifest>
XML;

    $result = $this->parser->parse($xml);

    expect($result->title)->toBe('FALLBACK-ID');
});

it('converts manifest data to array', function () {
    $xml = file_get_contents(base_path('tests/fixtures/scorm/imsmanifest-12.xml'));

    $result = $this->parser->parse($xml);
    $array = $result->toArray();

    expect($array)
        ->toBeArray()
        ->toHaveKeys(['version', 'identifier', 'title', 'entry_point', 'schema_version', 'metadata']);
});
