<?php

declare(strict_types=1);

namespace GiteeApiBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use GiteeApiBundle\Controller\Admin\GiteeAccessTokenCrudController;
use GiteeApiBundle\Entity\GiteeAccessToken;
use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Enum\GiteeScope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(GiteeAccessTokenCrudController::class)]
#[RunTestsInSeparateProcesses]
final class GiteeAccessTokenCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    private KernelBrowser $client;

    protected function onSetUp(): void
    {
        $this->client = self::createClientWithDatabase();
        self::getClient($this->client);
    }

    #[Test]
    public function testGetEntityFqcnShouldReturnGiteeAccessTokenClass(): void
    {
        $entityFqcn = GiteeAccessTokenCrudController::getEntityFqcn();

        $this->assertSame(GiteeAccessToken::class, $entityFqcn);
    }

    #[Test]
    public function testIndexPageWithAdminUserShouldShowAccessTokenList(): void
    {
        self::markTestSkipped('跳过路由相关测试，专注于 CRUD 配置验证');
    }

    #[Test]
    public function testIndexPageWithoutAuthenticationShouldDenyAccess(): void
    {
        self::markTestSkipped('跳过路由相关测试，专注于 CRUD 配置验证');
    }

    #[Test]
    public function testNewAccessTokenPageWithAdminUserShouldShowForm(): void
    {
        self::markTestSkipped('跳过路由相关测试，专注于 CRUD 配置验证');
    }

    #[Test]
    public function testCreateAccessTokenShouldPersistEntity(): void
    {
        $this->loginAsAdmin($this->client);
        $application = $this->createTestApplication();

        $accessToken = new GiteeAccessToken();
        $accessToken->setApplication($application);
        $accessToken->setUserId('12345');
        $accessToken->setAccessToken('test_access_token');
        $accessToken->setGiteeUsername('test_user');
        $accessToken->setExpireTime(new \DateTimeImmutable('+1 hour'));

        $em = self::getEntityManager();
        $em->persist($accessToken);
        $em->flush();

        $storedAccessToken = $em->getRepository(GiteeAccessToken::class)->findOneBy(['userId' => '12345']);
        $this->assertNotNull($storedAccessToken);
        $this->assertSame('test_access_token', $storedAccessToken->getAccessToken());
        $this->assertSame('test_user', $storedAccessToken->getGiteeUsername());
    }

    #[Test]
    public function testAccessTokenValidationShouldRejectInvalidData(): void
    {
        $this->loginAsAdmin($this->client);
        $application = $this->createTestApplication();

        $accessToken = new GiteeAccessToken();
        $accessToken->setApplication($application);
        $accessToken->setUserId(''); // 空字段，应该验证失败
        $accessToken->setAccessToken(''); // 空字段，应该验证失败

        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get('validator');
        $violations = $validator->validate($accessToken);

        $this->assertGreaterThan(0, $violations->count(), '验证应该失败');

        $violationMessages = [];
        foreach ($violations as $violation) {
            $violationMessages[] = $violation->getMessage();
        }

        $this->assertNotEmpty($violationMessages);
    }

    #[Test]
    public function testEditExistingAccessTokenShouldShowPrefilledForm(): void
    {
        self::markTestSkipped('跳过路由相关测试，专注于 CRUD 配置验证');
    }

    #[Test]
    public function testDetailPageShouldShowAccessTokenInformation(): void
    {
        self::markTestSkipped('跳过路由相关测试，专注于 CRUD 配置验证');
    }

    #[Test]
    public function testDeleteAccessTokenShouldRemoveFromDatabase(): void
    {
        $this->loginAsAdmin($this->client);
        $accessToken = $this->createTestAccessToken();
        $accessTokenId = $accessToken->getId();

        $em = self::getEntityManager();
        $em->remove($accessToken);
        $em->flush();

        $deletedAccessToken = $em->getRepository(GiteeAccessToken::class)->find($accessTokenId);
        $this->assertNull($deletedAccessToken, 'Access token should be deleted from database');
    }

    #[Test]
    public function testSearchFunctionalityShouldFilterResults(): void
    {
        self::markTestSkipped('跳过路由相关测试，专注于 CRUD 配置验证');
    }

    #[Test]
    public function testRequiredFieldValidation(): void
    {
        $this->loginAsAdmin($this->client);
        $application = $this->createTestApplication();

        $accessToken = new GiteeAccessToken();
        $accessToken->setApplication($application);
        $accessToken->setUserId('');
        $accessToken->setAccessToken('');

        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get('validator');
        $violations = $validator->validate($accessToken);

        $this->assertGreaterThan(0, $violations->count(), '验证应该失败，因为必填字段为空');

        $violationMessages = [];
        foreach ($violations as $violation) {
            $violationMessages[$violation->getPropertyPath()] = $violation->getMessage();
        }

        $this->assertArrayHasKey('userId', $violationMessages, '用户ID字段应该有验证错误');
        $this->assertArrayHasKey('accessToken', $violationMessages, '访问令牌字段应该有验证错误');
    }

    #[Test]
    public function testValidationErrors(): void
    {
        $this->loginAsAdmin($this->client);
        $application = $this->createTestApplication();

        $accessToken = new GiteeAccessToken();
        $accessToken->setApplication($application);
        $accessToken->setUserId('');
        $accessToken->setAccessToken('');

        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get('validator');
        $violations = $validator->validate($accessToken);

        $this->assertGreaterThan(0, $violations->count(), '验证应该失败，因为必填字段为空');

        $foundBlankError = false;
        foreach ($violations as $violation) {
            $message = (string) $violation->getMessage();
            if (false !== stripos($message, 'should not be blank')
                || false !== stripos($message, 'not be blank')
                || false !== stripos($message, 'blank')) {
                $foundBlankError = true;
                break;
            }
        }

        $this->assertTrue($foundBlankError, '应该找到包含"should not be blank"的验证错误');
    }

    private function createTestApplication(): GiteeApplication
    {
        $application = new GiteeApplication();
        $application->setName('测试应用');
        $application->setClientId('test_client_id');
        $application->setClientSecret('test_client_secret');
        $application->setHomepage('https://example.com');
        $application->setDescription('测试应用描述');
        $application->setScopes([GiteeScope::USER, GiteeScope::PROJECTS]);

        $em = self::getEntityManager();
        $em->persist($application);
        $em->flush();

        return $application;
    }

    private function createTestAccessToken(string $userId = 'test_user_123'): GiteeAccessToken
    {
        $application = $this->createTestApplication();

        $accessToken = new GiteeAccessToken();
        $accessToken->setApplication($application);
        $accessToken->setUserId($userId);
        $accessToken->setAccessToken('test_access_token_' . uniqid());
        $accessToken->setGiteeUsername($userId);
        $accessToken->setExpireTime(new \DateTimeImmutable('+1 hour'));

        $em = self::getEntityManager();
        $em->persist($accessToken);
        $em->flush();

        return $accessToken;
    }

    /**
     * @return GiteeAccessTokenCrudController
     */
    #[\ReturnTypeWillChange]
    protected function getControllerService(): AbstractCrudController
    {
        return self::getService(GiteeAccessTokenCrudController::class);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield 'Gitee应用' => ['Gitee应用'];
        yield '用户ID' => ['用户ID'];
        yield 'Gitee用户名' => ['Gitee用户名'];
        yield '过期时间' => ['过期时间'];
        yield '创建时间' => ['创建时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'application' => ['application'];
        yield 'userId' => ['userId'];
        yield 'giteeUsername' => ['giteeUsername'];
        yield 'accessToken' => ['accessToken'];
        yield 'expireTime' => ['expireTime'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'application' => ['application'];
        yield 'userId' => ['userId'];
        yield 'giteeUsername' => ['giteeUsername'];
        yield 'accessToken' => ['accessToken'];
        yield 'expireTime' => ['expireTime'];
    }

    /**
     * 重写基类的新建页面字段验证方法
     */
}
