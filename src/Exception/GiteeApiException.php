<?php

namespace GiteeApiBundle\Exception;

class GiteeApiException extends \RuntimeException
{
}

class GiteeUserInfoException extends GiteeApiException
{
    public static function failedToGetUsername(): self
    {
        return new self('Failed to get Gitee username from user info response');
    }
} 