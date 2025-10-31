<?php

namespace GiteeApiBundle\Tests\Service;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * 用于测试的模拟 URL 生成器
 */
final class MockUrlGenerator implements UrlGeneratorInterface
{
    /**
     * @phpstan-ignore-next-line Interface compatibility requires generic array type
     */
    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
    {
        // Return a simple URL for testing
        return '/mock/' . $name . ([] === $parameters ? '' : '?' . http_build_query($parameters));
    }

    public function setContext(RequestContext $context): void
    {
        // Do nothing for mock
    }

    public function getContext(): RequestContext
    {
        return new RequestContext();
    }
}
