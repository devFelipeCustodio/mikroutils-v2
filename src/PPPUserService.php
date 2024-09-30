<?php

namespace App;

final class PPPUserService
{

    private $filterMap = ["name" => "name", "mac" => "caller-id", "ip" => "address"];

    public function __construct(private ZabbixService $zabbix, private GatewayFacade $gateway) {}

    public function findUserBy($filter, $query)
    {
        $hosts = $this->zabbix->fetchHosts();

        foreach ($hosts as $host) {
            // $config = GatewayFacade::createConfig($host["interfaces"]["ip"]);
            // $client = GatewayFacade::createClient($config);
            // $this->gateway = GatewayFacade::connect($client);
            $users = $this->gateway->getActivePPPUsers();

            $filtered = array_filter($users, function ($user) use (&$filter, &$query) {
                $regex = preg_match("/$query/i", $user[$this->filterMap[$filter]]);
                return $regex === 1;
            });
        }
        return $filtered;
    }

}
