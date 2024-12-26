<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\ZabbixAPIClient;
use DateTimeImmutable;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private ZabbixAPIClient $zabbix,
        public UserPasswordHasherInterface $userPasswordHasher
    ) {}
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Usuários')
            ->setPageTitle('new', 'Novo usuário')
            ->setPageTitle('detail', fn(User $user) => (string) $user->getUsername())
            ->setPageTitle('edit', fn(User $user) => sprintf('Editando <b>%s</b>', $user->getUsername()))
            ->setDateTimeFormat('dd/MM/yyyy, kk:mm');
    }

    public function configureFields(string $pageName): iterable
    {
        $rolesMap = ["ROLE_USER" => "Usuário", "ROLE_ADMIN" => "Administrador"];
        if (Crud::PAGE_INDEX === $pageName) {
            return [
                IdField::new('id'),
                TextField::new('username')->setLabel("Nome"),
                ArrayField::new('roles')->setLabel("Funções")
                    ->formatValue(function ($arr) use ($rolesMap) {
                        return implode(", ", array_map(function ($i) use ($rolesMap) {
                            if (isset($rolesMap[$i]))
                                return $rolesMap[$i];
                            return null;
                        }, $arr));
                    }),
                DateTimeField::new('created_at')->setLabel("Data da criação"),

            ];
        } else {
            $params = ['output' => ['host'], 'selectInterfaces' => ['ip']];
            $hostsMap = [];
            foreach ($this->zabbix->fetchHosts($params)["result"] as $i) {
                $hostsMap[$i["hostid"]] = $i["host"];
            };
            $fields =  [
                TextField::new('username')->setLabel("Nome"),
                TextField::new('password')->onlyWhenCreating(),
                ChoiceField::new('roles')->setLabel("Funções")
                    ->allowMultipleChoices()
                    ->setChoices(array_flip($rolesMap))
                    ->formatValue(function ($arr) use ($rolesMap) {
                        return implode(", ", array_map(function ($i) use ($rolesMap) {
                            if (isset($rolesMap[$i]))
                                return $rolesMap[$i];
                            return null;
                        }, $arr));
                    }),
                ChoiceField::new('allowed_host_ids')->setLabel("Hosts")
                    ->allowMultipleChoices()
                    ->setChoices(array_merge(["Todos" => implode(",", array_flip($hostsMap))], array_flip($hostsMap)))
                    ->formatValue(function ($arr) use ($hostsMap) {
                        return implode(", ", array_map(function ($i) use ($hostsMap) {
                            return $hostsMap[$i];
                        }, $arr));
                    })
            ];

            if (Crud::PAGE_DETAIL === $pageName) {
                array_push($fields, DateTimeField::new('created_at')->setLabel("Data da criação"));
            }

            return $fields;
        }
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilder
    {
        $formBuilder = parent::createNewFormBuilder($entityDto, $formOptions, $context);
        $user = $entityDto->getInstance();
        assert($user instanceof User);
        $user->setCreatedAt(new DateTimeImmutable());
        return $this->addPasswordEventListener($formBuilder);
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilder
    {
        $formBuilder = parent::createEditFormBuilder($entityDto, $formOptions, $context);
        return $this->addPasswordEventListener($formBuilder);
    }

    private function addPasswordEventListener(FormBuilder $formBuilder): FormBuilder
    {
        return $formBuilder->addEventListener(FormEvents::POST_SUBMIT, $this->hashPassword());
    }

    private function hashPassword()
    {
        return function ($event) {
            $form = $event->getForm();
            if (!$form->isValid()) {
                return;
            }
            $password = $form->get('password')->getData();
            if ($password === null) {
                return;
            }
            $user = $this->getUser();
            assert($user instanceof User);
            $hash = $this->userPasswordHasher->hashPassword($user, $password);
            $form->getData()->setPassword($hash);
        };
    }
}
