<?php

namespace App;

final class GatewayCollection
{

    private $clients = [];
    public function __construct(private array $hosts)
    {
        foreach ($hosts as $host) {
            $hostname = $host["host"];
            $ip = $host["interfaces"][0]["ip"];
            $client = GatewayFacade::createClient(GatewayFacade::createConfig($ip));
            $this->clients[] = [
                "hostname" => $hostname,
                "ip" => $ip,
                "client" => GatewayFacade::connect($client)
            ];
        }
    }

    public function findShortUserDataBy(string $filter, string $query)
    {
        $results["meta"]["length"] = 0;
        $results["data"] = [];

        foreach ($this->clients as $client) {
            $hostname = $client["hostname"];
            $ip = $client["ip"];
            $userService = new PPPUserService($client["client"]);

            $users = $userService->getShortUserDataBy($filter, $query);

            if ($users) {
                $len = count($users);
                array_push(
                    $results["data"],
                    [
                        "meta" => [
                            "hostname" => $hostname,
                            "ip" => $ip
                        ],
                        "data" => $users,
                    ]

                );
                $results["meta"]["length"] += $len;
            }
        }

        $results["meta"]["createdAt"] = time();
        return $results;
    }

}
