<?php

namespace App\Controller\Admin;

use App\Entity\ClientDetail;
use App\Entity\User;
use App\ZabbixAPIClient;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

class ClientDetailCrudController extends AbstractCrudController
{
    public function __construct(private ZabbixAPIClient $zabbix,) {}
    public static function getEntityFqcn(): string
    {
        return ClientDetail::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Visualizações de clientes')
            ->setPageTitle('detail', fn(ClientDetail $clientDetail) => (string) $clientDetail->getClientName())
            ->setDateTimeFormat('dd/MM/yyyy, kk:mm');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        $params = ['output' => ['host'], 'selectInterfaces' => ['ip']];
        $hostsMap = [];
        foreach ($this->zabbix->fetchHosts($params)["result"] as $i) {
            $hostsMap[$i["hostid"]] = $i["host"];
        };
        return [
            AssociationField::new('user')
                ->formatValue(fn(User $user) => $user->getUsername())
                ->setLabel("Usuário"),
            CollectionField::new('host')
                ->formatValue(fn ($value) => $hostsMap[$value]),
            DateTimeField::new('created_at')->setLabel("Data da visualização"),
        ];
    }
}
