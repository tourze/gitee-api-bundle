<?php

declare(strict_types=1);

namespace GiteeApiBundle\Tests\DependencyInjection;

use GiteeApiBundle\DependencyInjection\GiteeApiExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(GiteeApiExtension::class)]
final class GiteeApiExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
}
