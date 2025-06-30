<?php

namespace GiteeApiBundle\Tests\Unit;

use GiteeApiBundle\GiteeApiBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class GiteeApiBundleTest extends TestCase
{
    public function testBundleExtendsSymfonyBundle(): void
    {
        $bundle = new GiteeApiBundle();
        
        $this->assertInstanceOf(Bundle::class, $bundle);
    }
}