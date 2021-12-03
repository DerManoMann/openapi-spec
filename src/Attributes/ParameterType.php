<?php declare(strict_types=1);

namespace Radebatz\OpenApi\Spec\Attributes;

use OpenApi\Attributes\OpenApiAttributeInterface;
use Radebatz\OpenApi\Spec\Helper;
use ReflectionNamedType;

class ParameterType implements OpenApiAttributeInterface
{
    use Helper;

    public function __construct(public string $name, public ReflectionNamedType $type)
    {
    }

    public function ref(): string
    {
        return $this->fqdn2ref($this->type->getName());
    }
}
