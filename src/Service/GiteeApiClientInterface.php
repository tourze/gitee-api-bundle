<?php

declare(strict_types=1);

namespace GiteeApiBundle\Service;

use GiteeApiBundle\Entity\GiteeApplication;

interface GiteeApiClientInterface
{
    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function request(string $method, string $path, array $options = [], ?string $userId = null, ?GiteeApplication $application = null): array;

    /** @return array<string, mixed> */
    public function getUser(string $userId, GiteeApplication $application): array;

    /**
     * @param array<string, mixed> $params
     * @return array<array-key, array<string, mixed>>
     */
    public function getRepositories(string $userId, GiteeApplication $application, array $params = []): array;

    /** @return array<string, mixed> */
    public function getRepository(string $owner, string $repo, ?string $userId = null, ?GiteeApplication $application = null): array;

    /** @return array<array-key, array<string, mixed>> */
    public function getBranches(string $owner, string $repo, ?string $userId = null, ?GiteeApplication $application = null): array;

    /**
     * @param array<string, mixed> $params
     * @return array<array-key, array<string, mixed>>
     */
    public function getIssues(string $owner, string $repo, array $params = [], ?string $userId = null, ?GiteeApplication $application = null): array;

    /**
     * @param array<string, mixed> $params
     * @return array<array-key, array<string, mixed>>
     */
    public function getPullRequests(string $owner, string $repo, array $params = [], ?string $userId = null, ?GiteeApplication $application = null): array;
}
