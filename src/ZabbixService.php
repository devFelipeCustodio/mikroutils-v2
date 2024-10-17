<?php

namespace App;

use Exception;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ZabbixService
{
    private $url;
    private $token;
    private $groupID;
    public function __construct(
        private HttpClientInterface $client,
    ) {
        $this->url = $_SERVER["ZABBIX_URL"] . "/api_jsonrpc.php";
        $this->token = $_SERVER["ZABBIX_AUTH_TOKEN"];
        $this->groupID = $_SERVER["ZABBIX_GW_GROUPID"];
    }

    public function fetchHosts(): array
    {
        $params = ["groupids" => $this->groupID, "output" => ["host"], "selectInterfaces" => ["ip"]];

        $data = [

            "jsonrpc" => "2.0",
            "method" => "host.get",
            "params" => $params,
            "id" => 1,
            "auth" => $this->token

        ];
        
        $response = $this->client->request(
            'POST',
            $this->url,
            ["json" => $data]
        );

        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200)
            throw new Exception("[ZAB_CON_ERR] O servidor do Zabbix não retornou uma resposta válida. Verifique se a URL e o token são válidos.");
        $content = $response->toArray();
        return $content;
    }
}
