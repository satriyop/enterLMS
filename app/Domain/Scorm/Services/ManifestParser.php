<?php

namespace App\Domain\Scorm\Services;

use App\Domain\Scorm\DTOs\ManifestData;
use App\Domain\Scorm\Exceptions\ManifestParsingException;
use SimpleXMLElement;

class ManifestParser
{
    /**
     * Parse an imsmanifest.xml file and extract SCORM metadata.
     */
    public function parse(string $xmlContent): ManifestData
    {
        $xml = $this->loadXml($xmlContent);
        $version = $this->detectVersion($xml);
        $identifier = $this->getIdentifier($xml);
        $title = $this->getTitle($xml);
        $entryPoint = $this->getEntryPoint($xml);
        $schemaVersion = $this->getSchemaVersion($xml);

        return new ManifestData(
            version: $version,
            identifier: $identifier,
            title: $title,
            entryPoint: $entryPoint,
            schemaVersion: $schemaVersion,
            metadata: $this->extractMetadata($xml),
        );
    }

    protected function loadXml(string $xmlContent): SimpleXMLElement
    {
        libxml_use_internal_errors(true);

        $xml = simplexml_load_string($xmlContent);

        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            $errorMsg = ! empty($errors) ? $errors[0]->message : 'Unknown XML error';

            throw ManifestParsingException::invalidXml(trim($errorMsg));
        }

        return $xml;
    }

    protected function detectVersion(SimpleXMLElement $xml): string
    {
        // Check for schemaversion element in metadata
        $schemaVersion = $this->getSchemaVersion($xml);

        if ($schemaVersion !== null) {
            if (str_contains($schemaVersion, '2004')) {
                return '2004';
            }

            if (str_contains($schemaVersion, '1.2')) {
                return '1.2';
            }
        }

        // Check namespace for 2004 indicators
        $namespaces = $xml->getNamespaces(true);

        if (isset($namespaces['adlseq']) || isset($namespaces['adlnav'])) {
            return '2004';
        }

        // Default to 1.2 as the more common/older standard
        return '1.2';
    }

    protected function getSchemaVersion(SimpleXMLElement $xml): ?string
    {
        // Try direct metadata child
        $metadata = $xml->metadata ?? null;

        if ($metadata) {
            $schemaversion = $metadata->schemaversion ?? null;

            if ($schemaversion) {
                return (string) $schemaversion;
            }
        }

        // Try with namespace prefix
        $namespaces = $xml->getNamespaces(true);
        $defaultNs = $namespaces[''] ?? null;

        if ($defaultNs) {
            $xml->registerXPathNamespace('ims', $defaultNs);
            $result = $xml->xpath('//ims:metadata/ims:schemaversion');

            if (! empty($result)) {
                return (string) $result[0];
            }
        }

        return null;
    }

    protected function getIdentifier(SimpleXMLElement $xml): ?string
    {
        return isset($xml['identifier']) ? (string) $xml['identifier'] : null;
    }

    protected function getTitle(SimpleXMLElement $xml): string
    {
        // Try organizations > organization > title
        $namespaces = $xml->getNamespaces(true);
        $defaultNs = $namespaces[''] ?? null;

        if ($defaultNs) {
            $xml->registerXPathNamespace('ims', $defaultNs);
            $titles = $xml->xpath('//ims:organizations/ims:organization/ims:title');

            if (! empty($titles)) {
                return (string) $titles[0];
            }
        }

        // Try without namespace
        if (isset($xml->organizations->organization->title)) {
            return (string) $xml->organizations->organization->title;
        }

        // Fallback to identifier
        return $this->getIdentifier($xml) ?? 'Untitled SCORM Package';
    }

    protected function getEntryPoint(SimpleXMLElement $xml): string
    {
        $namespaces = $xml->getNamespaces(true);
        $defaultNs = $namespaces[''] ?? null;

        // Try with namespace
        if ($defaultNs) {
            $xml->registerXPathNamespace('ims', $defaultNs);

            // Get the first resource with adlcp:scormtype="sco" or adlcp:scormType="sco"
            foreach (['adlcp'] as $prefix) {
                if (isset($namespaces[$prefix])) {
                    $xml->registerXPathNamespace($prefix, $namespaces[$prefix]);

                    // SCORM 1.2 uses lowercase, 2004 uses camelCase
                    $resources = $xml->xpath('//ims:resources/ims:resource[@'.$prefix.':scormtype="sco" or @'.$prefix.':scormType="sco"]');

                    if (! empty($resources) && isset($resources[0]['href'])) {
                        return (string) $resources[0]['href'];
                    }
                }
            }

            // If no SCO-typed resource, get the first resource with an href
            $resources = $xml->xpath('//ims:resources/ims:resource[@href]');

            if (! empty($resources)) {
                return (string) $resources[0]['href'];
            }
        }

        // Try without namespace
        if (isset($xml->resources->resource)) {
            foreach ($xml->resources->resource as $resource) {
                if (isset($resource['href'])) {
                    return (string) $resource['href'];
                }
            }
        }

        throw ManifestParsingException::missingRequiredElement('resource href (entry point)');
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractMetadata(SimpleXMLElement $xml): array
    {
        $metadata = [];

        $schemaVersion = $this->getSchemaVersion($xml);
        if ($schemaVersion) {
            $metadata['schema_version'] = $schemaVersion;
        }

        // Count resources
        $namespaces = $xml->getNamespaces(true);
        $defaultNs = $namespaces[''] ?? null;

        if ($defaultNs) {
            $xml->registerXPathNamespace('ims', $defaultNs);
            $resources = $xml->xpath('//ims:resources/ims:resource');
            $metadata['resource_count'] = ! empty($resources) ? count($resources) : 0;

            $items = $xml->xpath('//ims:organizations/ims:organization/ims:item');
            $metadata['item_count'] = ! empty($items) ? count($items) : 0;
        }

        if (isset($xml->metadata->schema)) {
            $metadata['schema'] = (string) $xml->metadata->schema;
        }

        return $metadata;
    }
}
