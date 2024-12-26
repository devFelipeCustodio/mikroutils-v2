<?php

namespace App\Controller\Admin;

use App\Entity\ClientExport;
use App\Entity\User;
use App\ZabbixAPIClient;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

class ClientExportCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ClientExport::class;
    }

    public function __construct(private ZabbixAPIClient $zabbix,) {}

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Exportações de usuários')
            ->setPageTitle('detail', fn (ClientExport $export) => $export->getCreatedAt()->format('d/m/Y, H:i'))
            ->setDateTimeFormat('dd/MM/yyyy, kk:mm');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
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
            CollectionField::new('hosts')
                ->onlyOnDetail()
                ->formatValue(function ($arr) use ($hostsMap) {
                    return implode(", ", array_map(function ($i) use ($hostsMap) {
                        return $hostsMap[$i];
                    }, $arr));
                }),
            DateTimeField::new('created_at')->setLabel("Data da exportação")
        ];
    }
}
