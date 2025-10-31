<?php

namespace GiteeApiBundle\Tests\Controller;

use GiteeApiBundle\Controller\GiteeOAuthConnectController;
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
#[CoversClass(GiteeOAuthConnectController::class)]
#[RunTestsInSeparateProcesses]
final class GiteeOAuthConnectControllerTest extends AbstractWebTestCase
{
    public function testConnectRedirectsToGiteeOAuth(): void
    {
        $client = self::createClientWithDatabase();

        $application = $this->createGiteeApplication();

        $client->request('GET', '/gitee/oauth/connect/' . $application->getId());

        $this->assertEquals(Response::HTTP_FOUND, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect());

        $location = $client->getResponse()->headers->get('Location');
        $this->assertNotNull($location);
        $this->assertStringContainsString('gitee.com/oauth/authorize', $location);
    }

    public function testConnectWithCallbackUrl(): void
    {
        $client = self::createClientWithDatabase();

        $application = $this->createGiteeApplication();

        $client->request('GET', '/gitee/oauth/connect/' . $application->getId(), [
            'callbackUrl' => 'https://example.com/callback',
        ]);

        $this->assertEquals(Response::HTTP_FOUND, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect());

        $location = $client->getResponse()->headers->get('Location');
        $this->assertNotNull($location);
        $this->assertStringContainsString('gitee.com/oauth/authorize', $location);
        $this->assertStringContainsString('state=', $location);
    }

    public function testConnectWithUnauthorizedAccess(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(NotFoundHttpException::class);
        $client->request('GET', '/gitee/oauth/connect/999');
    }

    public function testConnectPostMethod(): void
    {
        $client = self::createClientWithDatabase();

        $application = $this->createGiteeApplication();

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('POST', '/gitee/oauth/connect/' . $application->getId());
    }

    public function testConnectPutMethod(): void
    {
        $client = self::createClientWithDatabase();

        $application = $this->createGiteeApplication();

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('PUT', '/gitee/oauth/connect/' . $application->getId());
    }

    public function testConnectDeleteMethod(): void
    {
        $client = self::createClientWithDatabase();

        $application = $this->createGiteeApplication();

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('DELETE', '/gitee/oauth/connect/' . $application->getId());
    }

    public function testConnectPatchMethod(): void
    {
        $client = self::createClientWithDatabase();

        $application = $this->createGiteeApplication();

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('PATCH', '/gitee/oauth/connect/' . $application->getId());
    }

    public function testConnectHeadMethod(): void
    {
        $client = self::createClientWithDatabase();

        $application = $this->createGiteeApplication();

        $client->request('HEAD', '/gitee/oauth/connect/' . $application->getId());

        $this->assertEquals(Response::HTTP_FOUND, $client->getResponse()->getStatusCode());
    }

    public function testConnectOptionsMethod(): void
    {
        $client = self::createClientWithDatabase();

        $application = $this->createGiteeApplication();

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request('OPTIONS', '/gitee/oauth/connect/' . $application->getId());
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();

        $application = $this->createGiteeApplication();

        // Test the HTTP method - expect MethodNotAllowedHttpException
        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request($method, '/gitee/oauth/connect/' . $application->getId());
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
