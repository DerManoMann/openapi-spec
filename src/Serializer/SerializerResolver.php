<?php

namespace Radebatz\OpenApi\Spec\Serializer;

use OpenApi\Attributes\Headers\Header;
use OpenApi\Attributes\Media\Content;
use OpenApi\Attributes\Media\Schema;
use OpenApi\Attributes\OpenApiAttributeInterface;
use OpenApi\Attributes\Parameter;
use OpenApi\Attributes\Responses\ApiResponse;
use OpenApi\Attributes\Security\SecurityScheme;
use OpenApi\Attributes\Servers\Server;
use OpenApi\Attributes\Tags\Tag;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Resolves serializers for any given attribute class.
 */
class SerializerResolver
{
    protected SerializerInterface $defaultSerializer;
    protected array $SERIALIZERS;

    public function __construct(LoggerInterface $logger = new NullLogger())
    {
        $this->defaultSerializer = new DefaultSerializer($logger);
        $this->SERIALIZERS = [
            Content::class => new SerializerChain([
                $this->defaultSerializer,
                new KeyedSerializer('mediaType', $logger),
            ]),
            ApiResponse::class => new SerializerChain([
                $this->defaultSerializer,
                new KeyedSerializer('responseCode', $logger),
            ]),
            SecurityScheme::class => new SerializerChain([
                $this->defaultSerializer,
                new KeyedSerializer('name', $logger),
            ]),
            Parameter::class => new SerializerChain([
                $this->defaultSerializer,
                new KeyedSerializer(null, $logger),
            ]),
            Tag::class => new SerializerChain([
                $this->defaultSerializer,
                new KeyedSerializer(null, $logger),
            ]),
            Server::class => new SerializerChain([
                $this->defaultSerializer,
                new KeyedSerializer(null, $logger),
            ]),
            Header::class => new SerializerChain([
                $this->defaultSerializer,
                new KeyedSerializer('name', $logger),
            ]),
            Schema::class => new SerializerChain([
                $this->defaultSerializer,
                new FilterSerializer(['name'], $logger),
                new MapperSerializer([
                    'allowableValues' => 'enum',
                    'defaultValue' => 'default',
                ], $logger),
            ]),
        ];
    }

    public function resolve(string $class): ?SerializerInterface
    {
        if (isset($this->SERIALIZERS[$class])) {
            return $this->SERIALIZERS[$class];
        }

        return $this->defaultSerializer;
    }
}
