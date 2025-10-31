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

A comprehensive Gitee API integration bundle for Symfony applications, providing OAuth authentication, API client, 
repository synchronization, and data management features.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Requirements](#requirements)
- [Configuration](#configuration)
- [Usage](#usage)
  - [OAuth Authentication](#oauth-authentication)
  - [Multiple Tokens](#multiple-tokens)
  - [API Calls](#api-calls)
  - [Console Commands](#console-commands)
  - [Customizing the OAuth Flow](#customizing-the-oauth-flow)
- [Advanced Usage](#advanced-usage)
- [Data Model](#data-model)
- [Service Architecture](#service-architecture)
- [Security](#security)
- [Error Handling](#error-handling)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

## Features

- 🔐 **OAuth 2.0 Authentication** - Full OAuth flow with multiple token support per user
- 🔌 **Comprehensive API Client** - Access user info, repositories, branches, issues, and pull requests
- 🔄 **Repository Synchronization** - Sync and cache repository data locally
- 🎛️ **Flexible Scope Management** - Configure OAuth scopes per application
- 🏗️ **Doctrine Integration** - Entities for applications, tokens, and repositories
- 🛠️ **Console Commands** - CLI tools for data management
- 🎨 **Customizable Controllers** - Extend or replace default OAuth controllers
- 📦 **Auto-configuration** - Symfony Flex recipe for quick setup

## Installation

1. Add the bundle to your project:

```bash
composer require tourze/gitee-api-bundle
```

## Requirements

- PHP 8.1 or higher
- Symfony 6.4 or higher
- Doctrine ORM 3.0 or higher

## Configuration

1. Create and configure your Gitee OAuth application in the database:

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

By default, applications are configured with the following scopes:

- `user_info`: Access user information
- `projects`: Access repositories
- `pull_requests`: Access pull requests
- `issues`: Access issues
- `notes`: Access comments

Additional available scopes:

- `enterprises`: Access enterprise information
- `gists`: Access gists
- `groups`: Access groups
- `hook`: Access webhooks

2. Configure the callback URL in your Gitee application settings:

```text
https://your-domain.com/gitee/oauth/callback/{applicationId}
```

## Usage

### OAuth Authentication

The bundle provides built-in controllers for handling the OAuth flow. To start the authentication process, 
redirect users to:

```text
/gitee/oauth/connect/{applicationId}?callbackUrl={successUrl}
```

The `callbackUrl` parameter supports the following template variables:

- `{accessToken}`: The OAuth access token
- `{userId}`: The user ID (Gitee username if no user is authenticated)
- `{giteeUsername}`: The Gitee username
- `{applicationId}`: The application ID

Example usage in your templates:

```html
{# Basic usage #}
<a href="{{ path('gitee_oauth_connect', {applicationId: application.id}) }}">
    Connect with Gitee
</a>

{# With custom callback URL #}
<a href="{{ path('gitee_oauth_connect', {
    applicationId: application.id,
    callbackUrl: 'https://your-domain.com/success?token={accessToken}&user={giteeUsername}'
}) }}">
    Connect with Gitee
</a>
```

The bundle will:

1. Redirect users to Gitee's authorization page with configured scopes
2. Handle the OAuth callback
3. Store the access token
4. Redirect to the specified callback URL with template variables replaced, or to the homepage route if no 
   callback URL is provided

### Multiple Tokens

The bundle now supports multiple tokens per user per application. Each time a user authorizes the 
application, a new token is created instead of updating the existing one. When using the API, the most 
recently created valid token will be used.

### API Calls

```php
// Get user information (requires user_info scope)
$user = $giteeApiClient->getUser($userId, $application);

// Get user repositories (requires projects scope)
$repos = $giteeApiClient->getRepositories($userId, $application);

// Get repository details (requires projects scope)
$repo = $giteeApiClient->getRepository('owner', 'repo', $userId, $application);

// Get repository branches (requires projects scope)
$branches = $giteeApiClient->getBranches('owner', 'repo', $userId, $application);

// Get repository issues (requires issues scope)
$issues = $giteeApiClient->getIssues('owner', 'repo', ['state' => 'open'], $userId, $application);

// Get repository pull requests (requires pull_requests scope)
$prs = $giteeApiClient->getPullRequests('owner', 'repo', ['state' => 'open'], $userId, $application);
```

### Console Commands

The bundle provides console commands to manage Gitee data:

#### gitee:sync:repositories

Synchronizes a user's Gitee repositories with the local database.

```bash
# Sync repositories for a specific user and application
php bin/console gitee:sync:repositories {userId} {applicationId}

# Force update all repositories (even if they haven't changed)
php bin/console gitee:sync:repositories {userId} {applicationId} --force
```

**Arguments:**
- `userId`: The user ID to sync repositories for
- `applicationId`: The ID of the Gitee application to use

**Options:**
- `--force` (`-f`): Force update all repository information even if no changes detected

The command will:
1. Fetch all repositories for the specified user using the application's OAuth token
2. Compare with existing local repository data
3. Create new repository records or update existing ones if changes are detected
4. Skip repositories that haven't been updated since the last sync (unless `--force` is used)
5. Display progress and summary statistics

## Customizing the OAuth Flow

The default controllers provide a basic OAuth flow. You can customize the flow by:

1. Creating your own controller that extends the default one:

```php
use GiteeApiBundle\Controller\OAuthController;

class CustomOAuthController extends OAuthController
{
    public function callback(Request $request, GiteeApplication $application): Response
    {
        $token = parent::callback($request, $application);

        // Add your custom logic here

        return $this->redirectToRoute('your_custom_route');
    }
}
```

2. Or implementing the flow entirely in your own controller using the services:

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

        // Handle the callback URL from state if needed
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

## Advanced Usage

### Token Management

For applications requiring multiple authentication workflows or token management:

```php
// Get all tokens for a user-application pair
$tokens = $tokenRepository->findByUserAndApplication($userId, $applicationId);

// Get the most recent valid token
$latestToken = $tokenRepository->findLatestValidToken($userId, $applicationId);

// Manually refresh a token
$refreshedToken = $oauthService->refreshToken($token);
```

### Custom API Endpoints

Extend the API client for custom endpoints:

```php
class CustomGiteeApiClient extends GiteeApiClient
{
    public function getCustomData(string $userId, GiteeApplication $application): array
    {
        return $this->request('GET', '/v5/custom-endpoint', [], $userId, $application);
    }
}
```

### Repository Filtering

Filter synchronized repositories based on criteria:

```php
// Only sync public repositories
$publicRepos = array_filter($repositories, fn($repo) => !$repo['private']);

// Only sync repositories with specific languages
$phpRepos = array_filter($repositories, fn($repo) => $repo['language'] === 'PHP');
```

## Data Model

The bundle provides three main entities:

### GiteeApplication

Stores Gitee OAuth application configuration:
- Client ID and secret
- OAuth scopes
- Application metadata

### GiteeAccessToken

Manages user OAuth tokens:
- Access and refresh tokens
- Token expiration
- User-application associations
- Support for multiple tokens per user

### GiteeRepository

Caches repository information:
- Repository metadata
- Owner and permissions
- Clone URLs
- Last push timestamps

## Service Architecture

The bundle provides several services:

- **GiteeApiClient** - HTTP client for Gitee API requests
- **GiteeOAuthService** - OAuth flow management
- **GiteeRepositoryService** - Repository data management
- **Repository classes** - Doctrine repositories for data access

## Security

### Token Security

- Store tokens securely in the database with proper encryption if needed
- Never expose access tokens in logs or error messages
- Implement token rotation for long-lived applications
- Use HTTPS for all OAuth redirects and API calls

### Input Validation

- All entity properties include validation constraints
- API responses are validated before processing
- OAuth state parameters include CSRF protection

### Scope Management

- Request only the minimum required scopes
- Validate scope requirements before API calls
- Allow users to review and revoke permissions

### Rate Limiting

- Respect Gitee's API rate limits
- Implement exponential backoff for failed requests
- Monitor API usage patterns

## Error Handling

The bundle throws `GiteeApiException` for API-related errors. Always wrap API calls in try-catch blocks:

```php
use GiteeApiBundle\Exception\GiteeApiException;

try {
    $user = $giteeApiClient->getUser($userId, $application);
} catch (GiteeApiException $e) {
    // Handle API errors
    $logger->error('Gitee API error: ' . $e->getMessage());
}
```

## Testing

Run the test suite:

```bash
# Run all tests
vendor/bin/phpunit packages/gitee-api-bundle/tests

# Run with coverage
vendor/bin/phpunit packages/gitee-api-bundle/tests --coverage-html coverage
```

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests and ensure they pass
5. Submit a pull request

Please follow PSR-12 coding standards and write tests for new features.

## License

This bundle is open-sourced software licensed under the [MIT license](LICENSE).