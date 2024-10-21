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
        Utilities $utilities,
        FormFactoryInterface $formFactory
    ): Response {

        $params = ["output" => ["host"], "selectInterfaces" => ["ip"]];
        $allowedHosts = $security->getUser()->getAllowedHostIds();
        $hosts = [];

        foreach ($zabbix->fetchHosts($params)["result"] as $h) {
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

        $query = $request->query->get("q");

        if ($query) {
            $filter = $utilities::guessSearchFilterFromQuery($query);
            $gw = $request->query->get("gw");
            $page = $request->query->get("page") ?? 1;
            $cacheKey = $query . $filter . $gw;

            $session = $request->getSession();
            $results = $session->get($cacheKey);

            if (!$results || time() - $results["meta"]["createdAt"] > 60) {

                $session->start();
                $results["meta"]["length"] = 0;
                $results["data"] = [];

                foreach ($hosts as $host) {
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
