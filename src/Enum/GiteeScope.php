<?php

namespace GiteeApiBundle\Enum;

enum GiteeScope: string
{
    case USER = 'user_info';
    case PROJECTS = 'projects';
    case PULL_REQUESTS = 'pull_requests';
    case ISSUES = 'issues';
    case NOTES = 'notes';
    case ENTERPRISES = 'enterprises';
    case GISTS = 'gists';
    case GROUPS = 'groups';
    case HOOKS = 'hook';

    public static function getDefaultScopes(): array
    {
        return [
            self::USER,
            self::PROJECTS,
            self::PULL_REQUESTS,
            self::ISSUES,
            self::NOTES,
        ];
    }

    /**
     * 将作用域数组转换为空格分隔的字符串
     */
    public static function toString(array $scopes): string
    {
        return implode(' ', array_map(fn(self $scope) => $scope->value, $scopes));
    }
}
