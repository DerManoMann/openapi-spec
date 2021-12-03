<?php declare(strict_types=1);

namespace Radebatz\OpenApi\Spec;

use OpenApi\Attributes\Media\Schema;
use OpenApi\Attributes\Method;
use OpenApi\Attributes\OpenAPIDefinition;
use OpenApi\Attributes\Operation;
use OpenApi\Attributes\Parameter;
use OpenApi\Attributes\Parameters\RequestBody;
use OpenApi\Attributes\Path;
use OpenApi\Attributes\Responses\ApiResponse;
use OpenApi\Attributes\Security\SecurityScheme;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Radebatz\OpenApi\Spec\Attributes\ReturnType;
use ReflectionClass;

class Merger
{
    use Helper;

    public function __construct(protected LoggerInterface $logger = new NullLogger())
    {
    }

    /**
     * Wrangle all grouped attributes into a spec.
     */
    public function merge(array $attributes): array
    {
        $data = [
            'openapi' => '3.0.2',
            'paths' => [],
            'components' => [
                'schemas' => [],
                'securitySchemes' => [],
            ],
        ];

        $all = fn (array $attributes, string $class) => array_filter($attributes, fn ($attribute) => $attribute instanceof $class);
        $first = fn (array $attributes, string $class) => ($match = $all($attributes, $class)) ? array_pop($match) : null;

        foreach ($attributes as $fqdn => $fqdnDetails) {
            foreach ($fqdnDetails as $type => $typeAttributes) {
                if ('properties' == $type && $typeAttributes) {
                    $data['components']['schemas'] += $this->mergeSchema($fqdn, $typeAttributes);
                }
                foreach ($typeAttributes as $attributes) {
                    if ($path = $first($attributes, Path::class)) {
                        $data['paths'][$path->path] = $this->mergePath($path, $attributes);
                        continue;
                    }
                    foreach ($attributes as $attribute) {
                        if ($attribute instanceof OpenAPIDefinition) {
                            $data[] = $attribute;
                        }

                        if ($attribute instanceof SecurityScheme) {
                            $data['components']['securitySchemes'][] = $attribute;
                        }
                    }
                }
            }
        }

        return $data;
    }

    protected function mergePath(Path $path, array $attributes): array
    {
        $all = fn (array $attributes, string $class) => array_filter($attributes, fn ($attribute) => $attribute instanceof $class);
        $first = fn (array $attributes, string $class) => ($match = $all($attributes, $class)) ? array_pop($match) : null;

        $responses = $all($attributes, ApiResponse::class);
        if ($responses && ($returnType = $first($attributes, ReturnType::class))) {
            array_walk($responses, function (ApiResponse $response) use ($returnType) {
                foreach ($response->content as $content) {
                    $content->schema ??= new Schema();
                    $content->schema->ref = $returnType->ref();
                }
            });
        }

        $parameters = $all($attributes, Parameter::class);

        $details = [
            $first($attributes, Operation::class),
            'parameters' => $parameters,
            'requestBody' => $first($attributes, RequestBody::class),
            'responses' => $all($attributes, ApiResponse::class),
        ];

        $methods = [];
        foreach ($all($attributes, Method::class) as $method) {
            $methods[strtolower((new ReflectionClass($method))->getShortName())] = $details;
        }

        return $methods;
    }

    protected function mergeSchema(string $fqdn, array $attributes): array
    {
        $schema = [
            'type' => 'object',
            'properties' => [],
        ];

        foreach ($attributes as $name => $propertyAttributes) {
            assert(1 == count($propertyAttributes));
            if (!$attribute = array_pop($propertyAttributes)) {
                continue;
            }
            assert($attribute instanceof Schema);
            $schema['properties'][$attribute->name] = $attribute;
        }

        return $schema['properties'] ? [$this->fqdn2name($fqdn) => $schema] : [];
    }
}
