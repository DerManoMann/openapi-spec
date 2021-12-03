<?php declare(strict_types=1);

namespace Radebatz\OpenApi\Spec\Tests;

use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\TestCase;
use Radebatz\OpenApi\Spec\Generator;

class GeneratorTest extends TestCase
{
    public function testExample()
    {
        $exampleDir = __DIR__ . '/../vendor/djairhogeuens/openapi-php/example/OpenApi';

        $classloader = new ClassLoader();
        $classloader->addPsr4('OpenApi\\', $exampleDir);
        $classloader->register();

        $spec = (new Generator())
            ->generate([$exampleDir])
        ;

        $this->assertNotNull($spec);
    }
}
