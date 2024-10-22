<?php

namespace App\Tests;

use Exception;
use App\GatewayFacade;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Dotenv\Dotenv;

class GatewayFacadeIntegrationTest extends TestCase
{
    private GatewayFacade $gw;

    protected function setUp(): void
    {
        $dotenv = new Dotenv();
        $dotenv->loadEnv(dirname(__FILE__, 2) . "/.env", true);
    }

    public function testeInstanciacaoInvalida(): void
    {
        $this->expectException(Exception::class);
        $config = GatewayFacade::createConfig(0);
        $client = GatewayFacade::createClient($config);
        GatewayFacade::connect($client);
    }


    private function configureAndConnect(): void
    {
        $config = GatewayFacade::createConfig($_SERVER["MK_TEST_IP"]);
        $client = GatewayFacade::createClient($config);
        $this->gw = GatewayFacade::connect($client);
    }


    public function testeInstanciacaoValida(): void
    {
        $this->configureAndConnect();
        $this->assertInstanceOf(GatewayFacade::class, $this->gw);
    }

    public function testeBuscaUsuariosValida(): void
    {
        $this->configureAndConnect();
        $users = $this->gw->getActivePPPUsers();
        $this->assertNotEmpty($users);
    }

    public function testePegaInterfaceUsuarioValida(): void
    {
        $this->configureAndConnect();
        $result = $this->gw->findPPPoEInterfaceOverview($_SERVER["MK_TEST_USER"]);
        $this->assertNotEmpty($result);
    }

    public function testePegaInterfaceUsuarioInvalida(): void
    {
        $this->configureAndConnect();
        $result = $this->gw->findPPPoEInterfaceOverview("invalida");
        $this->assertNull($result);
    }

    public function testePegaQueueUsuarioValida(): void
    {
        $this->configureAndConnect();
        $result = $this->gw->findPPPoEQueue($_SERVER["MK_TEST_USER"]);
        $this->assertNotNull($result);
    }

    public function testePegaQueueUsuarioInvalida(): void
    {
        $this->configureAndConnect();
        $result = $this->gw->findPPPoEQueue("invalida");
        $this->assertNull($result);
    }

    public function testePegaTrafficUsuarioValida(): void
    {
        $this->configureAndConnect();
        $result = $this->gw->findPPPoEInterface($_SERVER["MK_TEST_USER"]);
        $this->assertNotNull($result);
    }

    public function testePegaTrafficUsuarioInvalida(): void
    {
        $this->configureAndConnect();
        $result = $this->gw->findPPPoEInterface("invalida");
        $this->assertNull($result);
    }

    public function testePegaIdentity(): void
    {
        $this->configureAndConnect();
        $result = $this->gw->getIdentity();
        $this->assertIsString($result);
    }

    public function testePegaLogs(): void
    {
        $this->configureAndConnect();
        $result = $this->gw->findLogsWith($_SERVER["MK_TEST_USER"], "A0");
        $this->assertNotEmpty($result);
    }
}
