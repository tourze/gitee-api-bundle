# GiteeApiBundle

Gitee API integration for Symfony applications.

## Installation

1. Add the bundle to your project:

```bash
composer require tourze/gitee-api-bundle
```

2. Create and configure your Gitee OAuth application in the database:

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

3. Configure the callback URL in your Gitee application settings:

```
https://your-domain.com/gitee/oauth/callback/{applicationId}
```

## Usage

### OAuth Authentication

The bundle provides built-in controllers for handling the OAuth flow. To start the authentication process, redirect users to:

```
/gitee/oauth/connect/{applicationId}?callbackUrl={successUrl}
```

The `callbackUrl` parameter supports the following template variables:

- `{accessToken}`: The OAuth access token
- `{userId}`: The user ID (Gitee username if no user is authenticated)
- `{giteeUsername}`: The Gitee username
- `{applicationId}`: The application ID

Example usage in your templates:

```twig
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
4. Redirect to the specified callback URL with template variables replaced, or to the homepage route if no callback URL is provided

### Multiple Tokens

The bundle now supports multiple tokens per user per application. Each time a user authorizes the application, a new token is created instead of updating the existing one. When using the API, the most recently created valid token will be used.

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

### Customizing the OAuth Flow

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
