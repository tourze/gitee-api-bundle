<?php

declare(strict_types=1);

namespace GiteeApiBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use GiteeApiBundle\Entity\GiteeRepository;

#[AdminCrud(
    routePath: '/gitee-api/repository',
    routeName: 'gitee_api_repository'
)]
final class GiteeRepositoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return GiteeRepository::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Gitee仓库')
            ->setEntityLabelInPlural('Gitee仓库管理')
            ->setPageTitle(Crud::PAGE_INDEX, 'Gitee仓库列表')
            ->setPageTitle(Crud::PAGE_NEW, '新建Gitee仓库')
            ->setPageTitle(Crud::PAGE_EDIT, '编辑Gitee仓库')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Gitee仓库详情')
            ->setDefaultSort(['pushTime' => 'DESC'])
            ->setSearchFields(['name', 'fullName', 'owner', 'description'])
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

        yield TextField::new('fullName', '仓库全名')
            ->setColumns('col-md-6')
            ->setRequired(true)
            ->setMaxLength(255)
        ;

        yield TextField::new('name', '仓库名称')
            ->setColumns('col-md-6')
            ->setRequired(true)
            ->setMaxLength(255)
        ;

        yield TextField::new('owner', '仓库所有者')
            ->setColumns('col-md-6')
            ->setRequired(true)
            ->setMaxLength(255)
        ;

        yield TextareaField::new('description', '仓库描述')
            ->setColumns('col-md-12')
            ->setMaxLength(1000)
            ->setNumOfRows(2)
        ;

        yield TextField::new('defaultBranch', '默认分支')
            ->setColumns('col-md-6')
            ->setRequired(true)
            ->setMaxLength(255)
        ;

        yield BooleanField::new('private', '是否私有')
            ->renderAsSwitch(false)
        ;

        yield BooleanField::new('fork', '是否为Fork')
            ->renderAsSwitch(false)
        ;

        yield UrlField::new('htmlUrl', 'HTML URL')
            ->setColumns('col-md-6')
            ->setRequired(true)
            ->onlyOnDetail()
        ;

        yield TextField::new('sshUrl', 'SSH URL')
            ->setColumns('col-md-6')
            ->setRequired(true)
            ->setMaxLength(255)
            ->onlyOnDetail()
        ;

        yield DateTimeField::new('pushTime', '最后推送时间')
            ->setColumns('col-md-6')
            ->setRequired(true)
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
            ->add('name')
            ->add('owner')
            ->add(BooleanFilter::new('private'))
            ->add(BooleanFilter::new('fork'))
            ->add(DateTimeFilter::new('pushTime'))
            ->add(DateTimeFilter::new('createTime'))
            ->add(DateTimeFilter::new('updateTime'))
        ;
    }
}
