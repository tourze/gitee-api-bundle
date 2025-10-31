<?php

declare(strict_types=1);

namespace GiteeApiBundle\Exception;

final class GiteeUserInfoException extends \RuntimeException
{
    public static function failedToGetUsername(): self
    {
        return new self('Failed to get Gitee username from user info response');
    }
}
