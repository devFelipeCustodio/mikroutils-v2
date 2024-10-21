<?php

namespace App;

use Exception;

final class PPPUserService
{

    private static $FILTER_MAP = ["name" => "name", "mac" => "caller-id", "ip" => "address"];

    public function __construct(private GatewayFacade $gateway)
    {
    }

    public function findUserBy($filter, $query)
    {
        $users = $this->gateway->getActivePPPUsers();

        $filtered = array_filter($users, function ($user) use (&$filter, &$query) {
            $regex = preg_match("/$query/i", $user[PPPUserService::$FILTER_MAP[$filter]]);
            return $regex === 1;
        });
        return $filtered;
    }

    public function getFullUserDataByName(string $name)
    {
        $interfaceOverview = $this->gateway->findPPPoEInterfaceOverview($name);
        $queue = $this->gateway->findPPPoEQueue($name);
        $interface = $this->gateway->findPPPoEInterface($name);

        if (!$interfaceOverview || !$interface)
            throw new Exception("Usuário inexistente!");

        return [

            "user" => $interfaceOverview["user"],
            "caller-id" => $interfaceOverview["caller-id"],
            "interface" => $interfaceOverview["interface"],
            "uptime" => $interfaceOverview["uptime"],
            "local-address" => $interfaceOverview["local-address"],
            "remote-address" => $interfaceOverview["remote-address"],
            "max-limit" => $queue["max-limit"],
            "rx-byte" => $interface["rx-byte"],
            "tx-byte" => $interface["tx-byte"],
            "last-link-up-time" => $interface["last-link-up-time"]

        ];
    }
}
