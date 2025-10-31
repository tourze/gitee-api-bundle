<?php

declare(strict_types=1);

namespace GiteeApiBundle\Tests\Helper;

use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * 测试实体生成器 - 用于创建复杂的测试对象
 */
class TestEntityGenerator
{
    /**
     * @param array<string, mixed> $data
     */
    public static function createResponse(array $data): ResponseInterface
    {
        return new TestResponse($data);
    }

    public static function createResponseStream(): ResponseStreamInterface
    {
        return new TestResponseStream();
    }
}
