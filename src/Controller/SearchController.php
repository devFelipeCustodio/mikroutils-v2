<?php

namespace App\Controller;

use App\Entity\Search;
use App\Form\Type\PPUserSearchType;
use App\GatewayCollection;
use App\Utilities;
use App\ZabbixAPIClient;
use App\PPPUserSearchPaginator;
use DateTimeImmutable;
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
        $zabbixHosts = $zabbix->fetchHosts($params)["result"];
        $hosts = [];

        foreach ($zabbixHosts as $h) {
            if (array_search($h["hostid"], $allowedHosts))
                $hosts[$h["host"]] = $h["hostid"];
        }

        $search = new Search();

        $form = $formFactory->createNamedBuilder(
            "",
            PPUserSearchType::class,
            $search,
            ["hosts" => $hosts]
        )
            ->setMethod('GET')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $search = $form->getData();
            if (count($search->getHosts()) === 0)
                $search->setHosts($allowedHosts);
            $page = $form->get("page")->getData() ?? 1;
            $type = $utilities::guessSearchTypeFromQuery($search->getQ());
            $hash = array_reduce(
                $allowedHosts,
                function ($sum, $id) {
                    if (!$sum)
                        $sum = 0;
                    return $sum + $id;
                }
            );
            $cacheKey = $search->getQ() . $hash;

            $session = $request->getSession();
            $results = $session->get($cacheKey);

            if (!$results || time() - $results["meta"]["createdAt"] > 60) {

                $session->start();
                $gwCollection = new GatewayCollection($zabbixHosts);
                $results = $gwCollection->findShortUserDataBy($type, $search->getQ());
                $session->set($cacheKey, $results);
            }

            $paginator = new PPPUserSearchPaginator($results, $page);
            $results = $paginator->paginate();

            $search->setUserId($security->getUser()->getId());
            $search->setHosts($allowedHosts);
            $search->setCreatedAt(new DateTimeImmutable());
            $search->setType($type);
            $search->setPage($page);
            return $this->render('search/results.html.twig', ["results" => $results]);

        }

        return $this->render('search/index.html.twig', ['form' => $form]);
    }
}
