<?php

namespace App\Controller;

use App\Form\Type\PPUserSearchType;
use App\GatewayFacade;
use App\PPPUserService;
use App\Utilities;
use App\ZabbixAPIClient;
use App\PPPUserSearchPaginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_user_search', methods: 'GET')]
    public function index(
        Request $request,
        ZabbixAPIClient $zabbix,
        Security $security,
        FormFactoryInterface $formFactory,
        Utilities $utilities
    ): Response {

        $allowedHosts = $security->getUser()->getAllowedHostIds();
        $params = ["hostids" => $allowedHosts, "output" => ["host"], "selectInterfaces" => ["ip"]];
        $response = $zabbix->fetchHosts($params)["result"];
        $hosts = [];

        foreach ($response as $h) {
            if (array_search($h["hostid"], $allowedHosts))
                $hosts[$h["host"]] = $h["hostid"];
        }

        $form = $formFactory->createNamedBuilder(
            "",
            PPUserSearchType::class,
            null,
            ["hosts" => $hosts]
        )
            ->setMethod('GET')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $query = $form->getData()["q"];
            $gw = $form->getData()["gw"] ?? [];
            $page = $form->getData()["page"] ?? 1;
            $filter = $utilities::guessSearchFilterFromQuery($query);
            $hash = array_reduce(
                $gw,
                function ($sum, $id) {
                    if (!$sum)
                        $sum = 0;
                    return $sum + $id;
                }
            );
            $cacheKey = $query . $hash;

            $session = $request->getSession();
            $results = $session->get($cacheKey);

            if (!$results || time() - $results["meta"]["createdAt"] > 60) {

                $session->start();
                $results["meta"]["length"] = 0;
                $results["data"] = [];

                foreach ($response as $host) {
                    $hostname = $host["host"];
                    $ip = $host["interfaces"][0]["ip"];

                    $client = GatewayFacade::createClient(GatewayFacade::createConfig($ip));
                    $gateway = GatewayFacade::connect($client);

                    $userService = new PPPUserService($gateway);

                    $users = $userService->findUserBy($filter, $query);

                    if ($users) {
                        $len = count($users);
                        array_push(
                            $results["data"],
                            [
                                "meta" => [
                                    "hostname" => $hostname,
                                    "ip" => $ip
                                ],
                                "data" => $users,
                            ]

                        );
                        $results["meta"]["length"] += $len;
                    }
                }

                $results["meta"]["createdAt"] = time();
                $session->set($cacheKey, $results);
            }

            $paginator = new PPPUserSearchPaginator($results, $page);
            $results = $paginator->paginate();

            return $this->render('search/results.html.twig', ["results" => $results]);

        }

        return $this->render('search/index.html.twig', ['form' => $form]);
    }
}
