<?php

namespace GiteeApiBundle\Tests\Controller;

use GiteeApiBundle\Controller\GiteeOAuthCallbackController;
use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Enum\GiteeScope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(GiteeOAuthCallbackController::class)]
#[RunTestsInSeparateProcesses]
final class GiteeOAuthCallbackControllerTest extends AbstractWebTestCase
{
    public function testCallbackWithoutCode(): void
    {
        $client = self::createClientWithDatabase();

        $application = $this->createGiteeApplication();

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('No authorization code provided');
        $client->request('GET', '/gitee/oauth/callback/' . $application->getId());
    }

    public function testCallbackWithEmptyCode(): void
    {
        $client = self::createClientWithDatabase();

        $application = $this->createGiteeApplication();

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('No authorization code provided');
        $client->request('GET', '/gitee/oauth/callback/' . $application->getId(), ['code' => '']);
    }

    public function testCallbackWithUnauthorizedAccess(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(NotFoundHttpException::class);
        $client->request('GET', '/gitee/oauth/callback/999');
    }

    public function testCallbackPostMethod(): void
    {
        $client = self::createClientWithDatabase();

        $application = $this->createGiteeApplication();

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('POST', '/gitee/oauth/callback/' . $application->getId());
    }

    public function testCallbackPutMethod(): void
    {
        $client = self::createClientWithDatabase();

        $application = $this->createGiteeApplication();

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('PUT', '/gitee/oauth/callback/' . $application->getId());
    }

    public function testCallbackDeleteMethod(): void
    {
        $client = self::createClientWithDatabase();

        $application = $this->createGiteeApplication();

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('DELETE', '/gitee/oauth/callback/' . $application->getId());
    }

    public function testCallbackPatchMethod(): void
    {
        $client = self::createClientWithDatabase();

        $application = $this->createGiteeApplication();

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('PATCH', '/gitee/oauth/callback/' . $application->getId());
    }

    public function testCallbackHeadMethod(): void
    {
        $client = self::createClientWithDatabase();

        $application = $this->createGiteeApplication();

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('No authorization code provided');
        $client->request('HEAD', '/gitee/oauth/callback/' . $application->getId());
    }

    public function testCallbackOptionsMethod(): void
    {
        $client = self::createClientWithDatabase();

        $application = $this->createGiteeApplication();

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('OPTIONS', '/gitee/oauth/callback/' . $application->getId());
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();

        $application = $this->createGiteeApplication();

        // Test the HTTP method - expect MethodNotAllowedHttpException
        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request($method, '/gitee/oauth/callback/' . $application->getId());
    }

    private function createGiteeApplication(): GiteeApplication
    {
        $application = new GiteeApplication();
        $application->setName('Test App');
        $application->setClientId('test_client_id');
        $application->setClientSecret('test_client_secret');
        $application->setScopes([GiteeScope::USER, GiteeScope::PROJECTS]);

        self::getEntityManager()->persist($application);
        self::getEntityManager()->flush();

        return $application;
    }
}
