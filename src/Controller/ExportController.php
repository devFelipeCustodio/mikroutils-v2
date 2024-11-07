<?php

namespace App\Controller;

use App\Entity\User;
use App\GatewayCollection;
use App\ZabbixAPIClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ExportController extends AbstractController
{
    #[Route('/export/ppp_users', name: 'app_export_ppp_users', methods: 'GET')]
    public function export(Request $request,
        FormFactoryInterface $formFactory,
        ZabbixAPIClient $zabbix,
        EntityManagerInterface $entityManager): ?Response
    {
        $user = $this->getUser();
        assert($user instanceof User);
        $allowedHosts = $user->getAllowedHostIds();
        $params = ['hostids' => $allowedHosts, 'output' => ['host'], 'selectInterfaces' => ['ip']];
        $zabbixHosts = $zabbix->fetchHosts($params)['result'];
        $hostTable = [];

        foreach ($zabbixHosts as $h) {
            $hostTable[$h['host']] = $h['hostid'];
        }

        $form = $formFactory->createNamedBuilder('hosts', ChoiceType::class, null, [
            'csrf_protection' => false,
            'label' => false,
            'multiple' => true,
            'expanded' => true,
            'label_attr' => [
                'class' => 'checkbox-switch',
            ],
            'choices' => array_merge(['Todos' => 'all'], $hostTable),
        ])
                ->add('Exportar', SubmitType::class, ['attr' => ['class' => 'btn-primary mt-3']])
                ->setMethod('GET')
                ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $gwCollection = new GatewayCollection($zabbixHosts);
            $results = $gwCollection->getUsers();
            $csv = '';
            foreach ($results['data'] as $gw) {
                foreach ($gw['data'] as $user) {
                    $csv .= str_replace(',', "\,", $gw['meta']['hostname']).','.
                        str_replace(',', "\,", $user['name']).','.
                        str_replace(',', "\,", $user['caller-id'])."\n";
                }
            }
            $response = new Response();
            $response->setContent($csv);
            $response->setStatusCode(200);
            $response->headers->set('Content-Type', 'text/csv');
            $response->send();

            return null;
        }

        return $this->render('export/users.html.twig', [
            'form' => $form,
        ]);
    }
}