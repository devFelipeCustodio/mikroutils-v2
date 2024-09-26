<?php

namespace App\Tests;

use Exception;
use App\GatewayFacade;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Dotenv\Dotenv;

class GatewayFacadeTest extends TestCase
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
        GatewayFacade::connect($config);
    }


    private function configureAndConnect(): void
    {
        $config = GatewayFacade::createConfig($_SERVER["MK_TEST_IP"]);
        $this->gw = GatewayFacade::connect($config);
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
        $result = $this->gw->findUserInterface($_SERVER["MK_TEST_USER"]);
        $this->assertNotEmpty($result);
    }

    public function testePegaInterfaceUsuarioInvalida(): void
    {
        $this->configureAndConnect();
        $result = $this->gw->findUserInterface("invalida");
        $this->assertNull($result);
    }

    public function testePegaQueueUsuarioValida(): void
    {
        $this->configureAndConnect();
        $result = $this->gw->findUserQueue($_SERVER["MK_TEST_USER"]);
        $this->assertNotNull($result);
    }

    public function testePegaQueueUsuarioInvalida(): void
    {
        $this->configureAndConnect();
        $result = $this->gw->findUserQueue("invalida");
        $this->assertNull($result);
    }

    public function testePegaTrafficUsuarioValida(): void
    {
        $this->configureAndConnect();
        $result = $this->gw->findUserTraffic($_SERVER["MK_TEST_USER"]);
        $this->assertNotNull($result);
    }

    public function testePegaTrafficUsuarioInvalida(): void
    {
        $this->configureAndConnect();
        $result = $this->gw->findUserTraffic("invalida");
        $this->assertNull($result);
    }

    public function testePegaIdentity(): void
    {
        $this->configureAndConnect();
        $result = $this->gw->getIdentity();
        $this->assertIsString($result);
    }
}
