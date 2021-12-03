<?php declare(strict_types=1);

namespace Radebatz\OpenApi\Spec\Attributes;

use OpenApi\Attributes\OpenApiAttributeInterface;
use ReflectionNamedType;

class ReturnType implements OpenApiAttributeInterface
{
    public function __construct(public ReflectionNamedType $type)
    {
    }

    public function ref(): string
    {
        $token = explode('\\', $this->type->getName());
        $name = array_pop($token);

        return "#components/schemas/$name";
    }
}
