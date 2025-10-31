<?php

declare(strict_types=1);

namespace GiteeApiBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use GiteeApiBundle\Entity\GiteeAccessToken;

#[AdminCrud(
    routePath: '/gitee-api/access-token',
    routeName: 'gitee_api_access_token'
)]
final class GiteeAccessTokenCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return GiteeAccessToken::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Gitee访问令牌')
            ->setEntityLabelInPlural('Gitee访问令牌管理')
            ->setPageTitle(Crud::PAGE_INDEX, 'Gitee访问令牌列表')
            ->setPageTitle(Crud::PAGE_NEW, '新建访问令牌')
            ->setPageTitle(Crud::PAGE_EDIT, '编辑访问令牌')
            ->setPageTitle(Crud::PAGE_DETAIL, '访问令牌详情')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['userId', 'giteeUsername', 'accessToken'])
            ->showEntityActionsInlined()
            ->setFormThemes(['@EasyAdmin/crud/form_theme.html.twig'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->onlyOnIndex()
        ;

        yield AssociationField::new('application', 'Gitee应用')
            ->setColumns('col-md-6')
            ->setRequired(true)
        ;

        yield TextField::new('userId', '用户ID')
            ->setColumns('col-md-6')
            ->setRequired(true)
            ->setMaxLength(255)
        ;

        yield TextField::new('giteeUsername', 'Gitee用户名')
            ->setColumns('col-md-6')
            ->setMaxLength(255)
        ;

        yield TextField::new('accessToken', '访问令牌')
            ->setColumns('col-md-6')
            ->setRequired(true)
            ->setMaxLength(255)
            ->hideOnIndex()
        ;

        yield TextField::new('refreshToken', '刷新令牌')
            ->setColumns('col-md-6')
            ->setMaxLength(255)
            ->onlyOnDetail()
        ;

        yield DateTimeField::new('expireTime', '过期时间')
            ->setColumns('col-md-6')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->onlyOnDetail()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('application')
            ->add('userId')
            ->add('giteeUsername')
            ->add(DateTimeFilter::new('expireTime'))
            ->add(DateTimeFilter::new('createTime'))
            ->add(DateTimeFilter::new('updateTime'))
        ;
    }
}
