<?php

declare(strict_types=1);

namespace GiteeApiBundle\Tests;

use GiteeApiBundle\GiteeApiBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(GiteeApiBundle::class)]
#[RunTestsInSeparateProcesses]
final class GiteeApiBundleTest extends AbstractBundleTestCase
{
}
