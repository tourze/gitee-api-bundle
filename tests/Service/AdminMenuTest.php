<?php

declare(strict_types=1);

namespace GiteeApiBundle\Tests\Service;

use GiteeApiBundle\Service\AdminMenu;
use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private AdminMenu $adminMenu;

    private LinkGeneratorInterface $linkGenerator;

    private ItemInterface $item;

    protected function onSetUp(): void
    {
        $this->linkGenerator = new TestLinkGenerator();
        self::getContainer()->set(LinkGeneratorInterface::class, $this->linkGenerator);
        $this->adminMenu = self::getService(AdminMenu::class);
        $this->item = $this->createMockMenuItem();
    }

    private function createMockMenuItem(): ItemInterface
    {
        $mock = $this->createMock(ItemInterface::class);
        /** @var array<string, ItemInterface> $children */
        $children = [];

        // 配置getChild方法 - 支持动态添加子项
        $mock->method('getChild')
            ->willReturnCallback(function (string $name) use (&$children): ?ItemInterface {
                return $children[$name] ?? null;
            })
        ;

        // 配置addChild方法 - 会创建并返回子项
        $mock->method('addChild')
            ->willReturnCallback(function (string $name) use (&$children) {
                $child = $this->createChildMenuItem($name);
                $children[$name] = $child;

                return $child;
            })
        ;

        return $mock;
    }

    private function createChildMenuItem(string $name): ItemInterface
    {
        $child = $this->createMock(ItemInterface::class);
        /** @var array<string, ItemInterface> $childChildren */
        $childChildren = [];
        $uri = '';
        /** @var array<string, mixed> $attributes */
        $attributes = [];

        $child->method('getName')->willReturn($name);

        $child->method('addChild')
            ->willReturnCallback(function (string $childName) use (&$childChildren) {
                $grandChild = $this->createChildMenuItem($childName);
                $childChildren[$childName] = $grandChild;

                return $grandChild;
            })
        ;

        $child->method('getChild')
            ->willReturnCallback(function (string $childName) use (&$childChildren) {
                return $childChildren[$childName] ?? null;
            })
        ;

        $child->method('setUri')
            ->willReturnCallback(function (?string $newUri) use (&$uri, $child) {
                $uri = $newUri;

                return $child;
            })
        ;

        $child->method('getUri')
            ->willReturnCallback(function () use (&$uri) {
                return $uri;
            })
        ;

        $child->method('setAttribute')
            ->willReturnCallback(function (string $name, $value) use (&$attributes, $child) {
                $attributes[$name] = $value;

                return $child;
            })
        ;

        $child->method('getAttribute')
            ->willReturnCallback(function (string $name, $default = null) use (&$attributes) {
                return $attributes[$name] ?? $default;
            })
        ;

        return $child;
    }

    public function testServiceCreation(): void
    {
        $this->assertInstanceOf(AdminMenu::class, $this->adminMenu);
    }

    public function testImplementsMenuProviderInterface(): void
    {
        $this->assertInstanceOf(MenuProviderInterface::class, $this->adminMenu);
    }

    public function testInvokeShouldBeCallable(): void
    {
        // AdminMenu实现了__invoke方法，所以是可调用的
        $reflection = new \ReflectionClass(AdminMenu::class);
        $this->assertTrue($reflection->hasMethod('__invoke'));
    }

    public function testInvokeCreatesGiteeApiMenu(): void
    {
        // 执行 AdminMenu 的 __invoke 方法
        ($this->adminMenu)($this->item);

        // 验证 "Gitee API管理" 主菜单已创建
        $giteeMenu = $this->item->getChild('Gitee API管理');
        $this->assertInstanceOf(ItemInterface::class, $giteeMenu);

        // 验证子菜单项已创建
        $applicationItem = $giteeMenu->getChild('Gitee应用');
        $tokenItem = $giteeMenu->getChild('访问令牌');
        $repositoryItem = $giteeMenu->getChild('仓库信息');

        $this->assertInstanceOf(ItemInterface::class, $applicationItem);
        $this->assertInstanceOf(ItemInterface::class, $tokenItem);
        $this->assertInstanceOf(ItemInterface::class, $repositoryItem);

        // 验证子菜单项的URI设置
        $this->assertEquals('/admin/gitee-application', $applicationItem->getUri());
        $this->assertEquals('/admin/gitee-access-token', $tokenItem->getUri());
        $this->assertEquals('/admin/gitee-repository', $repositoryItem->getUri());

        // 验证子菜单项的图标属性
        $this->assertEquals('fas fa-code-branch', $applicationItem->getAttribute('icon'));
        $this->assertEquals('fas fa-key', $tokenItem->getAttribute('icon'));
        $this->assertEquals('fas fa-folder', $repositoryItem->getAttribute('icon'));
    }

    public function testInvokeWithExistingGiteeMenu(): void
    {
        // 先添加一个现有的菜单
        $existingMenu = $this->item->addChild('Gitee API管理');

        // 执行 AdminMenu 的 __invoke 方法
        ($this->adminMenu)($this->item);

        // 验证使用了现有的菜单而不是创建新的
        $giteeMenu = $this->item->getChild('Gitee API管理');
        $this->assertSame($existingMenu, $giteeMenu);

        // 验证子菜单项被添加到了现有菜单中
        $applicationItem = $giteeMenu->getChild('Gitee应用');
        $tokenItem = $giteeMenu->getChild('访问令牌');
        $repositoryItem = $giteeMenu->getChild('仓库信息');

        $this->assertInstanceOf(ItemInterface::class, $applicationItem);
        $this->assertInstanceOf(ItemInterface::class, $tokenItem);
        $this->assertInstanceOf(ItemInterface::class, $repositoryItem);

        // 验证URI和图标设置
        $this->assertEquals('/admin/gitee-application', $applicationItem->getUri());
        $this->assertEquals('fas fa-code-branch', $applicationItem->getAttribute('icon'));
    }

    public function testInvokeHandlesNullGiteeMenu(): void
    {
        // 创建一个Mock菜单项，第一次返回null，第二次添加后仍返回null
        $mockMenuItem = $this->createMock(ItemInterface::class);

        $mockMenuItem->expects($this->exactly(2))
            ->method('getChild')
            ->with('Gitee API管理')
            ->willReturn(null)
        ;

        $mockMenuItem->expects($this->once())
            ->method('addChild')
            ->with('Gitee API管理')
            ->willReturn($this->createMock(ItemInterface::class))
        ;

        // 方法应该能安全地处理返回null的情况
        ($this->adminMenu)($mockMenuItem);

        // 验证没有异常抛出
        $this->assertTrue(true, 'AdminMenu should handle null getChild result without errors');
    }
}
