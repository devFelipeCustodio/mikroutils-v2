<?php

namespace App;

use \RouterOS\Config;
use \RouterOS\Client;
use \RouterOS\Query;

class GatewayFacade
{
    private function __construct(private Client $client) {}

    public static function connect(Client $client): GatewayFacade
    {
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

    public static function createClient(Config $config): Client
    {
        return new Client($config);
    }

    /**
     * @return array|null
     */
    public function getActivePPPUsers()
    {
        $query = new Query("/ppp/active/print");
        return $this->client->query($query)->read();
    }

    /**
     * Método responsável por validar a resposta de uma query 
     * realizada com o parâmetro "numbers".
     * @param array $result Resposta da query.
     * @return bool Retorna `false` caso o item buscado não exista, e `true` em caso de sucesso
     */
    private function itemExists($result)
    {
        if (
            key_exists("after", $result) &&
            key_exists("message", $result["after"]) &&
            $result["after"]["message"] === "no such item"
        )
            return false;

        return true;
    }

    /**
     * @param string $name Nome do usuário PPPoE.
     * @return array|null
     */
    public function findPPPoEInterfaceOverview(string $name)
    {
        $query = new Query("/interface/pppoe-server/monitor");
        $query->equal("numbers", "<pppoe-$name>")->equal("once");
        $result =  $this->client->query($query)->read();

        if (!$this->itemExists($result))
            return null;

        return $result[0] ?? null;
    }

    /**
     * @param string $target Nome do usuário PPPoE alvo da queue.
     * @return array|null
     */
    public function findPPPoEQueue(string $target)
    {
        $query = new Query("/queue/simple/print");
        $query = $query->where("target", "<pppoe-$target>");
        $result = $this->client->query($query)->read();
        return $result[0] ?? null;
    }

    /**
     * @param string $name Nome do usuário PPPoE.
     * @return array|null
     */
    public function findPPPoEInterface($name)
    {
        $query = new Query("/interface/getall");
        $query = $query->where("name", "<pppoe-$name>");
        $result = $this->client->query($query)->read();
        return $result[0] ?? null;
    }

    /**
     * @return string Retorna a identidade configurada no equipamento. 
     * Não é possível deixar a identidade me branco. 
     * "Mikrotik" é a identidade padrão.
     */
    public function getIdentity()
    {
        $query = new Query("/system/identity/print");
        $result = $this->client->query($query)->read();
        return $result[0]["name"];
    }

    public function findLogsWith($name, $mac)
    {
        $query = new Query('/log/print');
        $logs = $this->client->query($query)->read();

        $result = array_filter(array_reverse($logs), function ($log) use (&$name, &$mac) {
            if (
                preg_match("/$name/i", $log['message']) ||
                preg_match("/$mac/i", $log['message'])
            ) {
                return true;
            }
        });
        return $result;
    }
}
