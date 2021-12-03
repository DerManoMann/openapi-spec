<?php declare(strict_types=1);

namespace Radebatz\OpenApi\Spec;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Radebatz\OpenApi\Spec\Serializer\DefaultSerializer;
use Radebatz\OpenApi\Spec\Serializer\SerializerInterface;
use Radebatz\OpenApi\Spec\Serializer\SerializerResolver;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * OpenApi spec generator.
 *
 * Scans PHP source code and generates OpenApi specifications from the found OpenApi annotations.
 */
class Generator
{
    protected Collector $collector;
    protected Merger $merger;
    protected SerializerInterface $defaultSerializer;

    public function __construct(protected LoggerInterface $logger = new NullLogger())
    {
        $this->collector = new Collector($this->logger);
        $this->merger = new Merger($this->logger);
        $this->defaultSerializer = new DefaultSerializer($this->logger);
    }

    /**
     * Generate OpenAPI spec by scanning the given source files.
     *
     * @param iterable $sources       PHP source files to scan.
     *                                Supported sources:
     *                                * string - file / directory name
     *                                * \SplFileInfo
     *                                * \Symfony\Component\Finder\Finder
     */
    public function generate(iterable $sources)
    {
        $attributes = $this->scanSources($sources);

        $specTree = $this->merger->merge($attributes);
        /* todo: validate
         * require OpenAPIDefinition
         * require paths not empty
         * duplicate response status
         * required properties - ValidableInterface?
         */
        $spec = $this->defaultSerializer->serialize($specTree, new SerializerResolver());

        // export as Json/Yaml
        echo '---' . PHP_EOL;
        echo Yaml::dump($spec, 10);

    }

    /**
     * Recursively scan sources.
     */
    protected function scanSources(iterable $sources): array
    {
        $attributes = [];

        foreach ($sources as $source) {
            if (is_iterable($source)) {
                $attributes += $this->scanSources($source);
            } else {
                $resolvedSource = $source instanceof SplFileInfo ? $source->getPathname() : realpath($source);
                if (!$resolvedSource) {
                    $this->logger->warning(sprintf('Skipping invalid source: %s', (string)$source));
                    continue;
                }
                if (is_dir($resolvedSource)) {
                    $finder = (new Finder())
                        ->sortByName()
                        ->files()
                        ->followLinks()
                        ->name('*.php')
                        ->in($resolvedSource);
                    $attributes += $this->scanSources($finder);
                } else {
                    $attributes += $this->scanFile($resolvedSource);
                }
            }
        }

        return $attributes;
    }

    /**
     * Scan a single file.
     */
    protected function scanFile(string $filename): array
    {
        $scanner = new TokenScanner();
        $fileDetails = $scanner->scanFile($filename);

        $attributes = [];
        foreach ($fileDetails as $fqdn => $details) {
            if (!class_exists($fqdn) && !interface_exists($fqdn) && !trait_exists($fqdn)) {
                $this->logger->warning(sprintf('Entity not found for FQDN: %s - skipping', $filename));
                continue;
            }

            $attributes += $this->scanFqdn($fqdn, $details);
        }

        return $attributes;
    }

    /**
     * Use reflection to scan a `fqdn`.
     */
    protected function scanFqdn(string $fqdn, array $details): array
    {
        $attributes = [
            'object' => [],
            'properties' => [],
            'methods' => [],
        ];

        $rc = new ReflectionClass($fqdn);
        $attributes['object'][] = $this->collector->collect($rc);

        foreach ($rc->getProperties() as $property) {
            if (in_array($property->name, $details['properties'])) {
                $attributes['properties'][$property->getName()] = $this->collector->collect($property);
            }
        }

        foreach ($rc->getMethods() as $method) {
            if (in_array($method->name, $details['methods'])) {
                $attributes['methods'][$method->getName()] = $this->collector->collect($method);
            }
        }

        return [$rc->getName() => $attributes];
    }
}
