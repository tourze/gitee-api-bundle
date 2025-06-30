<?php

namespace GiteeApiBundle\Tests\Unit\DependencyInjection;

use GiteeApiBundle\DependencyInjection\GiteeApiExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class GiteeApiExtensionTest extends TestCase
{
    public function testExtendsSymfonyExtension(): void
    {
        $extension = new GiteeApiExtension();
        
        $this->assertInstanceOf(Extension::class, $extension);
    }
    
    public function testLoadServicesConfiguration(): void
    {
        $extension = new GiteeApiExtension();
        $container = new ContainerBuilder();
        
        $extension->load([], $container);
        
        // 验证是否设置了自动注册的资源
        $resources = $container->getResources();
        $this->assertNotEmpty($resources);
    }
}