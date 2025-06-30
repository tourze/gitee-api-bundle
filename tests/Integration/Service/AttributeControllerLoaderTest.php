<?php

namespace GiteeApiBundle\Tests\Integration\Service;

use GiteeApiBundle\Service\AttributeControllerLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;
use Tourze\RoutingAutoLoaderBundle\Service\RoutingAutoLoaderInterface;

class AttributeControllerLoaderTest extends TestCase
{
    private AttributeControllerLoader $loader;
    
    protected function setUp(): void
    {
        $this->loader = new AttributeControllerLoader();
    }
    
    public function testExtendsLoader(): void
    {
        $this->assertInstanceOf(Loader::class, $this->loader);
    }
    
    public function testImplementsRoutingAutoLoaderInterface(): void
    {
        $this->assertInstanceOf(RoutingAutoLoaderInterface::class, $this->loader);
    }
    
    public function testLoadReturnsRouteCollection(): void
    {
        $result = $this->loader->load('resource');
        
        $this->assertInstanceOf(RouteCollection::class, $result);
    }
    
    public function testAutoloadReturnsRouteCollection(): void
    {
        $result = $this->loader->autoload();
        
        $this->assertInstanceOf(RouteCollection::class, $result);
    }
    
    public function testSupportsReturnsFalse(): void
    {
        $this->assertFalse($this->loader->supports('resource'));
    }
}