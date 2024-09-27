<?php

namespace App\Tests;

use App\PPPUserService;
use App\ZabbixService;
use PHPUnit\Framework\TestCase;

class PPPUserServiceTest extends TestCase
{
    public function testaBuscaUsuario(): void
    {
        $zabbix = $this->createStub(ZabbixService::class);
        $zabbix->method('fetchHosts')->willReturn([
            "result" => [
                "host" => "MKT-DQX-XRM-RTB-GW-AFINET",
                "interfaces" => ["ip" => $_SERVER["MK_TEST_IP"]]
            ]
        ]);
        
        $gateway = $this->createStub(Gate)
        $PPPUserService = new PPPUserService($zabbix, );

        $results = $PPPUserService->findUserByName("a");
        $this->assertNotEmpty($results);
    }
}
