<?php

namespace GiteeApiBundle\Tests\Unit\Exception;

use GiteeApiBundle\Exception\GiteeApiException;
use GiteeApiBundle\Exception\GiteeUserInfoException;
use PHPUnit\Framework\TestCase;

class GiteeApiExceptionTest extends TestCase
{
    public function testGiteeApiExceptionExtendRuntimeException(): void
    {
        $exception = new GiteeApiException('Test message');
        
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }
    
    public function testGiteeUserInfoExceptionFailedToGetUsername(): void
    {
        $exception = GiteeUserInfoException::failedToGetUsername();
        
        $this->assertInstanceOf(GiteeApiException::class, $exception);
        $this->assertEquals('Failed to get Gitee username from user info response', $exception->getMessage());
    }
}