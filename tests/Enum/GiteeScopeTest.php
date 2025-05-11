<?php

namespace GiteeApiBundle\Tests\Enum;

use GiteeApiBundle\Enum\GiteeScope;
use PHPUnit\Framework\TestCase;

class GiteeScopeTest extends TestCase
{
    /**
     * 测试获取默认作用域
     */
    public function testGetDefaultScopes(): void
    {
        $defaultScopes = GiteeScope::getDefaultScopes();
        
        $this->assertIsArray($defaultScopes);
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
    public function testToString_withMultipleScopes(): void
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
    public function testToString_withSingleScope(): void
    {
        $scopes = [GiteeScope::USER];
        
        $result = GiteeScope::toString($scopes);
        
        $this->assertEquals('user_info', $result);
    }
    
    /**
     * 测试将空作用域数组转换为字符串
     */
    public function testToString_withEmptyScopes(): void
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
            $this->assertIsString($scope->value);
            $this->assertNotEmpty($scope->value);
        }
    }
} 