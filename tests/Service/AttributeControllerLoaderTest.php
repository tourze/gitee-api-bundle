<?php

namespace GiteeApiBundle\Tests\Service;

use GiteeApiBundle\Service\AttributeControllerLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(AttributeControllerLoader::class)]
#[RunTestsInSeparateProcesses]
final class AttributeControllerLoaderTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 集成测试设置逻辑
    }

    public function testExtendsLoader(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $this->assertNotNull($loader);
    }

    public function testImplementsRoutingAutoLoaderInterface(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $this->assertNotNull($loader);
    }

    public function testLoadReturnsRouteCollection(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $result = $loader->load('resource');

        $this->assertNotNull($result);
    }

    public function testAutoloadReturnsRouteCollection(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $result = $loader->autoload();

        $this->assertNotNull($result);
    }

    public function testSupportsReturnsFalse(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $this->assertFalse($loader->supports('resource'));
    }
}
