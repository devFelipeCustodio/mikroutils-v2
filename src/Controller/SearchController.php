<?php

namespace App\Controller;

use App\GatewayFacade;
use App\PPPUserService;
use App\ZabbixService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'search')]
    public function index(Request $request, ZabbixService $zabbix): Response
    {
        $hasFilterType = $request->query->get("type");
        $currentPage = $request->query->get("page") ?? 1;

        $session = $request->getSession();
        $results = $session->get("searchResults");

        if (!$results || time() - $results["meta"]["createdAt"] < 60) {

            $session->start();
            $results["meta"] = ["length" => 0];

            foreach ($zabbix->fetchHosts()["result"] as $host) {
                $hostid = $host["hostid"];
                $ip = $host["interfaces"][0]["ip"];

                $client = GatewayFacade::createClient(GatewayFacade::createConfig($ip));
                $gateway = GatewayFacade::connect($client);

                $userService = new PPPUserService($gateway);

                $users = $userService->findUserBy("name", $request->query->get("q"));

                if ($users) {
                    $results[] = [
                        "hostid" => $hostid,
                        "users" => $users,
                    ];
                    if (count($users) > 1)
                        $results["meta"]["length"] += count($users);
                }
            }

            $maxPage = ceil($results["meta"]["length"] / 20);

            $results["meta"] += [
                "currentPage" =>
                    $currentPage <= $maxPage ? $currentPage : $maxPage,
                "maxPage" => $maxPage,
                "next" => $currentPage <= $maxPage,
                "previous" => $currentPage > 1,
                "createdAt" => time()
            ];

            $session->set("searchResults", $results);
        }

        return $this->render('search/index.html.twig', ["results" => $results]);

    }
}
