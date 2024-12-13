<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\ZabbixAPIClient;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;

class UserCrudController extends AbstractCrudController
{
    public function __construct(private ZabbixAPIClient $zabbix)
    {
        
    }
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $rolesMap = ["ROLE_USER" => "Usuário", "ROLE_ADMIN" => "Administrador"];
        if (Crud::PAGE_INDEX === $pageName){
            return [
                IdField::new('id'),
                TextField::new('username')->setLabel("Nome"),
                ArrayField::new('roles')->setLabel("Funções")
                    ->formatValue(function ($arr) use ($rolesMap) {
                        return implode(", ", array_map(function ($i) use ($rolesMap) {
                            if(isset($rolesMap[$i]))
                                 return $rolesMap[$i];
                            return null;
                        }, $arr));
                    }),
                DateTimeField::new('created_at')->setLabel("Data de criação"),
                
            ];
        } else {
            $params = ['output' => ['host'], 'selectInterfaces' => ['ip']];
            $hostsMap = [];
            foreach($this->zabbix->fetchHosts($params)["result"] as $i){
                $hostsMap[$i["hostid"]] = $i["host"];
            };
            return [
                TextField::new('username')->setLabel("Nome"),
                TextField::new('password')->onlyOnForms(),
                ChoiceField::new('roles')->setLabel("Funções")
                    ->allowMultipleChoices()
                    ->setChoices(array_flip($rolesMap))
                    ->formatValue(function ($arr) use ($rolesMap) {
                        return implode(", ", array_map(function ($i) use ($rolesMap) {
                            if(isset($rolesMap[$i]))
                                 return $rolesMap[$i];
                            return null;
                        }, $arr));
                    }),
                DateTimeField::new('created_at')->setLabel("Data de criação")
                    ->onlyOnDetail()
                    ->onlyOnIndex(),
                ChoiceField::new('allowed_host_ids')->setLabel("Hosts")
                    ->allowMultipleChoices()
                    ->setChoices(array_merge(["Todos" => implode(",", array_flip($hostsMap))], array_flip($hostsMap)))
                    ->formatValue(function ($arr) use ($hostsMap) {
                        return implode(", ", array_map(function ($i) use ($hostsMap) {
                            return $hostsMap[$i];
                        }, $arr));
                    })
            ];
        }
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::EDIT);
    }

    
}
