<?php

namespace GiteeApiBundle\Service;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use GiteeApiBundle\Entity\GiteeAccessToken;
use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Exception\GiteeUserInfoException;
use GiteeApiBundle\Repository\GiteeAccessTokenRepository;
use Psr\SimpleCache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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
    ) {
    }

    /**
     * 获取授权URL
     *
     * @param GiteeApplication $application 应用实例
     * @param string $redirectUri 重定向URI
     * @param string|null $callbackUrl 回调URL
     */
    public function getAuthorizationUrl(GiteeApplication $application, string $redirectUri, ?string $callbackUrl = null): string
    {
        $state = bin2hex(random_bytes(16));

        if ($callbackUrl !== null) {
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
        // Exchange code for access token
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

        // Get user info
        $userResponse = $this->httpClient->request('GET', self::USER_URL, [
            'headers' => [
                'Authorization' => 'Bearer ' . $data['access_token'],
            ],
        ]);

        $userData = $userResponse->toArray();
        $giteeUsername = $userData['login'] ?? throw GiteeUserInfoException::failedToGetUsername();

        // Create new token
        $token = new GiteeAccessToken();
        $token->setApplication($application)
            ->setUserId($giteeUsername)
            ->setAccessToken($data['access_token'])
            ->setRefreshToken($data['refresh_token'] ?? null)
            ->setExpiresAt(isset($data['expires_in']) ? new DateTimeImmutable('+' . $data['expires_in'] . ' seconds') : null)
            ->setGiteeUsername($giteeUsername);

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return $token;
    }

    public function refreshToken(GiteeAccessToken $token): GiteeAccessToken
    {
        $response = $this->httpClient->request('POST', self::TOKEN_URL, [
            'body' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $token->getRefreshToken(),
                'client_id' => $token->getApplication()->getClientId(),
                'client_secret' => $token->getApplication()->getClientSecret(),
            ],
        ]);

        $data = $response->toArray();

        // Create new token instead of updating
        $newToken = new GiteeAccessToken();
        $newToken->setApplication($token->getApplication())
            ->setUserId($token->getUserId())
            ->setAccessToken($data['access_token'])
            ->setRefreshToken($data['refresh_token'] ?? null)
            ->setExpiresAt(isset($data['expires_in']) ? new DateTimeImmutable('+' . $data['expires_in'] . ' seconds') : null)
            ->setGiteeUsername($token->getGiteeUsername());

        $this->entityManager->persist($newToken);
        $this->entityManager->flush();

        return $newToken;
    }

    public function getAccessToken(string $userId, GiteeApplication $application): ?GiteeAccessToken
    {
        $tokens = $this->tokenRepository->findBy(
            ['userId' => $userId, 'application' => $application],
            ['createdAt' => 'DESC']
        );

        if (empty($tokens)) {
            return null;
        }

        $token = $tokens[0];
        if ($token->getExpiresAt() !== null && $token->getExpiresAt() < new DateTimeImmutable() && $token->getRefreshToken() !== null) {
            return $this->refreshToken($token);
        }

        return $token;
    }
}
