<?php

namespace GiteeApiBundle\Tests\Exception;

use GiteeApiBundle\Exception\GiteeApiException;
use GiteeApiBundle\Exception\GiteeUserInfoException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(GiteeApiException::class)]
final class GiteeApiExceptionTest extends AbstractExceptionTestCase
{
    public function testGiteeApiExceptionExtendRuntimeException(): void
    {
        $exception = new GiteeApiException('Test message');

        $this->assertNotNull($exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testGiteeUserInfoExceptionFailedToGetUsername(): void
    {
        $exception = GiteeUserInfoException::failedToGetUsername();

        $this->assertNotNull($exception);
        $this->assertEquals('Failed to get Gitee username from user info response', $exception->getMessage());
    }
}
