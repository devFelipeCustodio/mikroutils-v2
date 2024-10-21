<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ZabbixStubController extends AbstractController
{
    #[Route('/zabbix', name: 'app_zabbix_stub',
        methods: ["POST"], condition: "'dev' === '%kernel.environment%'")]
    public function index(): JsonResponse
    {
        return $this->json(
            [
                "result" => [
                    [
                        "hostid" => "11131",
                        "host" => "GW-VIRTUAL-1",
                        "interfaces" => [
                            ["ip" => "192.168.1.100"]
                        ]
                    ],
                    [
                        "hostid" => "11132",
                        "host" => "GW-VIRTUAL-2",
                        "interfaces" => [
                            ["ip" => "192.168.1.101"]
                        ]
                    ]
                ]
            ]
        );
    }
}
