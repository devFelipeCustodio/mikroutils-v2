<?php

namespace App;

use Exception;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ZabbixAPIClient
{
    private $url;
    private $token;
    private $groupID;
    public function __construct(
        private HttpClientInterface $client,
    ) {
        $this->url = $_SERVER["ZABBIX_API_URL"];
        $this->token = $_SERVER["ZABBIX_AUTH_TOKEN"];
        $this->groupID = $_SERVER["ZABBIX_GW_GROUPID"];
    }

    public function fetchHosts(array $params = null): array
    {
        if (!$params)
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

        $content = $response->toArray();
        return $content;
    }
}
