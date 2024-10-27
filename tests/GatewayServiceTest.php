<?php

namespace App\Tests;

use App\GatewayFacade;
use App\GatewayService;
use Exception;
use PHPUnit\Framework\TestCase;

class GatewayServiceTest extends TestCase
{
    private $gateway;
    private $PPPUserService;

    protected function setUp(): void
    {
        $this->gateway = $this->createStub(GatewayFacade::class);
        $this->gateway->method("getActivePPPUsers")->willReturn([
            [
                ".id" => "*80009420",
                "name" => "jose@afinet.com.br",
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
        $this->gateway->method("findPPPoEInterfaceOverview")->willReturn(
            [
                "uptime" => "21h27s",
                "user" => "teste@afinet.com.br",
                "caller-id" => "40:ED:00:FD:FA:F2",
                "interface" => "VLAN-118",
                "local-address" => "10.0.0.69",
                "remote-address" => "170.0.0.2"
            ]
        );
        $this->gateway->method("findPPPoEInterface")->willReturn(
            [
                "rx-byte" => "6526849744",
                "tx-byte" => "16681180963",
                "last-link-up-time" => "sep/30/2024 21:35:18"
            ]
        );

        $this->gateway->method("findPPPoEQueue")->willReturn(
            [
                "max-limit" => "160000000/520000000"
            ]
        );
        /** @disregard  */
        $this->PPPUserService = new GatewayService($this->gateway);
    }

    public function testaBuscaUsuarioPorNome(): void
    {

        $results = $this->PPPUserService->getShortUserDataBy("name", "test");
        $this->assertCount(1, $results);
    }

    public function testaBuscaUsuarioPorMAC(): void
    {
        $results = $this->PPPUserService->getShortUserDataBy("mac", "40:ED:00:FD:FA:F2");
        $this->assertCount(1, $results);
    }

    public function testaBuscaUsuarioPorIP(): void
    {
        $results = $this->PPPUserService->getShortUserDataBy("ip", "179.127.194.184");
        $this->assertCount(1, $results);
    }

    public function testaPegaUsuarioCompletoValida(): void
    {
        $results = $this->PPPUserService->getFullUserDataByName("teste@afinet.com.br");
        $this->assertNotEmpty($results);
    }

    public function testaPegaUsuarioCompletoInvalida(): void
    {
        $this->expectException(Exception::class);
        $this->gateway->method("findPPPoEInterfaceOverview")->willThrowException(new Exception());
        $this->PPPUserService->getFullUserDataByName("invalida");
    }
}
