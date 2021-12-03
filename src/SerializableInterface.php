<?php declare(strict_types=1);

namespace Radebatz\OpenApi\Spec;

use OpenApi\Spec\Serializer\SerializerInterface;

interface SerializableInterface
{
    public function serializer(): SerializerInterface;
}
