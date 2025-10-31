<?php

namespace GiteeApiBundle\Tests\Enum;

use GiteeApiBundle\Enum\GiteeScope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(GiteeScope::class)]
final class GiteeScopeTest extends AbstractEnumTestCase
{
    /**
     * 测试获取默认作用域
     */
    public function testGetDefaultScopes(): void
    {
        $defaultScopes = GiteeScope::getDefaultScopes();

        $this->assertCount(5, $defaultScopes);
        $this->assertContains(GiteeScope::USER, $defaultScopes);
        $this->assertContains(GiteeScope::PROJECTS, $defaultScopes);
        $this->assertContains(GiteeScope::PULL_REQUESTS, $defaultScopes);
        $this->assertContains(GiteeScope::ISSUES, $defaultScopes);
        $this->assertContains(GiteeScope::NOTES, $defaultScopes);
    }

    /**
     * 测试将作用域数组转换为字符串
     */
    public function testToStringWithMultipleScopes(): void
    {
        $scopes = [
            GiteeScope::USER,
            GiteeScope::PROJECTS,
        ];

        $result = GiteeScope::toString($scopes);

        $this->assertEquals('user_info projects', $result);
    }

    /**
     * 测试将单个作用域转换为字符串
     */
    public function testToStringWithSingleScope(): void
    {
        $scopes = [GiteeScope::USER];

        $result = GiteeScope::toString($scopes);

        $this->assertEquals('user_info', $result);
    }

    /**
     * 测试将空作用域数组转换为字符串
     */
    public function testToStringWithEmptyScopes(): void
    {
        $scopes = [];

        $result = GiteeScope::toString($scopes);

        $this->assertEquals('', $result);
    }

    /**
     * 测试所有作用域值是否都是有效的字符串
     */
    public function testAllScopeValuesAreValidStrings(): void
    {
        $scopes = [
            GiteeScope::USER,
            GiteeScope::PROJECTS,
            GiteeScope::PULL_REQUESTS,
            GiteeScope::ISSUES,
            GiteeScope::NOTES,
            GiteeScope::ENTERPRISES,
            GiteeScope::GISTS,
            GiteeScope::GROUPS,
            GiteeScope::HOOKS,
        ];

        foreach ($scopes as $scope) {
            $this->assertNotEmpty($scope->value);
        }
    }

    /**
     * 测试toArray()方法返回正确的数组格式
     */
    public function testToArray(): void
    {
        $scope = GiteeScope::USER;
        $result = $scope->toSelectItem();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertEquals(GiteeScope::USER->value, $result['value']);
        $this->assertEquals(GiteeScope::USER->getLabel(), $result['label']);
    }

    /**
     * 测试toSelectItem()方法返回正确的选择项格式
     */
    #[TestWith([GiteeScope::USER, 'user_info', '用户信息'])]
    #[TestWith([GiteeScope::PROJECTS, 'projects', '项目管理'])]
    #[TestWith([GiteeScope::PULL_REQUESTS, 'pull_requests', '拉取请求'])]
    #[TestWith([GiteeScope::ISSUES, 'issues', '问题管理'])]
    #[TestWith([GiteeScope::NOTES, 'notes', '评论管理'])]
    #[TestWith([GiteeScope::ENTERPRISES, 'enterprises', '企业管理'])]
    #[TestWith([GiteeScope::GISTS, 'gists', '代码片段'])]
    #[TestWith([GiteeScope::GROUPS, 'groups', '组织管理'])]
    #[TestWith([GiteeScope::HOOKS, 'hook', 'Webhook'])]
    public function testValueAndLabel(GiteeScope $enum, string $expectedValue, string $expectedLabel): void
    {
        $this->assertSame($expectedValue, $enum->value);
        $this->assertSame($expectedLabel, $enum->getLabel());
    }

    public function testTryFromWithValidValueShouldReturnCorrectEnum(): void
    {
        $this->assertSame(GiteeScope::USER, GiteeScope::tryFrom('user_info'));
        $this->assertSame(GiteeScope::PROJECTS, GiteeScope::tryFrom('projects'));
        $this->assertSame(GiteeScope::HOOKS, GiteeScope::tryFrom('hook'));
    }
}
