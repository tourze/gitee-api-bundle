<?php

declare(strict_types=1);

namespace GiteeApiBundle\Exception;

class GiteeApiException extends \RuntimeException
{
    public static function create(string $message, int $code = 0, ?\Throwable $previous = null): self
    {
        return new self($message, $code, $previous);
    }
}
