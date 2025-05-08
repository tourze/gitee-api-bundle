<?php

namespace GiteeApiBundle\Service;

use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Exception\GiteeApiException;

class GiteeRepositoryService
{
    public function __construct(
        private readonly GiteeApiClient $giteeClient,
    ) {
    }

    /**
     * 获取用户的仓库列表
     *
     * @param string $userId 用户ID
     * @param GiteeApplication $application 应用
     * @param array $params 额外的查询参数
     * @return array 仓库列表
     * @throws GiteeApiException
     */
    public function getRepositories(string $userId, GiteeApplication $application, array $params = []): array
    {
        return $this->giteeClient->request('GET', '/user/repos', [
            'query' => array_merge([
                'sort' => 'pushed',
                'direction' => 'desc',
                'per_page' => 100,
                'page' => 1,
            ], $params),
        ], $userId, $application);
    }

    /**
     * 获取仓库详情
     *
     * @param string $owner 仓库所有者
     * @param string $repo 仓库名称
     * @param string|null $userId 用户ID
     * @param GiteeApplication|null $application 应用
     * @return array 仓库详情
     */
    public function getRepository(string $owner, string $repo, ?string $userId = null, GiteeApplication $application = null): array
    {
        return $this->giteeClient->request('GET', "/repos/$owner/$repo", [], $userId, $application);
    }

    /**
     * 获取仓库分支列表
     *
     * @param string $owner 仓库所有者
     * @param string $repo 仓库名称
     * @param string|null $userId 用户ID
     * @param GiteeApplication|null $application 应用
     * @return array 分支列表
     */
    public function getBranches(string $owner, string $repo, ?string $userId = null, GiteeApplication $application = null): array
    {
        return $this->giteeClient->request('GET', "/repos/$owner/$repo/branches", [], $userId, $application);
    }

    /**
     * 获取仓库问题列表
     *
     * @param string $owner 仓库所有者
     * @param string $repo 仓库名称
     * @param array $params 查询参数
     * @param string|null $userId 用户ID
     * @param GiteeApplication|null $application 应用
     * @return array 问题列表
     */
    public function getIssues(string $owner, string $repo, array $params = [], ?string $userId = null, GiteeApplication $application = null): array
    {
        return $this->giteeClient->request('GET', "/repos/$owner/$repo/issues", [
            'query' => $params,
        ], $userId, $application);
    }

    /**
     * 获取仓库PR列表
     *
     * @param string $owner 仓库所有者
     * @param string $repo 仓库名称
     * @param array $params 查询参数
     * @param string|null $userId 用户ID
     * @param GiteeApplication|null $application 应用
     * @return array PR列表
     */
    public function getPullRequests(string $owner, string $repo, array $params = [], ?string $userId = null, GiteeApplication $application = null): array
    {
        return $this->giteeClient->request('GET', "/repos/$owner/$repo/pulls", [
            'query' => $params,
        ], $userId, $application);
    }
}
