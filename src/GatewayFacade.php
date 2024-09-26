<?php

namespace App;

use Exception;
use \RouterOS\Config;
use \RouterOS\Client;
use \RouterOS\Query;

final class GatewayFacade
{
    private Client $client;

    private function __construct(Client $client)
    {
        $this->client = $client;
    }

    public static function connect(Config $config): GatewayFacade
    {
        $client = null;
        try {
            $client = new Client($config);
        } catch (\Throwable $th) {
            throw new Exception("Falha na conexão: " . $th->getMessage());
        }
        return new GatewayFacade($client);
    }

    public static function createConfig(string $address): Config
    {
        $params = [
            "host" => $address,
            "attempts" => 1,
            "socket_timeout" => 10,
            "timeout" => 2
        ];

        $ENV_VARS_MAP = ["user" => "MK_USER", "pass" => "MK_PASS", "port" => "MK_API_PORT"];

        foreach ($ENV_VARS_MAP as $k => $v) {
            if (key_exists($v, $_SERVER))
                $params[$k] = $_SERVER[$v];
        }

        return new Config($params);
    }

    public function getActivePPPUsers()
    {
        $query = new Query("/ppp/active/print");
        return $this->client->query($query)->read();
    }

    private function isValidResult($result)
    {
        if (
            key_exists("after", $result) &&
            key_exists("message", $result["after"]) &&
            $result["after"]["message"] === "no such item"
        )
            return false;

        return true;
    }

    public function findUserInterface(string $user)
    {
        $query = new Query("/interface/pppoe-server/monitor");
        $query->equal("numbers", "<pppoe-$user>")->equal("once");
        $result =  $this->client->query($query)->read();

        if (!$this->isValidResult($result))
            return null;

        return $result[0] ?? null;
    }

    public function findUserQueue(string $user)
    {
        $query = new Query("/queue/simple/print");
        $query = $query->where("target", "<pppoe-$user>");
        $result = $this->client->query($query)->read();
        return $result[0] ?? null;
    }

    public function findUserTraffic(string $user)
    {
        $query = new Query("/interface/getall");
        $query = $query->where("name", "<pppoe-$user>");
        $result = $this->client->query($query)->read();
        return $result[0] ?? null;
    }

    /**
     * Pega a identidade configurada no sistema.
     * @return string
     */
    public function getIdentity()
    {
        $query = new Query("/system/identity/print");
        $result = $this->client->query($query)->read();
        return $result[0]["name"];
    }
}
