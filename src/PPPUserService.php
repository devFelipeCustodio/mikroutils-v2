<?php

namespace App;

final class PPPUserService
{


    public function __construct(private ZabbixService $zabbix, private GatewayFacade $gateway) {}

    public function findUserByName($query)
    {
        $hosts = $this->zabbix->fetchHosts();

        $results = [];

        foreach ($hosts as $host) {

            $config = GatewayFacade::createConfig($host["interfaces"]["ip"]);
            $client = GatewayFacade::createClient($config);
            $this->gateway = GatewayFacade::connect($client);
            $users = $this->gateway->getActivePPPUsers();

            $filtered = array_filter($users, function ($user) use (&$query) {
                return preg_match("/$query/i", $user["name"]);
            });

            array_merge($results, $filtered);

        }
        return $results;
    }
}
