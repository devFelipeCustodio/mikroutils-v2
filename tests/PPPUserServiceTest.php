<?php

namespace App\Tests;

use App\GatewayFacade;
use App\PPPUserService;
use App\ZabbixService;
use PHPUnit\Framework\TestCase;

class PPPUserServiceTest extends TestCase
{
    private $zabbix;
    private $gateway;
    private $PPPUserService;

    protected function setUp(): void
    {
        $this->zabbix = $this->createStub(ZabbixService::class);
        $this->zabbix->method("fetchHosts")->willReturn([
            "result" => [
                "host" => "MKT-DQX-XRM-RTB-GW-AFINET",
                "interfaces" => ["ip" => $_SERVER["MK_TEST_IP"]]
            ]
        ]);

        $this->gateway = $this->createStub(GatewayFacade::class);
        $this->gateway->method("getActivePPPUsers")->willReturn([
            [
                ".id" => "*80009420",
                "name" => "wf.josearaujo@afinet.com.br",
                "service" => "pppoe",
                "caller-id" => "40:ED:00:FD:FA:F2",
                "address" => "179.127.194.184",
                "uptime" => "23h59m44s",
                "encoding" => "",
                "session-id" => "0x81909420",
                "limit-bytes-in" => "0",
                "limit-bytes-out" => "0",
                "radius" => true
            ],
            [
                ".id" => "*80009421",
                "name" => "teste@afinet.com.br",
                "service" => "pppoe",
                "caller-id" => "42:ED:00:FD:FA:F2",
                "address" => "179.127.194.185",
                "uptime" => "23h59m44s",
                "encoding" => "",
                "session-id" => "0x81909420",
                "limit-bytes-in" => "0",
                "limit-bytes-out" => "0",
                "radius" => true
            ]
        ]);
        /** @disregard  */
        $this->PPPUserService = new PPPUserService($this->zabbix, $this->gateway);
    }

    public function testaBuscaUsuarioPorNome(): void
    {

        $results = $this->PPPUserService->findUserBy("name", "test");
        $this->assertCount(1, $results);
    }

    public function testaBuscaUsuarioPorMAC(): void
    {
        $results = $this->PPPUserService->findUserBy("mac", "40:ED:00:FD:FA:F2");
        $this->assertCount(1, $results);
    }

    public function testaBuscaUsuarioPorIP(): void
    {
        $results = $this->PPPUserService->findUserBy("ip", "179.127.194.184");
        $this->assertCount(1, $results);
    }
}
