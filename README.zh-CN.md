# GiteeApiBundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/gitee-api-bundle.svg?style=flat-square)]
(https://packagist.org/packages/tourze/gitee-api-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/gitee-api-bundle.svg?style=flat-square)]
(https://packagist.org/packages/tourze/gitee-api-bundle)
[![License](https://img.shields.io/packagist/l/tourze/gitee-api-bundle.svg?style=flat-square)]
(https://packagist.org/packages/tourze/gitee-api-bundle)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/php-monorepo/test.yml?branch=master&style=flat-square)](https://github.com/tourze/php-monorepo/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/php-monorepo?style=flat-square)]
(https://codecov.io/gh/tourze/php-monorepo)

为 Symfony 应用程序提供全面的 Gitee API 集成包，包含 OAuth 认证、API 客户端、仓库同步和数据管理功能。

## 目录

- [功能特性](#功能特性)
- [安装](#安装)
- [要求](#要求)
- [配置](#配置)
- [使用方法](#使用方法)
  - [OAuth 认证](#oauth-认证)
  - [多令牌支持](#多令牌支持)
  - [API 调用](#api-调用)
  - [控制台命令](#控制台命令)
  - [自定义 OAuth 流程](#自定义-oauth-流程)
- [高级用法](#高级用法)
- [数据模型](#数据模型)
- [服务架构](#服务架构)
- [安全性](#安全性)
- [错误处理](#错误处理)
- [测试](#测试)
- [贡献](#贡献)
- [许可证](#许可证)

## 功能特性

- 🔐 **OAuth 2.0 认证** - 完整的 OAuth 流程，支持每个用户多个令牌
- 🔌 **全面的 API 客户端** - 访问用户信息、仓库、分支、问题和拉取请求
- 🔄 **仓库同步** - 同步并缓存仓库数据到本地
- 🎛️ **灵活的权限管理** - 为每个应用配置 OAuth 权限范围
- 🏗️ **Doctrine 集成** - 应用、令牌和仓库的实体类
- 🛠️ **控制台命令** - 用于数据管理的 CLI 工具
- 🎨 **可自定义控制器** - 扩展或替换默认的 OAuth 控制器
- 📦 **自动配置** - Symfony Flex recipe 快速设置

## 安装

1. 添加包到您的项目中：

```bash
composer require tourze/gitee-api-bundle
```

## 要求

- PHP 8.1 或更高版本
- Symfony 6.4 或更高版本
- Doctrine ORM 3.0 或更高版本

## 配置

1. 在数据库中创建和配置您的 Gitee OAuth 应用：

```php
use GiteeApiBundle\Enum\GiteeScope;

$application = new GiteeApplication();
$application->setName('My Gitee App')
    ->setClientId('your_client_id')
    ->setClientSecret('your_client_secret')
    ->setHomepage('https://your-domain.com')
    ->setDescription('My Gitee application description')
    ->setScopes([
        GiteeScope::USER,
        GiteeScope::PROJECTS,
        GiteeScope::PULL_REQUESTS,
        GiteeScope::ISSUES,
    ]);

$entityManager->persist($application);
$entityManager->flush();
```

默认情况下，应用程序配置以下权限范围：

- `user_info`: 访问用户信息
- `projects`: 访问仓库
- `pull_requests`: 访问拉取请求
- `issues`: 访问问题
- `notes`: 访问评论

其他可用的权限范围：

- `enterprises`: 访问企业信息
- `gists`: 访问代码片段
- `groups`: 访问群组
- `hook`: 访问 webhooks

2. 在您的 Gitee 应用设置中配置回调 URL：

```text
https://your-domain.com/gitee/oauth/callback/{applicationId}
```

## 使用方法

### OAuth 认证

该包提供了内置的控制器来处理 OAuth 流程。要开始认证过程，将用户重定向到：

```text
/gitee/oauth/connect/{applicationId}?callbackUrl={successUrl}
```

`callbackUrl` 参数支持以下模板变量：

- `{accessToken}`: OAuth 访问令牌
- `{userId}`: 用户 ID（如果未认证用户则为 Gitee 用户名）
- `{giteeUsername}`: Gitee 用户名
- `{applicationId}`: 应用程序 ID

在模板中的使用示例：

```html
{# 基本用法 #}
<a href="{{ path('gitee_oauth_connect', {applicationId: application.id}) }}">
    连接 Gitee
</a>

{# 自定义回调 URL #}
<a href="{{ path('gitee_oauth_connect', {
    applicationId: application.id,
    callbackUrl: 'https://your-domain.com/success?token={accessToken}&user={giteeUsername}'
}) }}">
    连接 Gitee
</a>
```

该包将：

1. 将用户重定向到 Gitee 的授权页面，包含配置的权限范围
2. 处理 OAuth 回调
3. 存储访问令牌
4. 重定向到指定的回调 URL 并替换模板变量，如果没有提供回调 URL 则重定向到主页路由

### 多令牌支持

该包现在支持每个用户每个应用程序多个令牌。每次用户授权应用程序时，都会创建一个新令牌而不是更新现有令牌。
使用 API 时，将使用最近创建的有效令牌。

### API 调用

```php
// 获取用户信息（需要 user_info 权限）
$user = $giteeApiClient->getUser($userId, $application);

// 获取用户仓库（需要 projects 权限）
$repos = $giteeApiClient->getRepositories($userId, $application);

// 获取仓库详情（需要 projects 权限）
$repo = $giteeApiClient->getRepository('owner', 'repo', $userId, $application);

// 获取仓库分支（需要 projects 权限）
$branches = $giteeApiClient->getBranches('owner', 'repo', $userId, $application);

// 获取仓库问题（需要 issues 权限）
$issues = $giteeApiClient->getIssues('owner', 'repo', ['state' => 'open'], $userId, $application);

// 获取仓库拉取请求（需要 pull_requests 权限）
$prs = $giteeApiClient->getPullRequests('owner', 'repo', ['state' => 'open'], $userId, $application);
```

### 控制台命令

该包提供控制台命令来管理 Gitee 数据：

#### gitee:sync:repositories

将用户的 Gitee 仓库与本地数据库同步。

```bash
# 为特定用户和应用程序同步仓库
php bin/console gitee:sync:repositories {userId} {applicationId}

# 强制更新所有仓库（即使它们没有更改）
php bin/console gitee:sync:repositories {userId} {applicationId} --force
```

**参数：**
- `userId`: 要同步仓库的用户 ID
- `applicationId`: 要使用的 Gitee 应用程序 ID

**选项：**
- `--force` (`-f`): 即使未检测到更改也强制更新所有仓库信息

命令将：
1. 使用应用程序的 OAuth 令牌获取指定用户的所有仓库
2. 与现有本地仓库数据进行比较
3. 如果检测到更改，创建新的仓库记录或更新现有记录
4. 跳过自上次同步以来未更新的仓库（除非使用 `--force`）
5. 显示进度和摘要统计信息

## 自定义 OAuth 流程

默认控制器提供基本的 OAuth 流程。您可以通过以下方式自定义流程：

1. 创建扩展默认控制器的自定义控制器：

```php
use GiteeApiBundle\Controller\OAuthController;

class CustomOAuthController extends OAuthController
{
    public function callback(Request $request, GiteeApplication $application): Response
    {
        $token = parent::callback($request, $application);

        // 在这里添加您的自定义逻辑

        return $this->redirectToRoute('your_custom_route');
    }
}
```

2. 或者使用服务在您自己的控制器中完全实现流程：

```php
use GiteeApiBundle\Service\GiteeOAuthService;

class YourController
{
    public function __construct(
        private readonly GiteeOAuthService $oauthService,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function connect(Request $request, GiteeApplication $application): Response
    {
        $callbackUrl = 'https://your-domain.com/success?token={accessToken}';

        $authUrl = $this->oauthService->getAuthorizationUrl(
            $application,
            $this->urlGenerator->generate('your_callback_route', [
                'applicationId' => $application->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            $callbackUrl
        );

        return new RedirectResponse($authUrl);
    }

    public function callback(Request $request, GiteeApplication $application): Response
    {
        $token = $this->oauthService->handleCallback(
            $request->query->get('code'),
            $this->getUser()?->getId(),
            $application,
            $this->urlGenerator->generate('your_callback_route', [
                'applicationId' => $application->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL)
        );

        // 如果需要，处理来自状态的回调 URL
        $state = $request->query->get('state');
        $callbackUrl = $state ? $this->oauthService->verifyState($state) : null;

        if ($callbackUrl) {
            $callbackUrl = strtr($callbackUrl, [
                '{accessToken}' => $token->getAccessToken(),
                '{userId}' => $token->getUserId(),
                '{giteeUsername}' => $token->getGiteeUsername() ?? '',
            ]);

            return new RedirectResponse($callbackUrl);
        }

        return $this->redirectToRoute('your_success_route');
    }
}
```

## 高级用法

### 令牌管理

对于需要多个认证工作流或令牌管理的应用程序：

```php
// 获取用户-应用程序对的所有令牌
$tokens = $tokenRepository->findByUserAndApplication($userId, $applicationId);

// 获取最新的有效令牌
$latestToken = $tokenRepository->findLatestValidToken($userId, $applicationId);

// 手动刷新令牌
$refreshedToken = $oauthService->refreshToken($token);
```

### 自定义 API 端点

扩展 API 客户端以支持自定义端点：

```php
class CustomGiteeApiClient extends GiteeApiClient
{
    public function getCustomData(string $userId, GiteeApplication $application): array
    {
        return $this->request('GET', '/v5/custom-endpoint', [], $userId, $application);
    }
}
```

### 仓库过滤

根据条件过滤同步的仓库：

```php
// 只同步公开仓库
$publicRepos = array_filter($repositories, fn($repo) => !$repo['private']);

// 只同步特定语言的仓库
$phpRepos = array_filter($repositories, fn($repo) => $repo['language'] === 'PHP');
```

## 数据模型

该包提供三个主要实体：

### GiteeApplication

存储 Gitee OAuth 应用程序配置：
- 客户端 ID 和密钥
- OAuth 权限范围
- 应用程序元数据

### GiteeAccessToken

管理用户 OAuth 令牌：
- 访问令牌和刷新令牌
- 令牌过期时间
- 用户-应用程序关联
- 支持每个用户多个令牌

### GiteeRepository

缓存仓库信息：
- 仓库元数据
- 所有者和权限
- 克隆 URL
- 最后推送时间戳

## 服务架构

该包提供几个服务：

- **GiteeApiClient** - 用于 Gitee API 请求的 HTTP 客户端
- **GiteeOAuthService** - OAuth 流程管理
- **GiteeRepositoryService** - 仓库数据管理
- **Repository 类** - 用于数据访问的 Doctrine repositories

## 安全性

### 令牌安全

- 在数据库中安全存储令牌，如需要请进行适当加密
- 永远不要在日志或错误消息中暴露访问令牌
- 为长期运行的应用程序实现令牌轮换
- 对所有 OAuth 重定向和 API 调用使用 HTTPS

### 输入验证

- 所有实体属性都包含验证约束
- API 响应在处理前进行验证
- OAuth 状态参数包含 CSRF 保护

### 权限管理

- 仅请求所需的最小权限范围
- 在 API 调用前验证权限要求
- 允许用户查看和撤销权限

### 速率限制

- 遵守 Gitee 的 API 速率限制
- 对失败请求实现指数退避
- 监控 API 使用模式

## 错误处理

该包对 API 相关错误抛出 `GiteeApiException`。始终在 try-catch 块中包装 API 调用：

```php
use GiteeApiBundle\Exception\GiteeApiException;

try {
    $user = $giteeApiClient->getUser($userId, $application);
} catch (GiteeApiException $e) {
    // 处理 API 错误
    $logger->error('Gitee API error: ' . $e->getMessage());
}
```

## 测试

运行测试套件：

```bash
# 运行所有测试
vendor/bin/phpunit packages/gitee-api-bundle/tests

# 运行覆盖率测试
vendor/bin/phpunit packages/gitee-api-bundle/tests --coverage-html coverage
```

## 贡献

欢迎贡献！请：

1. Fork 仓库
2. 创建功能分支
3. 进行更改
4. 运行测试并确保通过
5. 提交拉取请求

请遵循 PSR-12 编码标准并为新功能编写测试。

## 许可证

此包是在 [MIT 许可证](LICENSE) 下授权的开源软件。