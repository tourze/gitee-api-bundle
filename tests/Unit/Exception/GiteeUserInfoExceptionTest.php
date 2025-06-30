<?php

namespace GiteeApiBundle\Tests\Unit\Exception;

use GiteeApiBundle\Exception\GiteeApiException;
use GiteeApiBundle\Exception\GiteeUserInfoException;
use PHPUnit\Framework\TestCase;

class GiteeUserInfoExceptionTest extends TestCase
{
    public function testExtendsGiteeApiException(): void
    {
        $exception = GiteeUserInfoException::failedToGetUsername();
        
        $this->assertInstanceOf(GiteeApiException::class, $exception);
    }
    
    public function testFailedToGetUsernameReturnsCorrectMessage(): void
    {
        $exception = GiteeUserInfoException::failedToGetUsername();
        
        $this->assertEquals('Failed to get Gitee username from user info response', $exception->getMessage());
    }
}