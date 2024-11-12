<?php

namespace App\Controller;

use App\Entity\LogSearch;
use App\Entity\User;
use App\Form\Type\PPPUserSearchFormType;
use App\GatewayService;
use App\Utilities;
use App\ZabbixAPIClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LogsController extends AbstractController
{
    #[Route('/logs', name: 'app_logs')]
    public function index(
        Request $request,
        ZabbixAPIClient $zabbix,
        FormFactoryInterface $formFactory,
        Utilities $utilities,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $this->getUser();
        assert($user instanceof User);
        $allowedHosts = $user->getAllowedHostIds();
        $params = ['hostids' => $allowedHosts, 'output' => ['host'], 'selectInterfaces' => ['ip']];
        $zabbixHosts = $zabbix->fetchHosts($params)['result'];
        $hostTable = [];

        foreach ($zabbixHosts as $h) {
            $hostTable[$h['host']] = $h['hostid'];
        }

        $log = new LogSearch();

        $form = $formFactory->createNamedBuilder(
            '',
            PPPUserSearchFormType::class,
            $log,
            [
                'hosts' => $hostTable,
                'searchInputPlaceholder' => 'O que deseja pesquisar?',
            ]
        )
            ->setMethod('GET')
            ->getForm();

        $form->handleRequest($request);

        $results = [];
        $errors = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $log = $form->getData();
            assert($log instanceof LogSearch);
            if (false !== array_search('all', $log->getHosts())) {
                $log->setHosts($allowedHosts);
            }

            $filteredHosts = array_filter($zabbixHosts, function ($h) use (&$log) {
                if (false !== array_search($h['hostid'], $log->getHosts())) {
                    return true;
                }
            });

            $gwService = new GatewayService($filteredHosts);
            $results = $gwService->findLogsWith($log->getQuery(), null);
            $errors = $gwService->getErrors();
            $log->setUserId($user->getId());
            $log->setHosts(
                array_values(
                    array_map(fn ($h) => $h['hostid'], $filteredHosts)
                )
            );
            $log->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($log);
            $entityManager->flush();
        }

        return $this->render('log/index.html.twig', [
            'form' => $form,
            'results' => $results,
            'errors' => $errors,
        ]);
    }
}
