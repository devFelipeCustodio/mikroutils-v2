<?php

namespace App\Controller;

use App\ClientSearchPaginator;
use App\Entity\ClientDetail;
use App\Entity\ClientSearch;
use App\Entity\User;
use App\Form\Type\PPPUserSearchFormType;
use App\GatewayService;
use App\Utilities;
use App\ZabbixAPIClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\ItemInterface;
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

            $gwService = new GatewayService($zabbixHosts);
            $results = $gwService->getShortUserDataBy($type, $search->getQuery());
            $errors = $gwService->getErrors();
            $search->setUser($user);
            $search->setHosts(
                array_values(
                    array_map(fn($h) => $h['hostid'], $zabbixHosts)
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

    #[Route('/client/{gw}/{name}', name: 'app_client_detail', methods: 'GET')]
    public function detail(
        ZabbixAPIClient $zabbix,
        HttpClientInterface $httpClient,
        Utilities $util,
        EntityManagerInterface $entityManager,
        string $gw,
        string $name,
    ): NotFoundHttpException|Response|RedirectResponse {
        $params = ['hostids' => [$gw], 'output' => ['host'], 'selectInterfaces' => ['ip']];
        $result = $zabbix->fetchHosts($params)['result'];
        if (empty($result)) {
            return $this->createNotFoundException();
        }
        $host = new GatewayService($result);
        $errors = $host->getErrors();
        if (!empty($errors)) {
            $this->addFlash(
                'danger',
                $util::formatGatewayError($errors[0])
            );
            return $this->redirectToRoute('app_client_search');
        }
        $data = $host->getFullUserDataByName($name);
        $logs = $host->findLogsWith($name, $data["caller-id"]);
        $cache = new FilesystemAdapter();
        $manufacturer = $cache->get("macvendor." . $data["caller-id"], function (ItemInterface $item) use ($data, $httpClient): string {
            $item->expiresAfter(720);
            try {
                $apiResponse = $httpClient->
                    request('GET', 'https://www.macvendorlookup.com/api/v2/' . $data['caller-id'])->toArray();
                $manufacturer = $apiResponse[0]['company'];
            } catch (\Throwable $th) {
                $manufacturer = 'N/A';
            }
            return $manufacturer;
        });

        $maxLimitUp = 0;
        $maxLimitDown = 0;
        $maxLimit = explode("/", $data['max-limit']);
        
        if($maxLimit[0] !== null && $maxLimit[1] !== null){
            $maxLimitUp = $util::formatBytes($maxLimit[0]);
            $maxLimitDown = $util::formatBytes($maxLimit[1]);
        }

        $clientDetail = new ClientDetail();
        $clientDetail
            ->setUser($this->getUser())
            ->setHost($gw)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setClientName($name);

        $entityManager->persist($clientDetail);
        $entityManager->flush();

        return $this->render('client/detail/index.html.twig', [
            'name' => $data['user'],
            'gw' => $result[0]['host'],
            'callerId' => $data['caller-id'],
            'interface' => $data['interface'],
            'uptime' => $data['uptime'],
            'localAddress' => $data['local-address'],
            'remoteAddress' => $data['remote-address'],
            'maxLimitUp' => $maxLimitUp,
            'maxLimitDown' => $maxLimitDown,
            'rxByte' => $util::formatBytes($data['rx-byte']),
            'txByte' => $util::formatBytes($data['tx-byte']),
            'lastLinkUpTime' => $data['last-link-up-time'],
            'manufacturer' => $manufacturer,
            'logs' => $logs
        ]);
    }
}
