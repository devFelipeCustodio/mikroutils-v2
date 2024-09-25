<?php

namespace App;

use \RouterOS\Config;
use \RouterOS\Client;
use \RouterOS\Query;

class GatewayAdapter
{
    private Config $config;
    private Client $client;

    public function init()
    {
        if (!$this->hasConfig())
            throw new \Exception('Não há configurações para este gateway. Use o método createConfig($address).');

        $this->client = new Client($this->config);
    }

    public function getActivePPPUsers()
    {
        $query = new Query('/ppp/active/print');
        return $this->client->query($query)->read();
    }

    public function getUserInterface(string $user)
    {
        $query = new Query('/interface/pppoe-server/monitor');
        $query->equal("numbers", "<pppoe-$user>")->equal('once');
        return $this->client->query($query)->read();
    }

    public function getUserQueue(string $user)
    {
        $query = (new Query('/queue/simple/print'))->where('target', "<pppoe-$user>");
        return $this->client->query($query)->read();
    }

    public function getUserTraffic(string $user)
    {
        $query = new Query('/interface/getall');
        $query->where('name', "<pppoe-$user>");
        return $this->client->query($query)->read();
    }

    public function getIdentity()
    {
        $query = new Query('/system/identity/print');
        return $this->client->query($query)->read();
    }

    public function createConfig(string $address)
    {
        $params = [
            'host' => $address,
            'user' => $_SERVER["MK_USER"],
            'pass' => $_SERVER["MK_PASS"],
            'port' => $_SERVER["MK_API_PORT"],
            'attempts' => 1,
            'socket_timeout' => 10,
            'timeout' => 2
        ];
        $this->config = new Config($params);
    }

    private function hasConfig()
    {
        if ($this->config === null) return false;
        return true;
    }
}
