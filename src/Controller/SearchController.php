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

        $results = [];

        foreach ($zabbix->fetchHosts()["result"] as $host) {
            $name = $host["host"];
            $ip = $host["interfaces"][0]["ip"];

            $client = GatewayFacade::createClient(GatewayFacade::createConfig($ip));
            $gateway = GatewayFacade::connect($client);

            $userService = new PPPUserService($gateway);

            $users = $userService->findUserBy("name", $request->query->get("q"));

            if ($users)
                $results[] = [
                    "name" => $name,
                    "users" => $users
                ];
        }

        return $this->render('search/search.html.twig', ["results" => $results]);
    }
}
