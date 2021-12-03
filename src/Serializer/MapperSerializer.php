<?php declare(strict_types=1);

namespace Radebatz\OpenApi\Spec\Serializer;

use OpenApi\Attributes\OpenApiAttributeInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class MapperSerializer extends DefaultSerializer
{
    public function __construct(protected array $mappings, LoggerInterface $logger = new NullLogger())
    {
        parent::__construct($logger);
    }

    public function serialize(OpenApiAttributeInterface|array|int|string|bool|null $value, ?SerializerResolver $serializerResolver): mixed
    {
        assert(is_array($value));

        foreach ($this->mappings as $key => $name) {
            if (isset($value[$key])) {
                $value[$name] = $value[$key];
                unset($value[$key]);
            }
        }

        return $value;
    }
}
