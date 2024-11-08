<?php

namespace App;
use RouterOS\Exceptions\ConnectException;

final class GatewayCollection
{

    private $clients = [];
    private $errors = [];
    public function __construct(private array $hosts, private ?array $hostidsFromSearch = [])
    {
        foreach ($hosts as $host) {
            $hostname = $host["host"];
            $ip = $host["interfaces"][0]["ip"];
            try {
                $client = GatewayFacade::createClient(GatewayFacade::createConfig($ip));
                $this->clients[] = [
                    "hostname" => $hostname,
                    "ip" => $ip,
                    "client" => GatewayFacade::connect($client)
                ];
            } catch (ConnectException $e) {
                $this->errors[] = [
                    "hostname" => $hostname,
                    "message" => $e->getMessage()
                ];
            }
        }
    }

    public function getErrors() {
        return $this->errors;
    }

    public function findShortUserDataBy(string $filter, string $query)
    {
        $results["meta"]["length"] = 0;
        $results["data"] = [];

        foreach ($this->clients as $client) {
            $hostname = $client["hostname"];
            $ip = $client["ip"];
            $gwService = new GatewayService($client["client"]);

            $users = $gwService->getShortUserDataBy($filter, $query);

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

    public function getUsers()
    {
        $results["meta"]["length"] = 0;
        $results["data"] = [];

        foreach ($this->clients as $client) {
            $hostname = $client["hostname"];
            $gwService = new GatewayService($client["client"]);

            $users = $gwService->getUsers();

            if ($users) {
                $len = count($users);
                array_push(
                    $results["data"],
                    [
                        "meta" => [
                            "hostname" => $hostname,
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

    public function findLogsWith(string $query, ?string $mac){
        $results["meta"]["length"] = 0;
        $results["data"] = [];

        foreach ($this->clients as $client) {
            $hostname = $client["hostname"];
            $logs = $client["client"]->findLogsWith($query, $mac);

            if ($logs) {
                $len = count($logs);
                array_push(
                    $results["data"],
                    [
                        "meta" => [
                            "hostname" => $hostname,
                        ],
                        "data" => $logs,
                    ]

                );
                $results["meta"]["length"] += $len;
            }
        }

        $results["meta"]["createdAt"] = time();
        return $results;
    }

}
