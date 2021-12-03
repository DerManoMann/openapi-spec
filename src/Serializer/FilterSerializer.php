<?php declare(strict_types=1);

namespace Radebatz\OpenApi\Spec\Serializer;

use OpenApi\Attributes\OpenApiAttributeInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class FilterSerializer extends DefaultSerializer
{
    public function __construct(protected array $properties, LoggerInterface $logger = new NullLogger())
    {
        parent::__construct($logger);
    }


    public function serialize (OpenApiAttributeInterface|array|int|string|bool|null $value, ?SerializerResolver $serializerResolver): mixed
    {
        assert(is_array($value));

        foreach ($this->properties as $property) {
            unset($value[$property]);
        }

        return $value;
    }
}
