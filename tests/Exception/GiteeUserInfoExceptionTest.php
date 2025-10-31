<?php

namespace GiteeApiBundle\Tests\Exception;

use GiteeApiBundle\Exception\GiteeUserInfoException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(GiteeUserInfoException::class)]
final class GiteeUserInfoExceptionTest extends AbstractExceptionTestCase
{
    public function testExtendsRuntimeException(): void
    {
        $exception = GiteeUserInfoException::failedToGetUsername();

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testFailedToGetUsernameReturnsCorrectMessage(): void
    {
        $exception = GiteeUserInfoException::failedToGetUsername();

        $this->assertEquals('Failed to get Gitee username from user info response', $exception->getMessage());
    }
}
