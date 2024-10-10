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
    #[Route('/search', name: 'app_user_search')]
    public function index(Request $request, ZabbixService $zabbix): Response
    {
        $hasFilterType = $request->query->get("type");
        $page = $request->query->get("page") ?? 1;
        $cacheKey = $request->query->get("q") .
            $request->query->get("type") . $request->query->get("gw");
        $session = $request->getSession();
        $results = $session->get($cacheKey);

        if (!$results || time() - $results["meta"]["createdAt"] > 60) {

            $session->start();
            $results["meta"] = ["length" => 0];
            $results["data"] = [];

            foreach ($zabbix->fetchHosts()["result"] as $host) {
                $hostname = $host["host"];
                $hostid = $host["hostid"];
                $ip = $host["interfaces"][0]["ip"];

                $client = GatewayFacade::createClient(GatewayFacade::createConfig($ip));
                $gateway = GatewayFacade::connect($client);

                $userService = new PPPUserService($gateway);

                $users = $userService->findUserBy("name", $request->query->get("q"));

                if ($users) {
                    array_push(
                        $results["data"],
                        [
                            "hostname" => $hostname,
                            "hostid" => $hostid,
                            "users" => $users,
                        ]

                    );
                    $results["meta"]["length"] += count($users);
                }
            }

            $maxPage = ceil($results["meta"]["length"] / 20);

            $page = $page <= $maxPage ? $page : $maxPage;

            $results["meta"] += [
                "currentPage" => $page,
                "maxPage" => $maxPage,
                "next" => $page <= $maxPage,
                "previous" => $page > 1,
                "createdAt" => time()
            ];

            $session->set($cacheKey, $results);
        }

        $output["users"] = [];
        $counter = 0;

        foreach ($results["data"] as $result) {
            foreach ($result["users"] as $user) {
                $counter++;
                if ($counter <= ($page - 1) * 20)
                    continue;

                if (count($output["users"]) === 20)
                    break 2;

                $user["hostid"] = $result["hostid"];
                $user["hostname"] = $result["hostname"];
                array_push($output["users"], $user);
            }
        }
        
        $output["meta"] = $results["meta"];

        return $this->render('search/index.html.twig', ["output" => $output]);

    }
}
