<?php

declare(strict_types=1);

namespace GiteeApiBundle\Enum;

use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum GiteeScope: string implements BadgeInterface, Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case USER = 'user_info';
    case PROJECTS = 'projects';
    case PULL_REQUESTS = 'pull_requests';
    case ISSUES = 'issues';
    case NOTES = 'notes';
    case ENTERPRISES = 'enterprises';
    case GISTS = 'gists';
    case GROUPS = 'groups';
    case HOOKS = 'hook';

    /** @return array<int, self> */
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
     * @param array<int, self> $scopes
     */
    public static function toString(array $scopes): string
    {
        return implode(' ', array_map(fn (self $scope) => $scope->value, $scopes));
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::USER => '用户信息',
            self::PROJECTS => '项目管理',
            self::PULL_REQUESTS => '拉取请求',
            self::ISSUES => '问题管理',
            self::NOTES => '评论管理',
            self::ENTERPRISES => '企业管理',
            self::GISTS => '代码片段',
            self::GROUPS => '组织管理',
            self::HOOKS => 'Webhook',
        };
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::USER => self::PRIMARY,
            self::PROJECTS => self::SUCCESS,
            self::PULL_REQUESTS => self::INFO,
            self::ISSUES => self::WARNING,
            self::NOTES => self::SECONDARY,
            self::ENTERPRISES => self::DARK,
            self::GISTS => self::LIGHT,
            self::GROUPS => self::INFO,
            self::HOOKS => self::DANGER,
        };
    }
}
