<?php

declare(strict_types=1);

namespace GiteeApiBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use GiteeApiBundle\Entity\GiteeAccessToken;
use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Exception\GiteeUserInfoException;
use GiteeApiBundle\Repository\GiteeAccessTokenRepository;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Autoconfigure(public: true)]
#[WithMonologChannel(channel: 'gitee_api')]
final class GiteeOAuthService
{
    private const AUTHORIZE_URL = 'https://gitee.com/oauth/authorize';
    private const TOKEN_URL = 'https://gitee.com/oauth/token';
    private const USER_URL = 'https://gitee.com/api/v5/user';
    private const STATE_TTL = 3600; // 1 hour

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly GiteeAccessTokenRepository $tokenRepository,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * 获取授权URL
     *
     * @param GiteeApplication $application 应用实例
     * @param string           $redirectUri 重定向URI
     * @param string|null      $callbackUrl 回调URL
     */
    public function getAuthorizationUrl(GiteeApplication $application, string $redirectUri, ?string $callbackUrl = null): string
    {
        $state = bin2hex(random_bytes(16));

        if (null !== $callbackUrl) {
            $this->cache->set("gitee_oauth_state_{$state}", $callbackUrl, self::STATE_TTL);
        }

        $params = [
            'client_id' => $application->getClientId(),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $application->getScopesAsString(),
            'state' => $state,
        ];

        return self::AUTHORIZE_URL . '?' . http_build_query($params);
    }

    public function verifyState(string $state): ?string
    {
        $key = "gitee_oauth_state_{$state}";
        $callbackUrl = $this->cache->get($key);
        $this->cache->delete($key);

        return $callbackUrl;
    }

    public function handleCallback(string $code, GiteeApplication $application, string $redirectUri): GiteeAccessToken
    {
        $startTime = microtime(true);

        $this->logger->info('Gitee OAuth callback processing started', [
            'code' => substr($code, 0, 8) . '...',
            'applicationId' => $application->getId(),
            'redirectUri' => $redirectUri,
        ]);

        try {
            // Exchange code for access token
            $tokenStartTime = microtime(true);
            $response = $this->httpClient->request('POST', self::TOKEN_URL, [
                'body' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'client_id' => $application->getClientId(),
                    'client_secret' => $application->getClientSecret(),
                    'redirect_uri' => $redirectUri,
                ],
            ]);

            $data = $response->toArray();
            $tokenDuration = microtime(true) - $tokenStartTime;

            $this->logger->info('Gitee OAuth token exchange successful', [
                'applicationId' => $application->getId(),
                'statusCode' => $response->getStatusCode(),
                'duration' => round($tokenDuration, 3),
                'hasAccessToken' => isset($data['access_token']),
                'hasRefreshToken' => isset($data['refresh_token']),
                'expiresIn' => $data['expires_in'] ?? null,
            ]);

            // Get user info
            $userStartTime = microtime(true);
            $userResponse = $this->httpClient->request('GET', self::USER_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $data['access_token'],
                ],
            ]);

            $userData = $userResponse->toArray();
            $userDuration = microtime(true) - $userStartTime;

            $this->logger->info('Gitee user info request successful', [
                'statusCode' => $userResponse->getStatusCode(),
                'duration' => round($userDuration, 3),
                'login' => $userData['login'] ?? null,
            ]);

            $giteeUsername = $userData['login'] ?? throw GiteeUserInfoException::failedToGetUsername();

            // Create new token
            $token = new GiteeAccessToken();
            $token->setApplication($application);
            $token->setUserId($giteeUsername);
            $token->setAccessToken($data['access_token']);
            $token->setRefreshToken($data['refresh_token'] ?? null);
            $token->setExpireTime(isset($data['expires_in']) ? new \DateTimeImmutable('+' . $data['expires_in'] . ' seconds') : null);
            $token->setGiteeUsername($giteeUsername);

            $this->entityManager->persist($token);
            $this->entityManager->flush();

            $totalDuration = microtime(true) - $startTime;

            $this->logger->info('Gitee OAuth callback processing completed', [
                'applicationId' => $application->getId(),
                'giteeUsername' => $giteeUsername,
                'totalDuration' => round($totalDuration, 3),
            ]);

            return $token;
        } catch (\Throwable $e) {
            $totalDuration = microtime(true) - $startTime;

            $this->logger->error('Gitee OAuth callback processing failed', [
                'applicationId' => $application->getId(),
                'totalDuration' => round($totalDuration, 3),
                'error' => $e->getMessage(),
                'errorClass' => get_class($e),
            ]);

            throw $e;
        }
    }

    public function refreshToken(GiteeAccessToken $token): GiteeAccessToken
    {
        $startTime = microtime(true);

        $this->logger->info('Gitee OAuth token refresh started', [
            'userId' => $token->getUserId(),
            'applicationId' => $token->getApplication()->getId(),
        ]);

        try {
            $response = $this->httpClient->request('POST', self::TOKEN_URL, [
                'body' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $token->getRefreshToken(),
                    'client_id' => $token->getApplication()->getClientId(),
                    'client_secret' => $token->getApplication()->getClientSecret(),
                ],
            ]);

            $data = $response->toArray();
            $duration = microtime(true) - $startTime;

            $this->logger->info('Gitee OAuth token refresh successful', [
                'userId' => $token->getUserId(),
                'applicationId' => $token->getApplication()->getId(),
                'statusCode' => $response->getStatusCode(),
                'duration' => round($duration, 3),
                'hasAccessToken' => isset($data['access_token']),
                'hasRefreshToken' => isset($data['refresh_token']),
                'expiresIn' => $data['expires_in'] ?? null,
            ]);

            // Create new token instead of updating
            $newToken = new GiteeAccessToken();
            $newToken->setApplication($token->getApplication());
            $newToken->setUserId($token->getUserId());
            $newToken->setAccessToken($data['access_token']);
            $newToken->setRefreshToken($data['refresh_token'] ?? null);
            $newToken->setExpireTime(isset($data['expires_in']) ? new \DateTimeImmutable('+' . $data['expires_in'] . ' seconds') : null);
            $newToken->setGiteeUsername($token->getGiteeUsername());

            $this->entityManager->persist($newToken);
            $this->entityManager->flush();

            return $newToken;
        } catch (\Throwable $e) {
            $duration = microtime(true) - $startTime;

            $this->logger->error('Gitee OAuth token refresh failed', [
                'userId' => $token->getUserId(),
                'applicationId' => $token->getApplication()->getId(),
                'duration' => round($duration, 3),
                'error' => $e->getMessage(),
                'errorClass' => get_class($e),
            ]);

            throw $e;
        }
    }

    public function getAccessToken(string $userId, GiteeApplication $application): ?GiteeAccessToken
    {
        /** @var GiteeAccessToken[] $tokens */
        $tokens = $this->tokenRepository->findBy(
            ['userId' => $userId, 'application' => $application],
            ['createTime' => 'DESC']
        );

        if ([] === $tokens) {
            return null;
        }

        $token = $tokens[0];
        if (null !== $token->getExpireTime() && $token->getExpireTime() < new \DateTimeImmutable() && null !== $token->getRefreshToken()) {
            return $this->refreshToken($token);
        }

        return $token;
    }
}
