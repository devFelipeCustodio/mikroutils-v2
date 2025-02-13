<?php

namespace App\Controller;

use App\Entity\ClientExport;
use App\Entity\User;
use App\Form\Type\ExportUsersFormType;
use App\GatewayService;
use App\ZabbixAPIClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ExportController extends AbstractController
{
    #[Route('/export/ppp_users', name: 'app_export_ppp_users', methods: 'GET')]
    public function export(
        Request $request,
        FormFactoryInterface $formFactory,
        ZabbixAPIClient $zabbix,
        EntityManagerInterface $entityManager
    ): ?Response {
        $user = $this->getUser();
        assert($user instanceof User);
        $allowedHosts = $user->getAllowedHostIds();
        $params = ['hostids' => $allowedHosts, 'output' => ['host'], 'selectInterfaces' => ['ip']];
        $zabbixHosts = $zabbix->fetchHosts($params)['result'];
        $hostTable = [];
        $errors = [];

        foreach ($zabbixHosts as $h) {
            $hostTable[$h['host']] = $h['hostid'];
        }

        $export = new ClientExport();

        $form = $formFactory->createNamedBuilder(
            '',
            ExportUsersFormType::class,
            $export,
            ['hosts' => $hostTable]
        )
            ->setMethod('GET')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $export = $form->getData();
            assert($export instanceof ClientExport);
            if (false !== array_search('all', $export->getHosts())) {
                $export->setHosts($allowedHosts);
            }

            $filteredHosts = array_filter($zabbixHosts, function ($h) use (&$export) {
                if (false !== array_search($h['hostid'], $export->getHosts())) {
                    return true;
                }
            });
            $gwService = new GatewayService($filteredHosts);
            $results = $gwService->getUsers();
            $errors = $gwService->getErrors();
            $csv = '';
            foreach ($results['data'] as $gw) {
                foreach ($gw['data'] as $username) {
                    $csv .= str_replace(',', "\,", $gw['meta']['hostname']) . ',' .
                        str_replace(',', "\,", $username['name']) . ',' .
                        str_replace(',', "\,", $username['caller-id']) . "\n";
                }
            }
            $request->getSession()->set("ppp_user_list", $csv);

            $export->setUser($user);
            $export->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($export);
            $entityManager->flush();
        }

        return $this->render('export/users.html.twig', [
            'form' => $form,
            'errors' => $errors
        ]);
    }

    #[Route('/export/ppp_users/download', name: 'app_export_ppp_users_download', methods: 'GET')]

    public function download(Request $request)
    {
        $csv = $request->getSession()->get("ppp_user_list");
        $response = new Response();
        $response->setContent($csv);
        $response->headers->set('Content-Type', 'text/csv');
        $request->getSession()->remove("ppp_user_list");
        $response->send();
    }
}
