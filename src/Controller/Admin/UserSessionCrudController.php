<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\UserSession;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserSessionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return UserSession::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Sessões')
            ->setPageTitle('detail', fn(UserSession $session) => (string) $session->getSession())
            ->setPageTitle('edit', fn(UserSession $session) => sprintf('Editando <b>%s</b>', $session->getSession()))
            ->setDateTimeFormat('dd/MM/yyyy, kk:mm');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::EDIT);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('user')
                ->formatValue(fn(User $user) => $user->getUsername())
                ->setLabel("Usuário"),
            TextField::new('ip')->setLabel("IP"),
            TextField::new('user_agent')->setLabel("Agente de usuário"),
            DateTimeField::new('created_at')->setLabel("Data da criação"),
        ];
    }
}
