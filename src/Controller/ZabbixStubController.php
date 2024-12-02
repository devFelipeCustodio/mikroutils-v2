<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ZabbixStubController extends AbstractController
{
    #[Route('/zabbix', name: 'app_zabbix_stub',
        methods: ['POST'], condition: "'dev' === '%kernel.environment%'")]
    public function index(Request $request): JsonResponse
    {
        $result = [
            [
                'hostid' => '11132',
                'host' => 'GW-VIRTUAL-2',
                'interfaces' => [
                    ['ip' => '192.168.1.101'],
                ],
            ],
            [
                'hostid' => '11131',
                'host' => 'GW-VIRTUAL-1',
                'interfaces' => [
                    ['ip' => '192.168.1.100'],
                ],
            ],
            [
                'hostid' => '11133',
                'host' => 'GW-VIRTUAL-3',
                'interfaces' => [
                    ['ip' => '192.168.1.102'],
                ],
            ],
            [
                'hostid' => '11134',
                'host' => 'GW-VIRTUAL-4',
                'interfaces' => [
                    ['ip' => '192.168.1.103'],
                ],
            ],
            [
                'hostid' => '11135',
                'host' => 'GW-VIRTUAL-5',
                'interfaces' => [
                    ['ip' => '192.168.1.104'],
                ],
            ],
            [
                'hostid' => '11136',
                'host' => 'GW-VIRTUAL-6',
                'interfaces' => [
                    ['ip' => '192.168.1.105'],
                ],
            ],
            [
                'hostid' => '11137',
                'host' => 'GW-VIRTUAL-7',
                'interfaces' => [
                    ['ip' => '192.168.1.106'],
                ],
            ],
            [
                'hostid' => '11138',
                'host' => 'GW-VIRTUAL-8',
                'interfaces' => [
                    ['ip' => '192.168.1.107'],
                ],
            ],
            [
                'hostid' => '11139',
                'host' => 'GW-VIRTUAL-9',
                'interfaces' => [
                    ['ip' => '192.168.1.108'],
                ],
            ],
        ];
        $content = json_decode($request->getContent(), true);
        if (isset($content["params"])) {
            $result = array_filter($result, function ($i) use ($content) {
                if (in_array($i["hostid"], $content["params"]["hostids"]) !== false)
                    return $i;
            });
        }
        return $this->json(
            [
                "result" => $result
            ]
        );
    }
}
