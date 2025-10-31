<?php

declare(strict_types=1);

namespace GiteeApiBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use GiteeApiBundle\Entity\GiteeApplication;
use GiteeApiBundle\Enum\GiteeScope;

#[AdminCrud(
    routePath: '/gitee-api/application',
    routeName: 'gitee_api_application'
)]
final class GiteeApplicationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return GiteeApplication::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Gitee应用')
            ->setEntityLabelInPlural('Gitee应用管理')
            ->setPageTitle(Crud::PAGE_INDEX, 'Gitee应用列表')
            ->setPageTitle(Crud::PAGE_NEW, '新建Gitee应用')
            ->setPageTitle(Crud::PAGE_EDIT, '编辑Gitee应用')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Gitee应用详情')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['name', 'clientId', 'description'])
            ->showEntityActionsInlined()
            ->setFormThemes(['@EasyAdmin/crud/form_theme.html.twig'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->onlyOnIndex()
        ;

        yield TextField::new('name', '应用名称')
            ->setColumns('col-md-6')
            ->setRequired(true)
            ->setMaxLength(255)
        ;

        yield TextField::new('clientId', '客户端ID')
            ->setColumns('col-md-6')
            ->setRequired(true)
            ->setMaxLength(255)
        ;

        yield TextField::new('clientSecret', '客户端密钥')
            ->setColumns('col-md-6')
            ->setRequired(true)
            ->setMaxLength(255)
            ->hideOnIndex()
        ;

        yield UrlField::new('homepage', '应用主页')
            ->setColumns('col-md-6')
        ;

        yield TextareaField::new('description', '应用描述')
            ->setColumns('col-md-12')
            ->setMaxLength(1000)
            ->setNumOfRows(3)
        ;

        yield ChoiceField::new('scopes', '授权作用域')
            ->setChoices(array_combine(
                array_map(fn (GiteeScope $scope) => $scope->getLabel(), GiteeScope::cases()),
                GiteeScope::cases()
            ))
            ->allowMultipleChoices(true)
            ->setColumns('col-md-12')
            ->setRequired(true)
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
            ->add('name')
            ->add('clientId')
            ->add('homepage')
            ->add(DateTimeFilter::new('createTime'))
            ->add(DateTimeFilter::new('updateTime'))
        ;
    }
}
