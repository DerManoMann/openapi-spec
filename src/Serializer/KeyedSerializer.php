<?php declare(strict_types=1);

namespace Radebatz\OpenApi\Spec\Serializer;

use OpenApi\Attributes\OpenApiAttributeInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class KeyedSerializer extends DefaultSerializer
{
    public function __construct(protected ?string $key, LoggerInterface $logger = new NullLogger())
    {
        parent::__construct($logger);
    }

    public function serialize(OpenApiAttributeInterface|array|int|string|bool|null $value, ?SerializerResolver $serializerResolver): mixed
    {
        assert(is_array($value));

        if (null === $this->key) {
            $value = [$value];
        } else {
            $key = $value[$this->key];
            unset($value[$this->key]);
            $value = [$key => $value];
        }

        return $value;
    }
}
