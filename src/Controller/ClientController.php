<?php

namespace App\Controller;

use App\Entity\ClientSearch;
use App\Entity\User;
use App\Form\Type\PPPUserSearchFormType;
use App\GatewayFacade;
use App\GatewayService;
use App\ClientSearchPaginator;
use App\Utilities;
use App\ZabbixAPIClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ClientController extends AbstractController
{
    #[Route('/client', name: 'app_client_search', methods: 'GET')]
    public function search(
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

        $search = new ClientSearch();

        $form = $formFactory->createNamedBuilder(
            '',
            PPPUserSearchFormType::class,
            $search,
            [
                'hosts' => $hostTable,
                'searchInputPlaceholder' => 'Digite um nome, IP ou MAC de usuário',
            ]
        )
            ->setMethod('GET')
            ->getForm();

        $form->handleRequest($request);

        $results = [];
        $errors = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $search = $form->getData();
            if (false !== array_search('all', $search->getHosts())) {
                $search->setHosts($allowedHosts);
            }
            $page = $request->query->getInt('page', 1);
            $type = $utilities::guessSearchTypeFromQuery($search->getQuery());

            $filteredHosts = array_filter($zabbixHosts, function ($h) use (&$search) {
                if (false !== array_search($h['hostid'], $search->getHosts())) {
                    return true;
                }
            });
            $gwService = new GatewayService($filteredHosts);
            $results = $gwService->getShortUserDataBy($type, $search->getQuery());
            $errors = $gwService->getErrors();
            $search->setUserId($user->getId());
            $search->setHosts(
                array_values(
                    array_map(fn($h) => $h['hostid'], $filteredHosts)
                )
            );
            $search->setCreatedAt(new \DateTimeImmutable());
            $search->setType($type);
            $entityManager->persist($search);
            $entityManager->flush();

            $paginator = new ClientSearchPaginator($results, $page);
            $results = $paginator->paginate();
        }

        return $this->render('client/index.html.twig', [
            'form' => $form,
            'results' => $results,
            'errors' => $errors,
        ]);
    }

    #[Route('/client/detail', name: 'app_client_detail', methods: 'GET')]
    public function detail(
        Request $request,
        ZabbixAPIClient $zabbix,
        HttpClientInterface $httpClient,
        EntityManagerInterface $entityManager,
        #[MapQueryParameter] string $name,
        #[MapQueryParameter] string $gw,
    ): NotFoundHttpException|Response {
        $params = ['hostids' => $gw, 'output' => ['host'], 'selectInterfaces' => ['ip']];
        $result = $zabbix->fetchHosts($params)['result'];
        if (empty($result)) {
            return $this->createNotFoundException();
        }
        $config = GatewayFacade::createConfig($result[0]['interfaces'][0]['ip']);
        $client = $client = GatewayFacade::createClient($config);
        $gwService = new GatewayService(GatewayFacade::connect($client));
        $user = $gwService->getFullUserDataByName($name);
        $manufacturer = null;
        try {
            $apiResponse = $httpClient->
                request('GET', 'https://www.macvendorlookup.com/api/v2/' . $user['caller-id'])->toArray();
            $manufacturer = $apiResponse[0]['company'];
        } catch (\Throwable $th) {
            $manufacturer = 'N/A';
        }

        return $this->render('client/detail/index.html.twig', [
            'name' => $user['user'],
            'gw' => $result[0]['host'],
            'manufacturer' => $manufacturer,
        ]);
    }
}
