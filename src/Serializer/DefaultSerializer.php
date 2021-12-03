<?php

namespace Radebatz\OpenApi\Spec\Serializer;

use OpenApi\Attributes\OpenApiAttributeInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Radebatz\OpenApi\Spec\SerializableInterface;
use ReflectionClass;

/**
 * Default serializer for all used data types.
 */
class DefaultSerializer implements SerializerInterface
{
    public function __construct(protected LoggerInterface $logger = new NullLogger())
    {
    }

    public function serialize(OpenApiAttributeInterface|array|int|string|bool|null $value, ?SerializerResolver $serializerResolver): mixed
    {
        if (is_object($value) || is_array($value)) {
            $data = [];
            foreach ($value as $vname => $vvalue) {
                $serializer = $vvalue instanceof SerializableInterface
                    ? $vvalue->serializer()
                    : (
                        $vvalue instanceof OpenApiAttributeInterface && $serializerResolver
                        ? $serializerResolver->resolve(get_class($vvalue))
                        : $this
                    );

                $serialized = $serializer->serialize($vvalue, $serializerResolver);

                if (empty($serialized)) {
                    continue;
                }

                if ($vvalue instanceof OpenApiAttributeInterface) {
                    // strip defaults
                    $rc = new ReflectionClass($vvalue);
                    $ctorArgs = ($ctor = $rc->getConstructor()) ? $ctor->getParameters() : [];
                    if (!$ctorArgs || $ctorArgs[0]->isDefaultValueAvailable()) {
                        $defaults = $rc->newInstance();
                        foreach ($rc->getProperties() as $rp) {
                            $name = $rp->getName();
                            if (isset($serialized[$name]) && $serialized[$name] === $defaults->{$name}) {
                                unset($serialized[$name]);
                            }
                        }
                    }
                }

                if (is_numeric($vname) && $vvalue instanceof OpenApiAttributeInterface) {
                    $data += $serialized;
                } else {
                    $vname = 'ref' == $vname ? '$ref' : $vname;
                    if (null === $vname) {
                        $data[] = $serialized;
                    } else {
                        $data[$vname] = $serialized;
                    }
                }
            }

            return $data;
        }

        return $value;
    }
}
